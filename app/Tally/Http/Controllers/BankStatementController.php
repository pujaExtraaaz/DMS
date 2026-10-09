<?php

namespace Tally\Http\Controllers;

use Tally\Banking\BankReconciliationService;
use Tally\Banking\BankStatementImporter;
use Tally\Context\WorkspaceContext;
use Tally\Models\BankAccount;
use Tally\Models\BankStatementLine;
use Tally\Models\VoucherEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankStatementController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Bank Statement', 'message' => 'Select a company.']);
        }

        $accounts = BankAccount::query()->with('ledger')->where('company_id', $company->id)->orderBy('bank_name')->get();
        $accountId = $request->integer('bank_account_id') ?: $accounts->first()?->id;
        $lines = BankStatementLine::query()
            ->with('voucherEntry.voucher')
            ->where('company_id', $company->id)
            ->when($accountId, fn ($query) => $query->where('bank_account_id', $accountId))
            ->orderByDesc('statement_date')
            ->paginate(50)
            ->withQueryString();

        return view('tally::banking.statements', [
            'company' => $company,
            'accounts' => $accounts,
            'accountId' => $accountId,
            'lines' => $lines,
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, BankStatementImporter $importer): RedirectResponse
    {
        $company = $context->company();
        $data = $request->validate([
            'bank_account_id' => ['required', Rule::exists('bank_accounts', 'id')->where('company_id', $company->id)],
            'file' => ['required', 'file', 'max:5120'],
        ]);
        $account = BankAccount::query()->where('company_id', $company->id)->findOrFail($data['bank_account_id']);
        $result = $importer->import($account, $request->file('file'));
        $message = $result['imported'].' line'.($result['imported'] === 1 ? '' : 's').' imported. '.$result['duplicates'].' duplicate'.($result['duplicates'] === 1 ? '' : 's').' skipped.';

        if ($result['errors'] !== []) {
            return back()->with('status', $message)->with('error', implode(' ', array_slice($result['errors'], 0, 5)));
        }

        return back()->with('status', $message);
    }

    public function match(Request $request, WorkspaceContext $context, BankReconciliationService $reconcile, BankStatementLine $line): RedirectResponse
    {
        abort_unless($line->company_id === $context->company()?->id, 404);
        $data = $request->validate([
            'voucher_entry_id' => ['required', 'integer'],
        ]);
        $entry = VoucherEntry::query()->with('voucher.financialYear')->whereKey($data['voucher_entry_id'])->whereHas('voucher', fn ($query) => $query->where('company_id', $line->company_id))->first();

        if (! $entry) {
            return back()->with('error', 'That voucher line is not in this company.');
        }

        $year = $entry->voucher->financialYear;
        $date = $line->statement_date->toDateString();

        if ($date < $year->start_date->toDateString() || $date > $year->end_date->toDateString()) {
            $date = $entry->voucher->voucher_date->toDateString();
        }

        $amount = (float) $line->debit > 0 ? $line->debit : $line->credit;
        $reconcile->reconcile($entry, $request->user(), $year, [
            'bank_amount' => $amount,
            'transaction_date' => $date,
            'reconciliation_date' => $date,
            'reference' => $line->reference ?: $line->transaction_id,
        ]);
        $line->update(['voucher_entry_id' => $entry->id]);

        return back()->with('status', 'Statement line matched and the bank transaction is reconciled.');
    }
}
