<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\BillAllocationService;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherNumberer;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\StoreVoucherRequest;
use Tally\Http\Requests\UpdateVoucherRequest;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Accounting\Money;
use Tally\Models\Ledger;
use Tally\Models\DeductionSection;
use Tally\Models\ManufacturingOrder;
use Tally\Models\PaymentRequest;
use Tally\Models\StockTransaction;
use Tally\Models\Voucher;
use Tally\Models\VoucherClass;
use Tally\Reporting\LedgerBalances;
use Illuminate\Support\Facades\DB;
use Tally\Support\Queries\DateRange;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VoucherController extends Controller
{
    public function other(): View
    {
        return view('tally::vouchers.other', [
            'types' => [
                ['key' => 'F4', 'label' => 'Contra', 'url' => tally_route('vouchers.contra.create')],
                ['key' => 'F5', 'label' => 'Payment', 'url' => tally_route('vouchers.payment.create')],
                ['key' => 'F6', 'label' => 'Receipt', 'url' => tally_route('vouchers.receipt.create')],
                ['key' => 'F7', 'label' => 'Journal', 'url' => tally_route('vouchers.journal.create')],
                ['key' => 'F8', 'label' => 'Sales', 'url' => tally_route('invoices.sales.create')],
                ['key' => 'F9', 'label' => 'Purchase', 'url' => tally_route('invoices.purchase.create')],
                ['key' => 'Alt+F5', 'label' => 'Stat Payment', 'url' => tally_route('vouchers.payment.create', ['stat' => 1])],
                ['key' => 'Ctrl+F8', 'label' => 'Credit Note', 'url' => tally_route('invoices.credit-note.create')],
                ['key' => 'Ctrl+F9', 'label' => 'Debit Note', 'url' => tally_route('invoices.debit-note.create')],
                ['key' => 'Alt+F6', 'label' => 'Reversing Journal', 'url' => tally_route('vouchers.journal.create', ['reversing' => 1])],
                ['key' => 'Ctrl+F7', 'label' => 'Memorandum', 'url' => tally_route('vouchers.journal.create', ['memo' => 1])],
                ['key' => 'Alt+F7', 'label' => 'Stock Journal', 'url' => tally_route('stock.adjustment.create')],
                ['key' => '', 'label' => 'Delivery Note', 'url' => tally_route('stock.out.create')],
                ['key' => '', 'label' => 'Receipt Note', 'url' => tally_route('stock.in.create')],
                ['key' => '', 'label' => 'Purchase Order', 'url' => tally_route('purchase-orders.create')],
                ['key' => '', 'label' => 'Sales Order', 'url' => tally_route('sales-orders.create')],
                ['key' => '', 'label' => 'Manufacturing Journal', 'url' => tally_route('manufacturing.create')],
            ],
        ]);
    }

    public function index(Request $request, WorkspaceContext $context): View
    {
        $scope = $this->scope($context);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $branch, $year] = $scope;
        $search = trim($request->string('q')->toString());
        $number = trim($request->string('number')->toString());
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
        $numberLike = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $number).'%';
        $branches = $company->branches()->where('is_active', true)->orderBy('name')->get();
        $branchFilter = (string) $request->input('branch_id', $branch->id);
        $ledgerId = $request->input('ledger_id');
        $ledgers = $company->ledgers()->orderBy('name')->get();

        if ($branchFilter !== 'all' && ! $branches->contains(fn (Branch $row) => (string) $row->id === $branchFilter)) {
            $branchFilter = (string) $branch->id;
        }

        if ($ledgerId !== null && $ledgerId !== '' && ! $ledgers->contains(fn (Ledger $row) => (string) $row->id === (string) $ledgerId)) {
            $ledgerId = null;
        }

        $vouchers = Voucher::query()
            ->with(['entries.ledger', 'branch'])
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->whereIn('voucher_type', [
                VoucherType::Journal->value,
                VoucherType::Payment->value,
                VoucherType::Receipt->value,
                VoucherType::Contra->value,
            ])
            ->when($branchFilter !== 'all', fn ($query) => $query->where('branch_id', $branchFilter))
            ->when($search !== '', function ($query) use ($like) {
                $query->where(function ($query) use ($like) {
                    $query->where('voucher_number', 'like', $like)
                        ->orWhere('narration', 'like', $like)
                        ->orWhere('reference_number', 'like', $like);
                });
            })
            ->when($number !== '', fn ($query) => $query->where('voucher_number', 'like', $numberLike))
            ->tap(fn ($query) => DateRange::apply($query, 'voucher_date', $this->dateFilter($request->input('from')), $this->dateFilter($request->input('to'))))
            ->when(
                in_array($request->input('voucher_type'), ['journal', 'payment', 'receipt', 'contra'], true),
                fn ($query) => $query->where('voucher_type', $request->input('voucher_type'))
            )
            ->when(
                in_array($request->input('status'), array_column(VoucherStatus::cases(), 'value'), true),
                fn ($query) => $query->where('status', $request->input('status'))
            )
            ->when($ledgerId, fn ($query) => $query->whereHas(
                'entries',
                fn ($entries) => $entries->where('ledger_id', $ledgerId)
            ))
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('tally::vouchers.index', [
            'company' => $company,
            'branch' => $branch,
            'year' => $year,
            'branches' => $branches,
            'ledgers' => $ledgers,
            'vouchers' => $vouchers,
            'filters' => [
                'q' => $search,
                'number' => $number,
                'from' => $request->input('from'),
                'to' => $request->input('to'),
                'voucher_type' => $request->input('voucher_type'),
                'status' => $request->input('status'),
                'ledger_id' => $ledgerId,
                'branch_id' => $branchFilter,
            ],
        ]);
    }

    public function create(Request $request, WorkspaceContext $context, VoucherNumberer $numbers, BillAllocationService $bills): View
    {
        $scope = $this->scope($context);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $branch, $year] = $scope;
        $type = $this->typeFromRoute($request);
        [$ledgers, $cashLedgers, $otherLedgers] = $this->ledgerSets($company);

        if ($request->boolean('stat') && $type === VoucherType::Payment) {
            $duties = $otherLedgers->filter(fn (Ledger $ledger) => $ledger->belongsToGroup('DUTIES'))->values();
            $otherLedgers = $duties->isNotEmpty() ? $duties : $otherLedgers;
        }

        $double = in_array($type, [VoucherType::Journal, VoucherType::Receipt], true);

        return view('tally::vouchers.create', [
            'company' => $company,
            'branch' => $branch,
            'year' => $year,
            'ledgers' => $ledgers,
            'cashLedgers' => $cashLedgers,
            'otherLedgers' => $otherLedgers,
            'action' => $this->storeRoute($type),
            'voucher' => new Voucher([
                'voucher_type' => $type,
                'voucher_date' => $this->defaultDate($company, $year),
                'status' => VoucherStatus::Draft,
            ]),
            'entries' => old('entries', $double ? [
                ['ledger_id' => '', 'debit' => '', 'credit' => '', 'narration' => '', 'reference' => ''],
                ['ledger_id' => '', 'debit' => '', 'credit' => '', 'narration' => '', 'reference' => ''],
            ] : [
                ['ledger_id' => '', 'debit' => '', 'credit' => '', 'narration' => '', 'reference' => ''],
            ]),
            'nextNumber' => $numbers->peek($company, $branch, $year, $type),
            'costCentres' => $company->costCentres()->where('is_active', true)->orderBy('name')->get(),
            'openBills' => $this->openBills($bills, $company, $year),
            'balances' => $this->ledgerBalanceMap($company, $branch->id, $year),
        ] + $this->voucherMasters($company, $year));
    }

    public function store(StoreVoucherRequest $request, WorkspaceContext $context, VoucherEngine $engine): RedirectResponse
    {
        $type = $this->typeFromRoute($request);
        $input = $this->applyCurrency($request->validated());
        $post = $this->wantsPost($request);
        $this->assertPaymentRequest($context->company(), $input, $post);
        $this->assertReversingDate($context->financialYear(), $input);
        $voucher = $engine->save(
            $context->company(),
            $context->branch(),
            $context->financialYear(),
            $request->user(),
            $type,
            $input,
            $post,
        );
        $this->settlePaymentRequest($voucher);
        $this->reverseJournal($engine, $context->financialYear(), $request->user(), $voucher);

        return redirect()
            ->route('books.tally.vouchers.show', $voucher)
            ->with('status', $voucher->isPosted() ? $type->label().' posted.' : $type->label().' saved as draft.');
    }

    public function show(WorkspaceContext $context, Voucher $voucher): View
    {
        $voucher->load(['entries.ledger', 'creator', 'branch', 'financialYear', 'invoice']);

        $neighbors = Voucher::query()
            ->where('company_id', $voucher->company_id)
            ->where('financial_year_id', $voucher->financial_year_id)
            ->where('voucher_type', $voucher->voucher_type);

        return view('tally::vouchers.show', [
            'company' => $context->company(),
            'voucher' => $voucher,
            'previousVoucher' => (clone $neighbors)->whereKeyNot($voucher->id)->where('id', '<', $voucher->id)->orderByDesc('id')->first(),
            'nextVoucher' => (clone $neighbors)->whereKeyNot($voucher->id)->where('id', '>', $voucher->id)->orderBy('id')->first(),
        ]);
    }

    public function edit(WorkspaceContext $context, Voucher $voucher, BillAllocationService $bills): View|RedirectResponse
    {
        if ($redirect = $this->invoiceRedirect($voucher)) {
            return $redirect;
        }

        if (! $voucher->isDraft()) {
            return redirect()
                ->route('books.tally.vouchers.show', $voucher)
                ->with('error', 'Posted and cancelled vouchers cannot be changed.');
        }

        $scope = $this->scope($context);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $branch, $year] = $scope;
        [$ledgers, $cashLedgers, $otherLedgers] = $this->ledgerSets($company);
        $voucher->load('entries');

        return view('tally::vouchers.edit', [
            'company' => $company,
            'branch' => $branch,
            'year' => $year,
            'ledgers' => $ledgers,
            'cashLedgers' => $cashLedgers,
            'otherLedgers' => $otherLedgers,
            'voucher' => $voucher,
            'entries' => old('entries', $voucher->entries->map(fn ($entry) => [
                'ledger_id' => $entry->ledger_id,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'narration' => $entry->narration,
                'reference' => $entry->reference,
                'cost_centre_id' => $entry->cost_centre_id,
            ])->all()),
            'nextNumber' => $voucher->voucher_number,
            'costCentres' => $company->costCentres()->where('is_active', true)->orderBy('name')->get(),
            'openBills' => $this->openBills($bills, $company, $year, $voucher->id),
            'balances' => $this->ledgerBalanceMap($company, $voucher->branch_id, $year),
            'postOnly' => false,
            'back' => tally_route('vouchers.index', ['voucher_type' => $voucher->voucher_type->value]),
        ] + $this->voucherMasters($company, $year));
    }

    public function entry(Request $request, WorkspaceContext $context, Voucher $voucher, BillAllocationService $bills): View|RedirectResponse
    {
        $voucher->loadMissing('invoice');

        if ($voucher->invoice) {
            return redirect()->route(
                $voucher->invoice->kind->routeName($voucher->invoice->isCancelled() ? 'show' : 'edit'),
                $voucher->invoice,
            );
        }

        if ($document = $this->stockDocument($voucher)) {
            return $document;
        }

        if ($voucher->isCancelled()) {
            return redirect()
                ->route('books.tally.vouchers.show', $voucher)
                ->with('error', 'A cancelled voucher cannot be altered.');
        }

        if ($voucher->isDraft()) {
            return $this->edit($context, $voucher, $bills);
        }

        $scope = $this->scope($context);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $branch, $year] = $scope;
        [$ledgers, $cashLedgers, $otherLedgers] = $this->ledgerSets($company);
        $voucher->load('entries');
        $back = $request->string('return')->toString() === 'day-book'
            ? tally_route('reports.day-book')
            : tally_route('vouchers.show', $voucher);

        return view('tally::vouchers.edit', [
            'company' => $company,
            'branch' => $branch,
            'year' => $year,
            'ledgers' => $ledgers,
            'cashLedgers' => $cashLedgers,
            'otherLedgers' => $otherLedgers,
            'voucher' => $voucher,
            'entries' => old('entries', $voucher->entries->map(fn ($entry) => [
                'ledger_id' => $entry->ledger_id,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'narration' => $entry->narration,
                'reference' => $entry->reference,
                'cost_centre_id' => $entry->cost_centre_id,
            ])->all()),
            'nextNumber' => $voucher->voucher_number,
            'costCentres' => $company->costCentres()->where('is_active', true)->orderBy('name')->get(),
            'openBills' => $this->openBills($bills, $company, $year, $voucher->id),
            'balances' => $this->ledgerBalanceMap($company, $voucher->branch_id, $year),
            'postOnly' => true,
            'back' => $back,
            'action' => tally_route('vouchers.alter', $voucher),
        ] + $this->voucherMasters($company, $year));
    }

    public function update(UpdateVoucherRequest $request, WorkspaceContext $context, VoucherEngine $engine, Voucher $voucher): RedirectResponse
    {
        $this->blockInvoiceVoucher($voucher);
        $input = $this->applyCurrency($request->validated());
        $post = $this->wantsPost($request);
        $this->assertPaymentRequest($context->company(), $input, $post);
        $this->assertReversingDate($voucher->financialYear, $input);
        $voucher = $engine->save(
            $context->company(),
            $voucher->branch,
            $voucher->financialYear,
            $request->user(),
            $voucher->voucher_type,
            $input,
            $post,
            $voucher,
        );
        $this->settlePaymentRequest($voucher);
        $this->reverseJournal($engine, $voucher->financialYear, $request->user(), $voucher);

        return redirect()
            ->route('books.tally.vouchers.show', $voucher)
            ->with('status', $voucher->isPosted() ? $voucher->voucher_type->label().' posted.' : 'Draft updated.');
    }

    public function alter(UpdateVoucherRequest $request, WorkspaceContext $context, VoucherEngine $engine, Voucher $voucher): RedirectResponse
    {
        $this->blockInvoiceVoucher($voucher);
        $input = $this->applyCurrency($request->validated());
        $returnToDayBook = $request->input('return') === 'day-book';

        $voucher = DB::transaction(function () use ($request, $context, $engine, $voucher, $input) {
            $this->releasePaymentRequest($voucher);
            $this->assertPaymentRequest($context->company(), $input, true);

            $saved = $engine->alter(
                $context->company(),
                $voucher->branch,
                $voucher->financialYear,
                $request->user(),
                $input,
                $voucher,
            );
            $this->settlePaymentRequest($saved);

            return $saved;
        });

        return redirect()
            ->to($returnToDayBook ? tally_route('reports.day-book') : tally_route('vouchers.show', $voucher))
            ->with('status', $voucher->voucher_type->label().' altered.');
    }

    public function post(VoucherEngine $engine, Voucher $voucher): RedirectResponse
    {
        $this->blockInvoiceVoucher($voucher);
        $this->assertPaymentRequest($voucher->company, [
            'payment_request_id' => $voucher->payment_request_id,
            'entries' => $voucher->entries->map(fn ($entry) => ['debit' => $entry->debit, 'credit' => $entry->credit])->all(),
        ], true);
        $voucher = $engine->post($voucher);
        $this->settlePaymentRequest($voucher);

        return redirect()
            ->route('books.tally.vouchers.show', $voucher)
            ->with('status', 'Voucher posted.');
    }

    public function cancel(VoucherEngine $engine, Voucher $voucher): RedirectResponse
    {
        $this->blockInvoiceVoucher($voucher);
        $engine->cancel($voucher);

        return redirect()
            ->route('books.tally.vouchers.show', $voucher)
            ->with('status', 'Voucher cancelled.');
    }

    public function print(Voucher $voucher): View
    {
        $voucher->load(['entries.ledger', 'creator', 'branch', 'financialYear', 'company']);

        return view('tally::vouchers.print', [
            'voucher' => $voucher,
        ]);
    }

    public function pdf(Voucher $voucher, \Tally\Documents\DocumentPdf $pdf): \Symfony\Component\HttpFoundation\Response
    {
        return $pdf->voucher($voucher);
    }

    public function destroy(VoucherEngine $engine, Voucher $voucher): RedirectResponse
    {
        $this->blockInvoiceVoucher($voucher);
        $engine->deleteDraft($voucher);

        return redirect()
            ->route('books.tally.vouchers.index')
            ->with('status', 'Draft deleted.');
    }

    /**
     * @return array{0: Company, 1: Branch, 2: FinancialYear}|View
     */
    /**
     * @return array<string, mixed>
     */
    private function voucherMasters(Company $company, FinancialYear $year): array
    {
        return [
            'currencies' => $company->currencies()->orderBy('code')->get(),
            'paymentRequests' => $company->paymentRequests()->with('ledger')->where('financial_year_id', $year->id)->whereIn('status', ['pending', 'partial'])->orderBy('due_date')->get(),
            'merchants' => $company->merchantProfiles()->orderBy('name')->get(),
            'voucherClasses' => VoucherClass::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'deductionSections' => DeductionSection::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('section_code')->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function applyCurrency(array $input): array
    {
        $rate = (string) ($input['exchange_rate'] ?? '');
        $currency = $input['currency_id'] ?? null;

        if (! $currency || $rate === '' || (float) $rate <= 0) {
            return $input;
        }

        $foreignDebit = '0.00';

        foreach ($input['entries'] as &$entry) {
            foreach (['debit', 'credit'] as $side) {
                $value = trim((string) ($entry[$side] ?? ''));

                if ($value === '' || (float) $value == 0.0) {
                    continue;
                }

                if ($side === 'debit') {
                    $foreignDebit = bcadd($foreignDebit, $value, 2);
                }

                $entry[$side] = bcmul($value, $rate, 2);
            }
        }

        $input['foreign_total'] = $foreignDebit;

        return $input;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function assertPaymentRequest(Company $company, array $input, bool $post): void
    {
        $id = $input['payment_request_id'] ?? null;

        if (! $id) {
            return;
        }

        $request = PaymentRequest::query()->where('company_id', $company->id)->whereKey($id)->first();

        if (! $request || $request->status === 'cancelled' || $request->status === 'paid') {
            throw ValidationException::withMessages([
                'payment_request_id' => 'Choose an open payment request for this company.',
            ]);
        }

        if (! $post) {
            return;
        }

        $paying = 0;

        foreach ($input['entries'] ?? [] as $entry) {
            $paying += Money::cents((string) ($entry['debit'] ?? 0));
        }

        $open = Money::cents((string) $request->amount) - Money::cents((string) $request->paid_amount);

        if ($paying > $open) {
            throw ValidationException::withMessages([
                'payment_request_id' => 'The payment is more than the amount still open on the request.',
            ]);
        }
    }

    private function releasePaymentRequest(Voucher $voucher): void
    {
        if (! $voucher->isPosted() || ! $voucher->payment_request_id) {
            return;
        }

        $request = PaymentRequest::query()->whereKey($voucher->payment_request_id)->first();

        if (! $request) {
            return;
        }

        $paid = max(0, Money::cents((string) $request->paid_amount) - Money::cents((string) $voucher->total_debit));
        $amount = Money::cents((string) $request->amount);
        $request->update([
            'paid_amount' => Money::format($paid),
            'voucher_id' => $paid === 0 ? null : $voucher->id,
            'status' => $paid === 0 ? 'pending' : ($paid >= $amount ? 'paid' : 'partial'),
        ]);
    }

    private function settlePaymentRequest(Voucher $voucher): void
    {
        if (! $voucher->isPosted() || ! $voucher->payment_request_id) {
            return;
        }

        $request = PaymentRequest::query()->whereKey($voucher->payment_request_id)->first();

        if (! $request) {
            return;
        }

        $paid = Money::cents((string) $request->paid_amount) + Money::cents((string) $voucher->total_debit);
        $amount = Money::cents((string) $request->amount);

        $request->update([
            'paid_amount' => Money::format(min($paid, $amount)),
            'voucher_id' => $voucher->id,
            'status' => $paid >= $amount ? 'paid' : 'partial',
        ]);
    }

    private function scope(WorkspaceContext $context): array|View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Journal',
                'message' => 'Select or create a company before entering vouchers.',
            ]);
        }

        if (! $context->branch() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', [
                'title' => 'Journal',
                'message' => 'Select a branch and a financial year before entering vouchers.',
            ]);
        }

        return [$company, $context->branch(), $context->financialYear()];
    }

    /**
     * @return array<int, string>
     */
    private function ledgerBalanceMap(Company $company, ?int $branchId, FinancialYear $year): array
    {
        $map = [];

        foreach (app(LedgerBalances::class)->asOn($company, $branchId, $year, $year->end_date->toDateString()) as $row) {
            $signed = $row['debit'] - $row['credit'];

            if ($signed === 0) {
                continue;
            }

            $map[$row['ledger']->id] = Money::format(abs($signed)).($signed > 0 ? ' Dr' : ' Cr');
        }

        return $map;
    }

    private function stockDocument(Voucher $voucher): ?RedirectResponse
    {
        $order = ManufacturingOrder::query()->where('voucher_id', $voucher->id)->first();

        if ($order) {
            return redirect()->route('books.tally.manufacturing.show', $order);
        }

        $reference = (string) $voucher->reference_number;

        if (preg_match('/^inv-stock-(\d+)$/', $reference, $match)) {
            $transaction = StockTransaction::query()->find((int) $match[1]);

            if ($transaction) {
                return redirect()->route($transaction->type->routeName('show'), $transaction);
            }
        }

        if (str_starts_with($reference, 'inv-')) {
            return redirect()
                ->route('books.tally.vouchers.show', $voucher)
                ->with('error', 'This voucher is changed from its stock or manufacturing document.');
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function assertReversingDate(FinancialYear $year, array $input): void
    {
        $date = (string) ($input['reverses_on'] ?? '');

        if ($date === '') {
            return;
        }

        if ($date < $year->start_date->toDateString() || $date > $year->end_date->toDateString()) {
            throw ValidationException::withMessages([
                'reverses_on' => 'The reversing date must fall in the open financial year.',
            ]);
        }
    }

    private function reverseJournal(VoucherEngine $engine, FinancialYear $year, \App\Models\User $user, Voucher $voucher): void
    {
        if (! $voucher->isPosted() || $voucher->voucher_type !== VoucherType::Journal || $voucher->reversed_voucher_id || ! $voucher->reverses_on) {
            return;
        }

        $voucher->load('entries');
        $reverse = $engine->save(
            $voucher->company,
            $voucher->branch,
            $year,
            $user,
            VoucherType::Journal,
            [
                'voucher_date' => $voucher->reverses_on->toDateString(),
                'reference_number' => $voucher->voucher_number,
                'narration' => 'Reversal of '.$voucher->voucher_number,
                'entries' => $voucher->entries->map(fn ($entry) => [
                    'ledger_id' => $entry->ledger_id,
                    'debit' => $entry->credit,
                    'credit' => $entry->debit,
                ])->all(),
            ],
            true,
        );
        $voucher->update(['reversed_voucher_id' => $reverse->id]);
    }

    private function blockInvoiceVoucher(Voucher $voucher): void
    {
        if (! in_array($voucher->voucher_type, [VoucherType::Sales, VoucherType::Purchase], true)) {
            return;
        }

        throw ValidationException::withMessages([
            'voucher' => 'Sales and purchase entries are changed from the invoice.',
        ]);
    }

    private function invoiceRedirect(Voucher $voucher): ?RedirectResponse
    {
        if (! in_array($voucher->voucher_type, [VoucherType::Sales, VoucherType::Purchase], true)) {
            return null;
        }

        $invoice = $voucher->invoice;

        if ($invoice) {
            return redirect()->route($invoice->kind->routeName($invoice->isCancelled() ? 'show' : 'edit'), $invoice);
        }

        return redirect()
            ->route('books.tally.vouchers.show', $voucher)
            ->with('error', 'Sales and purchase entries are changed from the invoice.');
    }

    /**
     * @return array{0: \Illuminate\Database\Eloquent\Collection<int, Ledger>, 1: \Illuminate\Database\Eloquent\Collection<int, Ledger>, 2: \Illuminate\Database\Eloquent\Collection<int, Ledger>}
     */
    private function ledgerSets(Company $company): array
    {
        $ledgers = $company->ledgers()->with('accountGroup.parent')->where('is_active', true)->orderBy('name')->get();

        return [
            $ledgers,
            $ledgers->filter(fn (Ledger $ledger) => $ledger->isCashOrBank())->values(),
            $ledgers->reject(fn (Ledger $ledger) => $ledger->isCashOrBank())->values(),
        ];
    }

    private function openBills(BillAllocationService $bills, Company $company, FinancialYear $year, ?int $ignoreVoucherId = null): \Illuminate\Support\Collection
    {
        $asOf = now()->toDateString();
        $start = $year->start_date->toDateString();
        $end = $year->end_date->toDateString();

        if ($asOf < $start || $asOf > $end) {
            $asOf = $end;
        }

        return $bills->openBills($company, $asOf, $ignoreVoucherId);
    }

    private function typeFromRoute(Request $request): VoucherType
    {
        return match ($request->route()?->getName()) {
            'vouchers.payment.create', 'vouchers.payment.store' => VoucherType::Payment,
            'vouchers.receipt.create', 'vouchers.receipt.store' => VoucherType::Receipt,
            'vouchers.contra.create', 'vouchers.contra.store' => VoucherType::Contra,
            default => VoucherType::Journal,
        };
    }

    private function wantsPost(StoreVoucherRequest|UpdateVoucherRequest $request): bool
    {
        return $request->input('action') === 'post' && ! $request->boolean('is_optional') && ! $request->boolean('is_memo');
    }

    private function storeRoute(VoucherType $type): string
    {
        return match ($type) {
            VoucherType::Payment => tally_route('vouchers.payment.store'),
            VoucherType::Receipt => tally_route('vouchers.receipt.store'),
            VoucherType::Contra => tally_route('vouchers.contra.store'),
            default => tally_route('vouchers.store'),
        };
    }

    private function defaultDate(Company $company, FinancialYear $year): string
    {
        return app(\Tally\Context\WorkingCalendar::class)->date($company, request()->user(), $year);
    }

    private function dateFilter(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
