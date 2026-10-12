<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Tally\Accounting\VoucherStatus;
use Tally\Context\WorkspaceContext;
use Tally\Models\Bill;
use Tally\Models\Ledger;
use Tally\Models\VoucherEntry;
use Tally\Reporting\OutstandingReport;

class SubLedgerController extends Controller
{
    public function index(Request $request, WorkspaceContext $context, OutstandingReport $outstanding): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', [
                'title' => 'Sub-ledgers',
                'message' => 'Select a company and financial year first.',
            ]);
        }

        $kind = $request->string('kind', 'receivables')->toString();
        if (! in_array($kind, ['receivables', 'payables', 'control'], true)) {
            $kind = 'receivables';
        }

        $asOn = $request->string('as_on', now()->toDateString())->toString();
        $branchId = $context->branch()?->id;

        $controlAccounts = Ledger::query()
            ->with(['accountGroup', 'subLedgers' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('is_control_account', true)
            ->orderBy('name')
            ->get();

        if ($kind === 'control') {
            return view('tally::reports.sub-ledgers', [
                'company' => $company,
                'year' => $year,
                'kind' => $kind,
                'asOn' => $asOn,
                'ledgers' => $controlAccounts,
                'controlAccounts' => $controlAccounts,
                'rows' => [],
                'total' => '0.00',
            ]);
        }

        $bucket = $kind === 'receivables'
            ? $outstanding->receivables($company, $asOn, $branchId)
            : $outstanding->payables($company, $asOn, $branchId);

        $ledgers = Ledger::query()
            ->with(['accountGroup', 'parentLedger'])
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where(function ($q) use ($kind) {
                $q->whereHas('accountGroup', function ($q) use ($kind) {
                    if ($kind === 'receivables') {
                        $q->where(function ($q) {
                            $q->where('code', 'DEBTORS')->orWhere('name', 'like', '%Sundry Debtors%');
                        });
                    } else {
                        $q->where(function ($q) {
                            $q->where('code', 'CREDITORS')->orWhere('name', 'like', '%Sundry Creditors%');
                        });
                    }
                })->orWhereHas('parentLedger', fn ($q) => $q->where('is_control_account', true));
            })
            ->orderBy('name')
            ->get();

        return view('tally::reports.sub-ledgers', [
            'company' => $company,
            'year' => $year,
            'kind' => $kind,
            'asOn' => $asOn,
            'ledgers' => $ledgers,
            'controlAccounts' => $controlAccounts,
            'rows' => $bucket['rows'] ?? [],
            'total' => $bucket['total'] ?? '0.00',
        ]);
    }

    public function show(Request $request, WorkspaceContext $context, Ledger $ledger): View
    {
        $company = $context->company();
        $year = $context->financialYear();
        abort_unless($company && $year && $ledger->company_id === $company->id, 404);

        $from = $request->string('from', $year->start_date->toDateString())->toString();
        $to = $request->string('to', $year->end_date->toDateString())->toString();

        $ledgerIds = $ledger->is_control_account
            ? $ledger->subLedgers()->pluck('id')->push($ledger->id)->all()
            : [$ledger->id];

        $entries = VoucherEntry::query()
            ->with(['voucher', 'ledger'])
            ->whereIn('ledger_id', $ledgerIds)
            ->whereHas('voucher', function ($q) use ($company, $year, $from, $to) {
                $q->where('company_id', $company->id)
                    ->where('financial_year_id', $year->id)
                    ->where('status', VoucherStatus::Posted)
                    ->whereDate('voucher_date', '>=', $from)
                    ->whereDate('voucher_date', '<=', $to);
            })
            ->join('acct_vouchers', 'acct_vouchers.id', '=', 'acct_voucher_entries.voucher_id')
            ->orderBy('acct_vouchers.voucher_date')
            ->orderBy('acct_voucher_entries.id')
            ->select('acct_voucher_entries.*')
            ->get();

        $bills = Bill::query()
            ->where('company_id', $company->id)
            ->whereIn('ledger_id', $ledgerIds)
            ->orderByDesc('bill_date')
            ->limit(100)
            ->get();

        return view('tally::reports.sub-ledger-show', [
            'company' => $company,
            'year' => $year,
            'ledger' => $ledger->load(['parentLedger', 'subLedgers']),
            'from' => $from,
            'to' => $to,
            'entries' => $entries,
            'bills' => $bills,
            'isControl' => (bool) $ledger->is_control_account,
        ]);
    }
}
