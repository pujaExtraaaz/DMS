<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\AccountNature;
use Tally\Accounting\VoucherStatus;
use Tally\Context\WorkspaceContext;
use Tally\Models\Budget;
use Tally\Models\DeductionSection;
use Tally\Models\Voucher;
use Tally\Reporting\ParityBooks;
use Tally\Tax\GstReturnFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ParityReportController extends Controller
{
    public function gstr1(Request $request, WorkspaceContext $context, ParityBooks $books): View
    {
        return $this->render($request, $context, fn ($company, $from, $to, $branch) => $books->gstr1($company, $from, $to, $branch, $request->integer('gst_registration_id') ?: null), true, 'reports.gstr-1.file');
    }

    public function gstr1File(Request $request, WorkspaceContext $context, GstReturnFile $files): Response
    {
        return $this->jsonFile($request, $context, 'gstr1', fn ($company, $from, $to, $branch) => $files->gstr1($company, $from, $to, $branch, $request->integer('gst_registration_id') ?: null));
    }

    public function gstr3b(Request $request, WorkspaceContext $context, ParityBooks $books): View
    {
        return $this->render($request, $context, fn ($company, $from, $to, $branch) => $books->gstr3b($company, $from, $to, $branch, $request->integer('gst_registration_id') ?: null), true, 'reports.gstr-3b.file');
    }

    public function gstr3bFile(Request $request, WorkspaceContext $context, GstReturnFile $files): Response
    {
        return $this->jsonFile($request, $context, 'gstr3b', fn ($company, $from, $to, $branch) => $files->gstr3b($company, $from, $to, $branch, $request->integer('gst_registration_id') ?: null));
    }

    public function tds(Request $request, WorkspaceContext $context): View
    {
        return $this->render($request, $context, function ($company, $from, $to, $branch) {
            $sections = DeductionSection::query()->where('company_id', $company->id)->get()->keyBy('section_code');
            $rows = [];

            $vouchers = Voucher::query()
                ->with('entries.ledger')
                ->where('company_id', $company->id)
                ->where('status', VoucherStatus::Posted)
                ->whereNotNull('nature_of_payment')
                ->where('nature_of_payment', '!=', '')
                ->when($branch, fn ($query) => $query->where('branch_id', $branch))
                ->whereDate('voucher_date', '>=', $from)
                ->whereDate('voucher_date', '<=', $to)
                ->orderBy('voucher_date')
                ->get();

            foreach ($vouchers as $voucher) {
                $section = $sections->get($voucher->nature_of_payment);
                $base = (float) $voucher->total_debit;
                $rate = $section ? (float) $section->rate : 0;
                $party = $voucher->entries->first(fn ($entry) => (float) $entry->debit > 0)?->ledger?->name ?? '';
                $rows[] = [
                    'Date' => $voucher->voucher_date?->format('d M Y') ?? '',
                    'Voucher' => $voucher->voucher_number,
                    'Section' => $voucher->nature_of_payment,
                    'Party' => $party,
                    'Amount' => number_format($base, 2, '.', ''),
                    'Rate' => $section ? rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.') : '—',
                    'Tax' => number_format(round($base * $rate / 100, 2), 2, '.', ''),
                ];
            }

            return [
                'title' => 'TDS / TCS',
                'sections' => [[
                    'heading' => 'Deduction on payments',
                    'columns' => ['Date', 'Voucher', 'Section', 'Party', 'Amount', 'Rate', 'Tax'],
                    'rows' => $rows,
                ]],
            ];
        });
    }

    public function budgetVariance(Request $request, WorkspaceContext $context): View
    {
        return $this->render($request, $context, function ($company, $from, $to) {
            $rows = [];
            $budgets = Budget::query()->with('ledger.accountGroup')->where('company_id', $company->id)->orderBy('name')->get();

            foreach ($budgets as $budget) {
                $start = $budget->period_start?->toDateString() ?: $from;
                $end = $budget->period_end?->toDateString() ?: $to;
                $ledgerIds = $budget->ledger_id
                    ? [$budget->ledger_id]
                    : ($budget->account_group_id ? $company->ledgers()->where('account_group_id', $budget->account_group_id)->pluck('id')->all() : []);
                $net = 0.0;

                if ($ledgerIds !== []) {
                    $net = (float) DB::table('acct_voucher_entries')
                        ->join('acct_vouchers', 'acct_vouchers.id', '=', 'acct_voucher_entries.voucher_id')
                        ->where('acct_vouchers.company_id', $company->id)
                        ->where('acct_vouchers.status', VoucherStatus::Posted->value)
                        ->whereBetween('acct_vouchers.voucher_date', [$start, $end])
                        ->whereIn('acct_voucher_entries.ledger_id', $ledgerIds)
                        ->selectRaw('COALESCE(SUM(acct_voucher_entries.debit), 0) - COALESCE(SUM(acct_voucher_entries.credit), 0) as net')
                        ->value('net');
                }

                $income = $budget->ledger?->accountGroup?->nature === AccountNature::Income;
                $actual = $income ? -1 * $net : $net;
                $planned = (float) $budget->amount;
                $rows[] = [
                    'Budget' => $budget->name,
                    'Ledger' => $budget->ledger?->name ?? 'Group',
                    'Budget amount' => number_format($planned, 2, '.', ''),
                    'Actual' => number_format($actual, 2, '.', ''),
                    'Variance' => number_format($planned - $actual, 2, '.', ''),
                ];
            }

            return [
                'title' => 'Budget variance',
                'sections' => [[
                    'heading' => 'Budget and actual',
                    'columns' => ['Budget', 'Ledger', 'Budget amount', 'Actual', 'Variance'],
                    'rows' => $rows,
                ]],
            ];
        });
    }

    public function cashFlow(Request $request, WorkspaceContext $context, ParityBooks $books): View
    {
        return $this->render($request, $context, fn ($company, $from, $to, $branch) => $books->cashFlow($company, $from, $to, $branch));
    }

    public function fundsFlow(Request $request, WorkspaceContext $context, ParityBooks $books): View
    {
        return $this->render($request, $context, fn ($company, $from, $to, $branch) => $books->fundsFlow($company, $from, $to, $branch));
    }

    public function accountBooks(Request $request, WorkspaceContext $context, ParityBooks $books): View
    {
        return $this->render($request, $context, fn ($company, $from, $to, $branch) => $books->accountBooks($company, $from, $to, $branch));
    }

    public function statementsOfAccounts(Request $request, WorkspaceContext $context, ParityBooks $books): View
    {
        return $this->render($request, $context, fn ($company, $from, $to, $branch) => $books->statementsOfAccounts($company, $from, $to, $branch));
    }

    public function inventoryBooks(Request $request, WorkspaceContext $context, ParityBooks $books): View
    {
        return $this->render($request, $context, fn ($company, $from, $to, $branch) => $books->inventoryBooks($company, $from, $to, $branch));
    }

    public function statementsOfInventory(Request $request, WorkspaceContext $context, ParityBooks $books): View
    {
        return $this->render($request, $context, fn ($company, $from, $to, $branch) => $books->statementsOfInventory($company, $from, $to, $branch));
    }

    public function exceptions(Request $request, WorkspaceContext $context, ParityBooks $books): View
    {
        return $this->render($request, $context, fn ($company, $from, $to, $branch) => $books->exceptions($company, $branch));
    }

    public function analysis(Request $request, WorkspaceContext $context, ParityBooks $books): View
    {
        return $this->render($request, $context, fn ($company, $from, $to, $branch) => $books->analysis($company, $from, $to, $branch));
    }

    public function editLog(Request $request, WorkspaceContext $context, ParityBooks $books): View
    {
        return $this->render($request, $context, fn ($company, $from, $to) => $books->editLog($company, $from, $to));
    }

    public function payroll(WorkspaceContext $context): View
    {
        return app(PayrollController::class)->index($context);
    }

    /**
     * @param  callable(mixed, string, string, ?int): array<string, mixed>  $build
     */
    private function jsonFile(Request $request, WorkspaceContext $context, string $name, callable $build): Response
    {
        $company = $context->company();
        $year = $context->financialYear();
        abort_unless($company && $year, 404);
        [$periodFrom, $periodTo] = app(\Tally\Context\WorkingCalendar::class)->period($company, $request->user(), $year);
        $from = $request->string('from')->toString() ?: $periodFrom;
        $to = $request->string('to')->toString() ?: $periodTo;
        $payload = $build($company, $from, $to, $context->branch()?->id);

        return response(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="'.$name.'-'.$from.'.json"',
        ]);
    }

    private function render(Request $request, WorkspaceContext $context, callable $build, bool $gst = false, ?string $download = null): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', ['title' => 'Report', 'message' => 'Select a company and financial year.']);
        }

        [$periodFrom, $periodTo] = app(\Tally\Context\WorkingCalendar::class)->period($company, $request->user(), $year);
        $from = $request->string('from')->toString() ?: $periodFrom;
        $to = $request->string('to')->toString() ?: $periodTo;
        $branch = $context->branch()?->id;

        return view('tally::reports.parity', [
            'company' => $company,
            'from' => $from,
            'to' => $to,
            'report' => $build($company, $from, $to, $branch),
            'registrations' => $gst ? $company->gstRegistrations()->orderBy('gstin')->get() : collect(),
            'registrationId' => $request->integer('gst_registration_id') ?: null,
            'download' => $download,
        ]);
    }
}
