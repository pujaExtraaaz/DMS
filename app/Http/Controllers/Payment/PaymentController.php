<?php

namespace App\Http\Controllers\Payment;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Services\FinancialYearService;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\PaymentAllocation;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sync\Support\BooksCompany;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Tally\Banking\BankBook;

class PaymentController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected OutstandingLedgerService $outstandingLedgerService,
        protected FinancialYearService $financialYearService,
    ) {}

    public function index(Request $request): View
    {
        $query = Payment::query()
            ->with(['customer', 'invoice', 'recorder', 'bankLedger.bankAccount'])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('paid_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('paid_at', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['payment_no', 'reference_no', 'method', 'status', 'notes'],
            ['customer' => ['name'], 'invoice' => ['invoice_no']]
        );

        $allowedSorts = [
            'payment_no' => 'payment_no',
            'method' => 'method',
            'amount' => 'amount',
            'paid_at' => 'paid_at',
            'customer' => function ($q, $dir) {
                $q->join('customers', 'payments.customer_id', '=', 'customers.id')
                    ->orderBy('customers.name', $dir)
                    ->select('payments.*');
            },
            'invoice' => function ($q, $dir) {
                $q->leftJoin('invoices', 'payments.invoice_id', '=', 'invoices.id')
                    ->orderBy('invoices.invoice_no', $dir)
                    ->select('payments.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'paid_at',
            defaultDirection: 'desc'
        );

        $payments = $query->paginate(15)->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'customer_id', 'method', 'date_from', 'date_to']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(Request $request): View
    {
        $invoice = $request->filled('invoice_id')
            ? Invoice::with('customer')->findOrFail($request->invoice_id)
            : null;

        return view('payments.create', $this->formPayload($invoice));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPayment($request);

        $this->financialYearService->assertOpen($validated['paid_at'] ?? now());

        $invoice = Invoice::findOrFail($validated['invoice_id']);
        $outstanding = (float) $invoice->grand_total - (float) $invoice->paid_amount;

        if ($validated['amount'] > $outstanding + 0.0001) {
            return $this->flashError('Payment amount exceeds invoice outstanding balance.');
        }

        DB::transaction(function () use ($validated, $invoice) {
            $payment = Payment::create([
                'payment_no' => $this->generatePaymentNo(),
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $validated['amount'],
                'method' => $validated['method'],
                'bank_ledger_id' => $validated['bank_ledger_id'],
                'reference_no' => $validated['reference_no'],
                'status' => 'completed',
                'paid_at' => $validated['paid_at'] ?? now(),
                'recorded_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
            ]);

            $newPaid = (float) $invoice->paid_amount + (float) $validated['amount'];
            $invoice->update([
                'paid_amount' => $newPaid,
                'status' => $newPaid >= (float) $invoice->grand_total ? 'paid' : 'partial',
            ]);

            $this->outstandingLedgerService->recordPayment($payment);
            $this->maybeRecordCheque($validated, $payment, $invoice);
        });

        return $this->flashSuccess('Payment recorded successfully.', 'payments.index');
    }

    public function show(Payment $payment): View
    {
        $payment->load(['customer', 'invoice', 'recorder', 'bankLedger.bankAccount']);

        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment): View
    {
        $payment->load(['customer', 'invoice', 'bankLedger.bankAccount']);

        return view('payments.edit', array_merge(
            $this->formPayload($payment->invoice),
            ['payment' => $payment]
        ));
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $this->validatedPayment($request, $payment);
        $this->financialYearService->assertOpen($validated['paid_at'] ?? $payment->paid_at ?? now());

        $invoice = Invoice::findOrFail($validated['invoice_id']);
        $oldAmount = (float) $payment->amount;
        $oldInvoiceId = (int) $payment->invoice_id;

        // Outstanding available = current due + this payment's amount if still on same invoice.
        $outstanding = (float) $invoice->grand_total - (float) $invoice->paid_amount;
        if ($oldInvoiceId === (int) $invoice->id) {
            $outstanding += $oldAmount;
        }

        if ($validated['amount'] > $outstanding + 0.0001) {
            return $this->flashError('Payment amount exceeds invoice outstanding balance.');
        }

        DB::transaction(function () use ($validated, $payment, $invoice, $oldAmount, $oldInvoiceId) {
            $oldCustomerId = (int) $payment->customer_id;

            if ($oldInvoiceId !== (int) $invoice->id) {
                $oldInvoice = Invoice::query()->find($oldInvoiceId);
                if ($oldInvoice) {
                    $this->applyInvoicePaidDelta($oldInvoice, -$oldAmount);
                }
                $this->applyInvoicePaidDelta($invoice, (float) $validated['amount']);
            } else {
                $this->applyInvoicePaidDelta($invoice, (float) $validated['amount'] - $oldAmount);
            }

            $payment->update([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $validated['amount'],
                'method' => $validated['method'],
                'bank_ledger_id' => $validated['bank_ledger_id'],
                'reference_no' => $validated['reference_no'],
                'paid_at' => $validated['paid_at'] ?? $payment->paid_at,
                'notes' => $validated['notes'] ?? null,
            ]);

            $payment->allocations()->delete();
            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
            ]);

            // Soft-correct outstanding by posting an adjustment for the delta.
            $delta = (float) $validated['amount'] - $oldAmount;
            if (abs($delta) > 0.0001 || $oldInvoiceId !== (int) $invoice->id) {
                if ($oldInvoiceId !== (int) $invoice->id) {
                    $this->outstandingLedgerService->recordAdjustment(
                        $oldCustomerId,
                        $oldAmount,
                        0,
                        "Payment {$payment->payment_no} reallocated (reverse)"
                    );
                    $this->outstandingLedgerService->recordPayment($payment->fresh());
                } elseif ($delta > 0) {
                    $this->outstandingLedgerService->recordAdjustment(
                        (int) $payment->customer_id,
                        0,
                        $delta,
                        "Payment {$payment->payment_no} amount increased"
                    );
                } else {
                    $this->outstandingLedgerService->recordAdjustment(
                        (int) $payment->customer_id,
                        abs($delta),
                        0,
                        "Payment {$payment->payment_no} amount decreased"
                    );
                }
            }
        });

        return $this->flashSuccess('Payment updated successfully.', 'payments.show', ['payment' => $payment]);
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        DB::transaction(function () use ($payment) {
            $payment->loadMissing('invoice');
            $amount = (float) $payment->amount;

            if ($payment->invoice) {
                $this->applyInvoicePaidDelta($payment->invoice, -$amount);
            }

            // Keep historical ledger rows intact; post a reversing adjustment.
            $this->outstandingLedgerService->recordAdjustment(
                (int) $payment->customer_id,
                $amount,
                0,
                "Payment {$payment->payment_no} deleted"
            );

            $payment->allocations()->delete();
            $payment->delete();
        });

        return $this->flashSuccess('Payment deleted successfully.', 'payments.index');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formPayload(?Invoice $invoice = null): array
    {
        $companyId = auth()->user()?->company_id;
        $company = $companyId
            ? Company::query()->find($companyId)
            : Company::query()->where('is_default', true)->first() ?? Company::query()->first();

        $upiAccounts = collect($company?->getCompanyBankAccounts() ?? [])
            ->filter(fn ($b) => filled($b['upi_id'] ?? null))
            ->map(fn ($b) => [
                'upi_id' => $b['upi_id'],
                'bank_name' => $b['bank_name'] ?? null,
                'account_number' => $b['account_number'] ?? null,
                'ifsc' => $b['ifsc'] ?? null,
                'branch' => $b['branch'] ?? null,
            ])
            ->unique('upi_id')
            ->values()
            ->all();

        // Include primary company UPI even if not in bank_accounts list.
        if ($company && filled($company->upi_id)) {
            $exists = collect($upiAccounts)->contains(fn ($u) => $u['upi_id'] === $company->upi_id);
            if (! $exists) {
                array_unshift($upiAccounts, [
                    'upi_id' => $company->upi_id,
                    'bank_name' => $company->bank_name,
                    'account_number' => $company->bank_account_no,
                    'ifsc' => $company->bank_ifsc,
                    'branch' => $company->bank_branch ?: $company->branch,
                ]);
            }
        }

        return [
            'invoice' => $invoice,
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'invoices' => Invoice::with('customer:id,name')
                ->where(function ($q) use ($invoice) {
                    $q->whereIn('status', ['issued', 'partial'])
                        ->whereRaw('(grand_total - paid_amount) > 0.009');
                    if ($invoice?->id) {
                        $q->orWhere('id', $invoice->id);
                    }
                })
                ->orderByDesc('invoice_date')
                ->get(),
            'bankLedgers' => $this->bankLedgers(),
            'upiAccounts' => $upiAccounts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedPayment(Request $request, ?Payment $existing = null): array
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,upi,bank,cheque,other',
            'bank_ledger_id' => 'nullable|integer|required_if:method,bank',
            'upi_id' => 'nullable|string|max:100|required_if:method,upi',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'cheque_no' => 'nullable|string|max:50',
            'cheque_date' => 'nullable|date',
            'cheque_bank_name' => 'nullable|string|max:255',
        ]);

        $validated['bank_ledger_id'] = $validated['method'] === 'bank'
            ? $this->assertBankLedger((int) $validated['bank_ledger_id'])
            : null;

        $validated['reference_no'] = $validated['method'] === 'upi'
            ? trim((string) ($validated['upi_id'] ?? ''))
            : ($existing?->reference_no);

        if ($validated['method'] === 'upi' && $validated['reference_no'] === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'upi_id' => 'Select a UPI ID for this payment.',
            ]);
        }

        return $validated;
    }

    protected function applyInvoicePaidDelta(Invoice $invoice, float $delta): void
    {
        $newPaid = max(0, round((float) $invoice->paid_amount + $delta, 2));
        $status = $newPaid <= 0.009
            ? 'issued'
            : ($newPaid >= (float) $invoice->grand_total ? 'paid' : 'partial');

        $invoice->update([
            'paid_amount' => $newPaid,
            'status' => $invoice->status === 'cancelled' ? 'cancelled' : $status,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function maybeRecordCheque(array $validated, Payment $payment, Invoice $invoice): void
    {
        if ($validated['method'] !== 'cheque' || blank($validated['cheque_no'] ?? null)) {
            return;
        }

        try {
            app(\App\Domains\Payment\Services\ChequeService::class)->create([
                'company_id' => auth()->user()?->company_id,
                'cheque_type' => \App\Domains\Payment\Models\Cheque::TYPE_RECEIVED,
                'direction' => 'received_from_client',
                'cheque_no' => $validated['cheque_no'],
                'customer_id' => $invoice->customer_id,
                'amount' => $validated['amount'],
                'cheque_date' => $validated['cheque_date'] ?? ($validated['paid_at'] ? date('Y-m-d', strtotime($validated['paid_at'])) : now()->toDateString()),
                'bank_name' => $validated['cheque_bank_name'] ?? null,
                'purpose' => 'collection',
                'status' => \App\Domains\Payment\Models\Cheque::STATUS_RECEIVED,
                'reference_voucher' => $payment->payment_no,
                'voucher_type' => 'Payment',
                'voucher_id' => $payment->id,
                'payment_id' => $payment->id,
                'notes' => $validated['notes'] ?? null,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Cheque recording failed for payment '.$payment->payment_no.': '.$e->getMessage());
        }
    }

    protected function bankLedgers()
    {
        $companyId = auth()->user()?->company_id;
        if (! $companyId) {
            return collect();
        }

        $books = BooksCompany::forOrganization((int) $companyId);
        if (! $books) {
            return collect();
        }

        return app(BankBook::class)->bankLedgers($books);
    }

    protected function assertBankLedger(int $ledgerId): int
    {
        $allowed = $this->bankLedgers()->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (! in_array($ledgerId, $allowed, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'bank_ledger_id' => 'Select the bank account that received this payment.',
            ]);
        }

        return $ledgerId;
    }

    protected function generatePaymentNo(): string
    {
        $date = now()->format('Ymd');
        $pattern = "PAY-{$date}-%";
        $last = Payment::where('payment_no', 'like', $pattern)->orderByDesc('payment_no')->value('payment_no');
        $sequence = $last ? (int) Str::afterLast($last, '-') + 1 : 1;

        return sprintf('PAY-%s-%04d', $date, $sequence);
    }
}
