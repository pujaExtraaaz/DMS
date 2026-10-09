<?php

namespace Tally\Http\Requests;

use Tally\Context\WorkspaceContext;
use Tally\Models\Company;
use Tally\Tax\TaxComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->company() !== null;
    }

    protected function prepareForValidation(): void
    {
        $cleaned = [];

        foreach (TaxComponent::cases() as $component) {
            $cleaned[$component->value.'_ledger_id'] = $this->input($component->value.'_ledger_id') ?: null;
        }

        $this->merge($cleaned);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = $this->company();
        $rules = [];

        foreach (TaxComponent::cases() as $component) {
            $rules[$component->value.'_ledger_id'] = [
                'nullable',
                'integer',
                'distinct',
                Rule::exists('ledgers', 'id')->where(function ($query) use ($company) {
                    $query->where('company_id', $company->id)->where('is_active', true);
                }),
            ];
        }

        return $rules;
    }

    public function company(): ?Company
    {
        $company = app(WorkspaceContext::class)->company();

        return $company?->is_active ? $company : null;
    }
}
