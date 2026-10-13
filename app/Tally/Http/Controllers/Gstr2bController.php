<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Context\WorkspaceContext;
use Tally\Models\Gstr2bRow;
use Tally\Tax\Gstr2bService;

class Gstr2bController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'GSTR-2B',
                'message' => 'Select a company before importing GSTR-2B.',
            ]);
        }

        $period = (string) $request->input('return_period', now()->format('Y-m'));

        return view('tally::tax.gstr2b', [
            'company' => $company,
            'returnPeriod' => $period,
            'rows' => Gstr2bRow::query()
                ->with('matchedInvoice')
                ->where('company_id', $company->id)
                ->when($period !== '', fn ($q) => $q->where('return_period', $period))
                ->orderByDesc('invoice_date')
                ->paginate(50),
        ]);
    }

    public function import(Request $request, WorkspaceContext $context, Gstr2bService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'return_period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.supplier_gstin' => ['nullable', 'string', 'max:15'],
            'rows.*.supplier_name' => ['nullable', 'string', 'max:160'],
            'rows.*.invoice_number' => ['required', 'string', 'max:80'],
            'rows.*.invoice_date' => ['nullable', 'date'],
            'rows.*.taxable_value' => ['nullable', 'numeric'],
            'rows.*.igst' => ['nullable', 'numeric'],
            'rows.*.cgst' => ['nullable', 'numeric'],
            'rows.*.sgst' => ['nullable', 'numeric'],
            'rows.*.cess' => ['nullable', 'numeric'],
        ]);

        $created = $service->import($company, $data['return_period'], $data['rows']);

        return redirect()
            ->to(tally_route('books.tally.tax.gstr2b', ['return_period' => $data['return_period']]))
            ->with('status', count($created).' GSTR-2B row(s) imported.');
    }

    public function match(Request $request, WorkspaceContext $context, Gstr2bService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'return_period' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'tolerance' => ['nullable', 'numeric', 'min:0'],
        ]);

        $stats = $service->match(
            $company,
            $data['return_period'] ?? null,
            (float) ($data['tolerance'] ?? 1.0)
        );

        return back()->with(
            'status',
            "Matched {$stats['matched']}, partial {$stats['partial']}, missing {$stats['missing']}."
        );
    }
}
