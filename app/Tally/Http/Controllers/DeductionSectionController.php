<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Controllers\Concerns\ActivatesRecords;
use Tally\Models\DeductionSection;
use Tally\Tax\DeductionCalculator;
use Tally\Tax\DeductionKind;
use Tally\Tax\PartyRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeductionSectionController extends Controller
{
    use ActivatesRecords;
    public function index(Request $request, WorkspaceContext $context, DeductionCalculator $calculator): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'TDS / TCS',
                'message' => 'Select or create a company before managing TDS and TCS.',
            ]);
        }

        $preview = null;
        $sectionId = $request->integer('section_id');
        $section = $sectionId ? $company->deductionSections()->whereKey($sectionId)->first() : null;

        if ($section && $request->filled('amount')) {
            $preview = $calculator->calculate(
                (string) $request->input('amount'),
                $section,
                (string) ($request->input('year_to_date') ?: '0'),
            );
        }

        return view('tally::deduction-sections.index', [
            'company' => $company,
            'sections' => $company->deductionSections()->orderBy('section_code')->paginate(25),
            'preview' => $preview,
            'filters' => $request->only(['section_id', 'amount', 'year_to_date']),
        ]);
    }

    public function create(WorkspaceContext $context): View|RedirectResponse
    {
        if (! $context->company()) {
            return redirect()->route('books.tally.deduction-sections.index');
        }

        return view('tally::deduction-sections.form', [
            'company' => $context->company(),
            'section' => new DeductionSection(['kind' => DeductionKind::Tds, 'party_role' => PartyRole::Any, 'is_active' => true, 'threshold_amount' => '0.00', 'rate' => '0']),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $context->company()->deductionSections()->create($this->validated($request, $context));

        return redirect()->route('books.tally.deduction-sections.index')->with('status', 'Section created.');
    }

    public function edit(WorkspaceContext $context, DeductionSection $deductionSection): View
    {
        return view('tally::deduction-sections.form', [
            'company' => $context->company(),
            'section' => $deductionSection,
        ]);
    }

    public function update(Request $request, WorkspaceContext $context, DeductionSection $deductionSection): RedirectResponse
    {
        $deductionSection->update($this->validated($request, $context, $deductionSection));

        return redirect()->route('books.tally.deduction-sections.index')->with('status', 'Section updated.');
    }

    public function updateActivation(Request $request, DeductionSection $deductionSection): RedirectResponse
    {
        return $this->setActive($request, $deductionSection, 'Deduction section');
    }

    public function destroy(DeductionSection $deductionSection): RedirectResponse
    {
        if (! $deductionSection->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($deductionSection, 'This section is assigned to a ledger.');
        }

        $deductionSection->delete();

        return redirect()->route('books.tally.deduction-sections.index')->with('status', 'Section deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, WorkspaceContext $context, ?DeductionSection $section = null): array
    {
        $data = $request->validate([
            'kind' => ['required', Rule::enum(DeductionKind::class)],
            'name' => ['required', 'string', 'max:255'],
            'section_code' => ['required', 'string', 'max:20', Rule::unique('deduction_sections', 'section_code')->where(fn ($query) => $query->where('company_id', $context->company()->id))->ignore($section)],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'threshold_amount' => ['required', 'numeric', 'min:0'],
            'party_role' => ['required', Rule::enum(PartyRole::class)],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
