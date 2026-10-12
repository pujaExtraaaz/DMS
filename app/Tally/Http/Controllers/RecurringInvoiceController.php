<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\PartyDirectory;
use Tally\Invoicing\TradingAccounts;
use Tally\Models\RecurringInvoice;
use Tally\Selling\RecurringInvoiceService;

class RecurringInvoiceController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Recurring invoices', 'message' => 'Select a company first.']);
        }

        return view('tally::recurring-invoices.index', [
            'company' => $company,
            'items' => RecurringInvoice::query()->with('customer')->where('company_id', $company->id)->orderBy('name')->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context, PartyDirectory $parties, TradingAccounts $accounts): View
    {
        $company = $context->company();
        abort_unless($company && $context->branch(), 404);

        return view('tally::recurring-invoices.form', [
            'company' => $company,
            'item' => new RecurringInvoice(['frequency' => 'monthly', 'invoice_category' => 'tax_invoice', 'is_active' => true]),
            'partyLedgers' => $parties->options($company, InvoiceKind::Sales),
            'accountLedgers' => $accounts->options($company, InvoiceKind::Sales),
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);
        $data = $this->validated($request, $company->id);

        $item = RecurringInvoice::query()->create([
            'company_id' => $company->id,
            'branch_id' => $data['branch_id'],
            'name' => $data['name'],
            'customer_ledger_id' => $data['customer_ledger_id'],
            'account_ledger_id' => $data['account_ledger_id'] ?? null,
            'frequency' => $data['frequency'],
            'day_of_month' => $data['day_of_month'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'next_run_on' => $data['next_run_on'] ?? ($data['start_date'] ?? now()->toDateString()),
            'invoice_category' => $data['invoice_category'],
            'narration' => $data['narration'] ?? null,
            'auto_post' => $request->boolean('auto_post'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        foreach (array_values($data['lines'] ?? []) as $i => $line) {
            $item->lines()->create([
                'line_number' => $i + 1,
                'item_name' => $line['item_name'],
                'product_id' => $line['product_id'] ?? null,
                'quantity' => $line['quantity'],
                'rate' => $line['rate'],
                'discount' => $line['discount'] ?? 0,
                'tax_rate' => $line['tax_rate'] ?? 0,
            ]);
        }

        return redirect()->to(tally_route('books.tally.recurring-invoices.index'))
            ->with('status', 'Recurring invoice template saved.');
    }

    public function edit(WorkspaceContext $context, RecurringInvoice $recurringInvoice, PartyDirectory $parties, TradingAccounts $accounts): View
    {
        abort_unless($context->company() && $recurringInvoice->company_id === $context->company()->id, 404);
        $company = $context->company();
        $recurringInvoice->load('lines');

        return view('tally::recurring-invoices.form', [
            'company' => $company,
            'item' => $recurringInvoice,
            'partyLedgers' => $parties->options($company, InvoiceKind::Sales),
            'accountLedgers' => $accounts->options($company, InvoiceKind::Sales),
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, WorkspaceContext $context, RecurringInvoice $recurringInvoice): RedirectResponse
    {
        abort_unless($context->company() && $recurringInvoice->company_id === $context->company()->id, 404);
        $data = $this->validated($request, $context->company()->id);

        $recurringInvoice->update([
            'branch_id' => $data['branch_id'],
            'name' => $data['name'],
            'customer_ledger_id' => $data['customer_ledger_id'],
            'account_ledger_id' => $data['account_ledger_id'] ?? null,
            'frequency' => $data['frequency'],
            'day_of_month' => $data['day_of_month'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'next_run_on' => $data['next_run_on'] ?? null,
            'invoice_category' => $data['invoice_category'],
            'narration' => $data['narration'] ?? null,
            'auto_post' => $request->boolean('auto_post'),
            'is_active' => $request->boolean('is_active', true),
        ]);
        $recurringInvoice->lines()->delete();
        foreach (array_values($data['lines'] ?? []) as $i => $line) {
            $recurringInvoice->lines()->create([
                'line_number' => $i + 1,
                'item_name' => $line['item_name'],
                'product_id' => $line['product_id'] ?? null,
                'quantity' => $line['quantity'],
                'rate' => $line['rate'],
                'discount' => $line['discount'] ?? 0,
                'tax_rate' => $line['tax_rate'] ?? 0,
            ]);
        }

        return redirect()->to(tally_route('books.tally.recurring-invoices.index'))
            ->with('status', 'Recurring invoice updated.');
    }

    public function run(WorkspaceContext $context, RecurringInvoiceService $service): RedirectResponse
    {
        abort_unless($context->company(), 404);
        $created = $service->runDue();

        return back()->with('status', count($created).' recurring invoice(s) created.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $companyId): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'branch_id' => ['required', 'integer', Rule::exists('acct_branches', 'id')->where('company_id', $companyId)],
            'customer_ledger_id' => ['required', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $companyId)],
            'account_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $companyId)],
            'frequency' => ['required', Rule::in(['weekly', 'monthly', 'quarterly', 'yearly'])],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'next_run_on' => ['nullable', 'date'],
            'invoice_category' => ['required', Rule::in(['tax_invoice', 'bill_of_supply'])],
            'narration' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_name' => ['required', 'string', 'max:200'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['required'],
            'lines.*.rate' => ['required'],
            'lines.*.discount' => ['nullable'],
            'lines.*.tax_rate' => ['nullable'],
        ]);
    }
}
