<?php

namespace Tally\Http\Requests;

use Tally\Context\WorkspaceContext;
use Tally\Models\Company;
use Tally\Models\HsnSac;
use Tally\Tax\HsnKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HsnSacRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->company() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'kind' => $this->input('kind') ?: null,
            'description' => trim((string) $this->input('description')) ?: null,
            'tax_rate_id' => $this->input('tax_rate_id') ?: null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = $this->company();
        $record = $this->route('hsnSac');
        $record = $record instanceof HsnSac ? $record : null;

        return [
            'code' => [
                'required',
                'string',
                'max:16',
                'regex:/^[A-Z0-9]{2,16}$/',
                Rule::unique('hsn_sacs', 'code')->where(fn ($query) => $query->where('company_id', $company->id))->ignore($record),
            ],
            'kind' => ['required', Rule::enum(HsnKind::class)],
            'description' => ['nullable', 'string', 'max:255'],
            'tax_rate_id' => [
                'nullable',
                'integer',
                Rule::exists('tax_rates', 'id')->where(function ($query) use ($company, $record) {
                    $query->where('company_id', $company->id)->where(function ($query) use ($record) {
                        $query->where('is_active', true);

                        if ($record?->tax_rate_id) {
                            $query->orWhere('id', $record->tax_rate_id);
                        }
                    });
                }),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function company(): ?Company
    {
        $company = app(WorkspaceContext::class)->company();

        return $company?->is_active ? $company : null;
    }
}
