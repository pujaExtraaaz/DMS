<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\TaxRateRequest;
use Tally\Models\TaxRate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Tax rates',
                'message' => 'Select or create a company before managing tax rates.',
            ]);
        }

        $search = trim($request->string('q')->toString());
        $rates = $company->taxRates()->with('category')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('tally::tax-rates.index', [
            'company' => $company,
            'rates' => $rates,
            'filters' => ['q' => $search],
        ]);
    }

    public function create(WorkspaceContext $context): View|RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return redirect()->route('books.tally.tax-rates.index');
        }

        return view('tally::tax-rates.create', [
            'company' => $company,
            'rate' => new TaxRate([
                'cgst_rate' => '0',
                'sgst_rate' => '0',
                'igst_rate' => '0',
                'cess_rate' => '0',
                'is_active' => true,
            ]),
            'categories' => $company->taxCategories()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(TaxRateRequest $request, WorkspaceContext $context): RedirectResponse
    {
        $rate = $context->company()->taxRates()->create($request->validated());

        return redirect()->route('books.tally.tax-rates.show', $rate)->with('status', 'Tax rate created.');
    }

    public function show(WorkspaceContext $context, TaxRate $taxRate): View
    {
        return view('tally::tax-rates.show', [
            'company' => $context->company(),
            'rate' => $taxRate->load('category'),
        ]);
    }

    public function edit(WorkspaceContext $context, TaxRate $taxRate): View
    {
        $company = $context->company();

        return view('tally::tax-rates.edit', [
            'company' => $company,
            'rate' => $taxRate,
            'categories' => $company->taxCategories()
                ->where(function ($query) use ($taxRate) {
                    $query->where('is_active', true)->orWhere('id', $taxRate->tax_category_id);
                })
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(TaxRateRequest $request, TaxRate $taxRate): RedirectResponse
    {
        $taxRate->update($request->validated());

        return redirect()->route('books.tally.tax-rates.show', $taxRate)->with('status', 'Tax rate updated.');
    }

    public function updateActivation(Request $request, TaxRate $taxRate): RedirectResponse
    {
        $request->validate(['is_active' => ['required', 'boolean']]);
        $taxRate->update(['is_active' => $request->boolean('is_active')]);

        return back()->with('status', $taxRate->is_active ? 'Tax rate activated.' : 'Tax rate deactivated.');
    }

    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        if (! $taxRate->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($taxRate, 'This tax rate is used by products, HSN/SAC codes, or invoices and cannot be deleted.');
        }

        $taxRate->delete();

        return redirect()->route('books.tally.tax-rates.index')->with('status', 'Tax rate deleted.');
    }
}
