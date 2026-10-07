<?php

namespace App\Http\Controllers\Banking;

use App\Domains\Banking\Models\BankAccountTransaction;
use App\Domains\Banking\Models\OdAccount;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OdAccountController extends Controller
{
    public function index(Request $request): View
    {
        $company = $this->resolveCompany($request);
        $companyBankAccounts = $company ? $company->getCompanyBankAccounts() : collect();

        $configuredOdAccounts = $company
            ? OdAccount::forCompany($company->id)
                ->with(['creator', 'updater'])
                ->orderBy('status', 'asc')
                ->orderBy('bank_name')
                ->get()
            : collect();

        $selectedAccountNo = $request->input(
            'account_number',
            $configuredOdAccounts->first()?->account_number ?? $companyBankAccounts->first()['account_number'] ?? null
        );

        // Find existing OD config for this account if any
        $activeConfig = $configuredOdAccounts->firstWhere('account_number', $selectedAccountNo);

        // Selected bank details from Company Profile
        $selectedBank = $companyBankAccounts->firstWhere('account_number', $selectedAccountNo);
        if (! $selectedBank && $activeConfig) {
            $selectedBank = [
                'account_number' => $activeConfig->account_number,
                'bank_name' => $activeConfig->bank_name,
                'ifsc' => $activeConfig->ifsc_code,
                'upi_id' => '',
                'label' => "{$activeConfig->bank_name} - {$activeConfig->account_number}",
            ];
        }

        // Recent transactions for the selected account
        $recentTransactions = collect();
        if ($company && $selectedAccountNo) {
            $recentTransactions = BankAccountTransaction::query()
                ->where('company_id', $company->id)
                ->where('account_number', $selectedAccountNo)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();
        }

        return view('banking.od.index', [
            'company' => $company,
            'companyBankAccounts' => $companyBankAccounts,
            'configuredOdAccounts' => $configuredOdAccounts,
            'selectedAccountNo' => $selectedAccountNo,
            'selectedBank' => $selectedBank,
            'activeConfig' => $activeConfig,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        if (! $company) {
            return back()->with('error', 'No active company found for your user.');
        }

        $validated = $request->validate([
            'account_number' => 'required|string|max:64',
            'bank_name' => 'required|string|max:150',
            'ifsc_code' => 'nullable|string|max:30',
            'od_limit' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'interest_calculation_method' => 'required|string|max:50',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string|max:1000',
            'opening_utilized' => 'nullable|numeric|min:0',
        ], [
            'od_limit.min' => 'The OD limit must be greater than or equal to 0.',
            'interest_rate.min' => 'The interest rate must be greater than or equal to 0.',
            'effective_to.after_or_equal' => 'Effective To date must not be before Effective From date.',
        ]);

        // Verify account belongs to company profile
        $companyAccounts = $company->getCompanyBankAccounts()->pluck('account_number')->all();
        if (! in_array($validated['account_number'], $companyAccounts, true)) {
            // Also accept if already in od_accounts for this company
            $existsInOd = OdAccount::forCompany($company->id)
                ->where('account_number', $validated['account_number'])
                ->exists();

            if (! $existsInOd && count($companyAccounts) > 0) {
                throw ValidationException::withMessages([
                    'account_number' => 'The selected bank account does not belong to the current company profile.',
                ]);
            }
        }

        // Prevent duplicate active OD configurations with overlapping effective periods
        $overlapQuery = OdAccount::forCompany($company->id)
            ->where('account_number', $validated['account_number'])
            ->where('status', 'active');

        if ($request->filled('id')) {
            $overlapQuery->where('id', '!=', $request->input('id'));
        }

        $existingActive = $overlapQuery->first();
        if ($validated['status'] === 'active' && $existingActive && ! $request->filled('id')) {
            // Update existing active record instead of creating duplicate
            $odAccount = $existingActive;
            $odAccount->update([
                'bank_name' => $validated['bank_name'],
                'ifsc_code' => $validated['ifsc_code'] ?? null,
                'od_limit' => $validated['od_limit'],
                'interest_rate' => $validated['interest_rate'],
                'interest_calculation_method' => $validated['interest_calculation_method'],
                'effective_from' => $validated['effective_from'],
                'effective_to' => $validated['effective_to'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'updated_by' => $request->user()->id,
            ]);
        } else {
            $odAccount = OdAccount::updateOrCreate(
                [
                    'id' => $request->input('id'),
                    'company_id' => $company->id,
                ],
                [
                    'account_number' => $validated['account_number'],
                    'bank_name' => $validated['bank_name'],
                    'ifsc_code' => $validated['ifsc_code'] ?? null,
                    'od_limit' => $validated['od_limit'],
                    'interest_rate' => $validated['interest_rate'],
                    'interest_calculation_method' => $validated['interest_calculation_method'],
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => $validated['effective_to'] ?? null,
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $request->filled('id') ? ($odAccount->created_by ?? $request->user()->id) : $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]
            );
        }

        // If an opening utilized amount is specified, record an opening balance transaction if not already recorded
        if ($request->filled('opening_utilized') && (float) $request->input('opening_utilized') > 0) {
            $openingAmount = (float) $request->input('opening_utilized');
            $existingOpening = BankAccountTransaction::where('company_id', $company->id)
                ->where('account_number', $validated['account_number'])
                ->where('transaction_type', 'opening_balance')
                ->first();

            if ($existingOpening) {
                $existingOpening->update([
                    'debit' => $openingAmount,
                    'transaction_date' => $validated['effective_from'],
                ]);
            } else {
                BankAccountTransaction::create([
                    'company_id' => $company->id,
                    'account_number' => $validated['account_number'],
                    'od_account_id' => $odAccount->id,
                    'transaction_date' => $validated['effective_from'],
                    'transaction_no' => 'OB-'.now()->format('Ymd'),
                    'description' => 'Initial OD Utilized Opening Balance',
                    'transaction_type' => 'opening_balance',
                    'debit' => $openingAmount,
                    'credit' => 0,
                    'created_by' => $request->user()->id,
                ]);
            }
        }

        return redirect()->route('od.index', ['account_number' => $odAccount->account_number])
            ->with('success', "OD Limit & Interest configuration for {$odAccount->bank_name} ({$odAccount->account_number}) saved successfully.");
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        if (! $company) {
            return back()->with('error', 'No active company found for your user.');
        }

        $validated = $request->validate([
            'account_number' => 'required|string|max:64',
            'transaction_date' => 'required|date',
            'transaction_no' => 'nullable|string|max:80',
            'transaction_type' => 'required|in:debit,credit,withdrawal,deposit,payment,receipt,bank_charges,interest',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ], [
            'amount.min' => 'Transaction amount must be greater than 0.',
        ]);

        $odAccount = OdAccount::forCompany($company->id)
            ->where('account_number', $validated['account_number'])
            ->first();

        // Determine Debit vs Credit
        // In bank passbook & OD accounting:
        // Debits (withdrawals / payments / charges) INCREASE OD utilization.
        // Credits (deposits / receipts) DECREASE OD utilization.
        $isDebit = in_array($validated['transaction_type'], ['debit', 'withdrawal', 'payment', 'bank_charges'], true);
        $debit = $isDebit ? (float) $validated['amount'] : 0.0;
        $credit = ! $isDebit ? (float) $validated['amount'] : 0.0;

        $txnNo = filled($validated['transaction_no'] ?? null)
            ? $validated['transaction_no']
            : 'TXN-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(2)));

        BankAccountTransaction::create([
            'company_id' => $company->id,
            'account_number' => $validated['account_number'],
            'od_account_id' => $odAccount?->id,
            'transaction_date' => $validated['transaction_date'],
            'transaction_no' => $txnNo,
            'description' => $validated['description'],
            'transaction_type' => $validated['transaction_type'],
            'debit' => $debit,
            'credit' => $credit,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', "Bank transaction ({$txnNo}) recorded successfully.");
    }

    public function destroyTransaction(Request $request, BankAccountTransaction $transaction): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        if (! $company || $transaction->company_id !== $company->id) {
            abort(403, 'Unauthorized action on another company transaction.');
        }

        $transaction->delete();

        return back()->with('success', 'Bank transaction deleted successfully.');
    }

    public function bankDetails(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);
        $accountNo = $request->query('account_number');

        if (! $company || ! $accountNo) {
            return response()->json(['error' => 'Account not specified'], 400);
        }

        $bankFromProfile = $company->getCompanyBankAccounts()->firstWhere('account_number', $accountNo);
        $odAccount = OdAccount::forCompany($company->id)
            ->where('account_number', $accountNo)
            ->first();

        return response()->json([
            'account_number' => $accountNo,
            'bank_name' => $odAccount?->bank_name ?? $bankFromProfile['bank_name'] ?? '',
            'ifsc' => $odAccount?->ifsc_code ?? $bankFromProfile['ifsc'] ?? '',
            'has_od' => (bool) $odAccount,
            'od_limit' => (float) ($odAccount?->od_limit ?? 0),
            'interest_rate' => (float) ($odAccount?->interest_rate ?? 0),
            'method' => $odAccount?->interest_calculation_method ?? 'daily_simple',
            'effective_from' => $odAccount?->effective_from?->toDateString() ?? now()->startOfMonth()->toDateString(),
            'effective_to' => $odAccount?->effective_to?->toDateString() ?? '',
            'status' => $odAccount?->status ?? 'active',
            'current_utilized' => $odAccount ? $odAccount->getCurrentUtilization() : 0,
            'available_od' => $odAccount ? $odAccount->getAvailableLimit() : 0,
            'utilization_pct' => $odAccount ? $odAccount->getUtilizationPercentage() : 0,
            'is_exceeded' => $odAccount ? $odAccount->isExceeded() : false,
            'exceeded_amount' => $odAccount ? $odAccount->getExceededAmount() : 0,
        ]);
    }

    protected function resolveCompany(Request $request): ?Company
    {
        $user = $request->user();

        return Company::find($user?->company_id) ?? Company::first();
    }
}

