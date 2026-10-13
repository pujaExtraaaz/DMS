<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\CustomField;

class CustomFieldController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Custom fields',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::custom-fields.index', [
            'company' => $company,
            'fields' => CustomField::query()
                ->where('company_id', $company->id)
                ->orderBy('entity_type')
                ->orderBy('field_key')
                ->paginate(25),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'entity_type' => ['required', 'string', 'max:40'],
            'field_key' => [
                'required', 'string', 'max:60',
                Rule::unique('acct_custom_fields', 'field_key')->where(fn ($q) => $q
                    ->where('company_id', $company->id)
                    ->where('entity_type', $request->input('entity_type'))),
            ],
            'label' => ['required', 'string', 'max:120'],
            'field_type' => ['nullable', Rule::in(['text', 'number', 'date', 'boolean', 'select'])],
            'is_required' => ['nullable', 'boolean'],
        ]);

        CustomField::query()->create([
            'company_id' => $company->id,
            'entity_type' => $data['entity_type'],
            'field_key' => $data['field_key'],
            'label' => $data['label'],
            'field_type' => $data['field_type'] ?? 'text',
            'is_required' => $request->boolean('is_required'),
            'is_active' => true,
        ]);

        return back()->with('status', 'Custom field created.');
    }
}
