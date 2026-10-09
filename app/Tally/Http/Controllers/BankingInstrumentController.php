<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Context\WorkspaceContext;
use Tally\Models\BankAccount;
use Tally\Models\BankInstrument;
use Tally\Models\Company;
use Tally\Models\DepositSlip;
use Tally\Models\DepositSlipLine;
use Tally\Models\FinancialYear;
use Tally\Models\GatewaySettlement;
use Tally\Models\PaymentAdvice;
use Tally\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BankingInstrumentController extends Controller
{
    public function printing(Request $request, WorkspaceContext $context): View
    {
        return $this->cheques($request, $context, 'printing', 'Cheque Printing');
    }

    public function register(Request $request, WorkspaceContext $context): View
    {
        return $this->cheques($request, $context, 'register', 'Cheque Register');
    }

    public function postDated(Request $request, WorkspaceContext $context): View
    {
        return $this->cheques($request, $context, 'post-dated', 'Post-Dated Summary');
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $scope = $this->scope($context);

        if ($scope instanceof View) {
            return back();
        }

        [$company, $branch, $year] = $scope;
        $data = $this->validateCheque($request, $company);
        unset($data['status']);

        BankInstrument::query()->create($data + [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'financial_year_id' => $year->id,
            'status' => 'open',
        ]);

        return back()->with('status', 'Cheque saved.');
    }

    public function update(Request $request, WorkspaceContext $context, BankInstrument $instrument): RedirectResponse
    {
        $this->owns($context, $instrument->company_id);
        $data = $this->validateCheque($request, $context->company(), $instrument->id);
        $instrument->update($data);

        return back()->with('status', 'Cheque updated.');
    }

    public function print(WorkspaceContext $context, BankInstrument $instrument): View
    {
        $this->owns($context, $instrument->company_id);
        $instrument->load(['bankAccount.ledger', 'party']);

        if ($instrument->status === 'open') {
            $instrument->update(['status' => 'printed']);
        }

        return view('tally::banking.cheque-print', ['instrument' => $instrument->fresh(['bankAccount.ledger', 'party'])]);
    }

    public function deposits(WorkspaceContext $context): View
    {
        $scope = $this->scope($context);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $branch, $year] = $scope;

        return view('tally::banking.deposits', [
            'company' => $company,
            'accounts' => $this->accounts($company),
            'cheques' => BankInstrument::query()
                ->where('company_id', $company->id)
                ->where('financial_year_id', $year->id)
                ->whereIn('status', ['open', 'printed'])
                ->orderBy('instrument_date')
                ->get(),
            'slips' => DepositSlip::query()
                ->with(['bankAccount', 'lines.instrument'])
                ->where('company_id', $company->id)
                ->where('financial_year_id', $year->id)
                ->orderByDesc('slip_date')
                ->get(),
            'nextNumber' => $this->nextNumber($company, 'deposit_slips', 'slip_number', 'DS-'),
        ]);
    }

    public function storeDeposit(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $scope = $this->scope($context);

        if ($scope instanceof View) {
            return back();
        }

        [$company, $branch, $year] = $scope;
        $data = $request->validate([
            'bank_account_id' => ['required', Rule::exists('bank_accounts', 'id')->where('company_id', $company->id)],
            'slip_number' => ['required', 'string', 'max:30', Rule::unique('deposit_slips', 'slip_number')->where('company_id', $company->id)],
            'slip_date' => ['required', 'date'],
            'narration' => ['nullable', 'string', 'max:500'],
            'instrument_ids' => ['required', 'array', 'min:1'],
            'instrument_ids.*' => ['integer', Rule::exists('bank_instruments', 'id')->where('company_id', $company->id)],
        ]);

        DB::transaction(function () use ($company, $branch, $year, $data) {
            $instruments = BankInstrument::query()
                ->where('company_id', $company->id)
                ->whereIn('id', $data['instrument_ids'])
                ->whereIn('status', ['open', 'printed'])
                ->get();
            $cents = 0;

            foreach ($instruments as $instrument) {
                $cents += Money::cents((string) $instrument->amount);
            }

            $slip = DepositSlip::query()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'financial_year_id' => $year->id,
                'bank_account_id' => $data['bank_account_id'],
                'slip_number' => $data['slip_number'],
                'slip_date' => $data['slip_date'],
                'amount' => Money::format($cents),
                'narration' => $data['narration'] ?? null,
            ]);

            foreach ($instruments as $instrument) {
                DepositSlipLine::query()->create([
                    'deposit_slip_id' => $slip->id,
                    'bank_instrument_id' => $instrument->id,
                    'amount' => $instrument->amount,
                ]);
                $instrument->update(['status' => 'deposited', 'bank_account_id' => $data['bank_account_id']]);
            }
        });

        return back()->with('status', 'Deposit slip saved.');
    }

    public function advices(WorkspaceContext $context): View
    {
        $scope = $this->scope($context);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, , $year] = $scope;

        return view('tally::banking.advices', [
            'company' => $company,
            'accounts' => $this->accounts($company),
            'parties' => $this->parties($company),
            'advices' => PaymentAdvice::query()
                ->with(['party', 'bankAccount', 'voucher'])
                ->where('company_id', $company->id)
                ->where('financial_year_id', $year->id)
                ->orderByDesc('advice_date')
                ->get(),
            'nextNumber' => $this->nextNumber($company, 'payment_advices', 'advice_number', 'PA-'),
        ]);
    }

    public function storeAdvice(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $scope = $this->scope($context);

        if ($scope instanceof View) {
            return back();
        }

        [$company, $branch, $year] = $scope;
        $request->merge([
            'party_ledger_id' => $request->input('party_ledger_id') ?: null,
            'bank_account_id' => $request->input('bank_account_id') ?: null,
        ]);
        $data = $request->validate([
            'advice_number' => ['required', 'string', 'max:30', Rule::unique('payment_advices', 'advice_number')->where('company_id', $company->id)],
            'advice_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'favouring' => ['nullable', 'string', 'max:160'],
            'party_ledger_id' => ['nullable', Rule::exists('ledgers', 'id')->where('company_id', $company->id)],
            'bank_account_id' => ['nullable', Rule::exists('bank_accounts', 'id')->where('company_id', $company->id)],
            'narration' => ['nullable', 'string', 'max:500'],
        ]);
        unset($data['favouring']);

        PaymentAdvice::query()->create($data + [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'financial_year_id' => $year->id,
        ]);

        return back()->with('status', 'Payment advice saved.');
    }

    public function gateway(WorkspaceContext $context): View
    {
        $scope = $this->scope($context);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, , $year] = $scope;

        return view('tally::banking.gateway', [
            'company' => $company,
            'accounts' => $this->accounts($company),
            'merchants' => $company->merchantProfiles()->orderBy('name')->get(),
            'rows' => GatewaySettlement::query()
                ->with(['merchant', 'bankAccount', 'voucher'])
                ->where('company_id', $company->id)
                ->where('financial_year_id', $year->id)
                ->orderByDesc('settlement_date')
                ->get(),
        ]);
    }

    public function storeGateway(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $scope = $this->scope($context);

        if ($scope instanceof View) {
            return back();
        }

        [$company, $branch, $year] = $scope;
        $request->merge([
            'merchant_profile_id' => $request->input('merchant_profile_id') ?: null,
            'bank_account_id' => $request->input('bank_account_id') ?: null,
            'charges' => $request->input('charges') === '' ? '0' : $request->input('charges'),
        ]);
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:40', Rule::unique('gateway_settlements', 'reference')->where('company_id', $company->id)],
            'settlement_date' => ['required', 'date'],
            'gross_amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'charges' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'merchant_profile_id' => ['nullable', Rule::exists('merchant_profiles', 'id')->where('company_id', $company->id)],
            'bank_account_id' => ['nullable', Rule::exists('bank_accounts', 'id')->where('company_id', $company->id)],
        ]);
        $gross = Money::cents((string) $data['gross_amount']);
        $charges = Money::cents((string) ($data['charges'] ?? '0'));

        GatewaySettlement::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'financial_year_id' => $year->id,
            'merchant_profile_id' => $data['merchant_profile_id'] ?? null,
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'reference' => $data['reference'],
            'settlement_date' => $data['settlement_date'],
            'gross_amount' => Money::format($gross),
            'charges' => Money::format($charges),
            'net_amount' => Money::format($gross - $charges),
            'status' => 'pending',
        ]);

        return back()->with('status', 'Settlement saved.');
    }

    public function reconcileGateway(WorkspaceContext $context, VoucherEngine $engine, GatewaySettlement $settlement): RedirectResponse
    {
        $this->owns($context, $settlement->company_id);
        $settlement->load(['merchant', 'bankAccount', 'voucher', 'branch', 'financialYear']);

        if ($settlement->status === 'reconciled') {
            if ($settlement->voucher && $settlement->voucher->isPosted()) {
                $engine->cancel($settlement->voucher, true);
            }

            $settlement->update(['status' => 'pending', 'voucher_id' => null]);

            return back()->with('status', 'Settlement set back to pending.');
        }

        $bankLedger = $settlement->bankAccount?->ledger_id;
        $settlementLedger = $settlement->merchant?->settlement_ledger_id;
        $gross = Money::cents((string) $settlement->gross_amount);
        $charges = Money::cents((string) $settlement->charges);
        $net = Money::cents((string) $settlement->net_amount);

        if (! $bankLedger || ! $settlementLedger || $gross <= 0 || $net + $charges !== $gross) {
            return back()->with('error', 'This settlement was not posted. Set the merchant settlement ledger and the bank ledger, and keep gross equal to charges plus net.');
        }

        $entries = [
            ['ledger_id' => $bankLedger, 'debit' => Money::format($net), 'credit' => '0.00'],
        ];

        if ($charges > 0) {
            $expense = $context->company()->ledgers()->with('accountGroup.parent')->where('is_active', true)->get()
                ->first(fn ($ledger) => $ledger->belongsToGroup('INDIRECT_EXPENSES'));

            if (! $expense) {
                return back()->with('error', 'This settlement was not posted. Add an indirect expense ledger for the gateway charges.');
            }

            $entries[] = ['ledger_id' => $expense->id, 'debit' => Money::format($charges), 'credit' => '0.00'];
        }

        $entries[] = ['ledger_id' => $settlementLedger, 'debit' => '0.00', 'credit' => Money::format($gross)];
        $voucher = $engine->save(
            $context->company(),
            $settlement->branch,
            $settlement->financialYear,
            request()->user(),
            VoucherType::Receipt,
            [
                'voucher_date' => $settlement->settlement_date->toDateString(),
                'reference_number' => $settlement->reference,
                'narration' => 'Gateway settlement '.$settlement->reference,
                'entries' => $entries,
            ],
            true,
        );
        $settlement->update(['status' => 'reconciled', 'voucher_id' => $voucher->id]);

        return back()->with('status', 'Settlement posted as '.$voucher->voucher_number.'.');
    }

    private function cheques(Request $request, WorkspaceContext $context, string $mode, string $title): View
    {
        $scope = $this->scope($context);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, , $year] = $scope;
        $asOn = now()->toDateString();
        $rows = BankInstrument::query()
            ->with(['bankAccount', 'party', 'voucher'])
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->when($mode === 'printing', fn ($query) => $query->where('status', 'open'))
            ->when($mode === 'post-dated', fn ($query) => $query->whereDate('instrument_date', '>', $asOn)->where('status', '!=', 'cancelled'))
            ->orderBy('instrument_date')
            ->orderBy('number')
            ->get();

        return view('tally::banking.cheques', [
            'title' => $title,
            'mode' => $mode,
            'company' => $company,
            'rows' => $rows,
            'accounts' => $this->accounts($company),
            'parties' => $this->parties($company),
            'nextNumber' => $this->nextNumber($company, 'bank_instruments', 'number', 'CHQ-'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCheque(Request $request, Company $company, ?int $ignoreId = null): array
    {
        $request->merge([
            'bank_account_id' => $request->input('bank_account_id') ?: null,
            'party_ledger_id' => $request->input('party_ledger_id') ?: null,
            'status' => $request->input('status') ?: null,
        ]);

        return $request->validate([
            'number' => ['required', 'string', 'max:30', Rule::unique('bank_instruments', 'number')->where('company_id', $company->id)->ignore($ignoreId)],
            'instrument_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'favouring' => ['required', 'string', 'max:160'],
            'bank_account_id' => ['nullable', Rule::exists('bank_accounts', 'id')->where('company_id', $company->id)],
            'party_ledger_id' => ['nullable', Rule::exists('ledgers', 'id')->where('company_id', $company->id)],
            'narration' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['open', 'printed', 'deposited', 'cancelled'])],
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, BankAccount>
     */
    private function parties(Company $company)
    {
        $parties = $company->ledgers()
            ->whereHas('accountGroup', fn ($query) => $query->whereIn('code', ['CREDITORS', 'DEBTORS']))
            ->orderBy('name')
            ->get();

        return $parties->isNotEmpty() ? $parties : $company->ledgers()->orderBy('name')->get();
    }

    private function accounts(Company $company)
    {
        return BankAccount::query()->with('ledger')->where('company_id', $company->id)->orderBy('bank_name')->get();
    }

    private function nextNumber(Company $company, string $table, string $column, string $prefix): string
    {
        $last = DB::table($table)->where('company_id', $company->id)->where($column, 'like', $prefix.'%')->orderByDesc('id')->value($column);
        $sequence = $last ? ((int) substr((string) $last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{0: Company, 1: Branch, 2: FinancialYear}|View
     */
    private function scope(WorkspaceContext $context): array|View
    {
        if (! $context->company() || ! $context->branch() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', [
                'title' => 'Banking',
                'message' => 'Select a company, branch, and financial year before using banking.',
            ]);
        }

        return [$context->company(), $context->branch(), $context->financialYear()];
    }

    private function owns(WorkspaceContext $context, int $companyId): void
    {
        abort_unless($context->company() && (int) $context->company()->id === $companyId, 404);
    }
}
