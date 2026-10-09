<?php

namespace Tally\Http\Requests;

use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Rules\FinancialYearDoesNotOverlap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancialYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $year = $this->route('financialYear') ?? $this->route('financial_year');
        $yearId = $year instanceof FinancialYear ? $year->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:40',
                Rule::unique('financial_years', 'name')
                    ->where(fn ($query) => $query->where('company_id', $this->company()->id))
                    ->ignore($yearId),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
                new FinancialYearDoesNotOverlap($this->company()->id, $yearId),
            ],
            'is_active' => ['required', 'boolean'],
            'opening_profit' => ['nullable', 'numeric', 'decimal:0,2', 'min:-9999999999999.99', 'max:9999999999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'This financial year name is already used in this company.',
            'end_date.after' => 'The end date must be after the start date.',
        ];
    }

    public function company(): Company
    {
        $company = $this->route('company');

        if (! $company instanceof Company) {
            abort(404);
        }

        return $company;
    }
}
