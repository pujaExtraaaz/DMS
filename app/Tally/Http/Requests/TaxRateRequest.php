<?php

namespace Tally\Http\Requests;

use Tally\Context\WorkspaceContext;
use Tally\Models\Company;
use Tally\Models\TaxRate;
use Tally\Tax\TaxCalculationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class TaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->company() !== null;
    }

    protected function prepareForValidation(): void
    {
        $code = strtoupper(trim((string) $this->input('code')));

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => $code === '' ? null : $code,
            'tax_category_id' => $this->input('tax_category_id') ?: null,
            'cgst_rate' => $this->rate('cgst_rate'),
            'sgst_rate' => $this->rate('sgst_rate'),
            'igst_rate' => $this->rate('igst_rate'),
            'cess_rate' => $this->rate('cess_rate'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = $this->company();
        $rate = $this->route('taxRate');
        $rate = $rate instanceof TaxRate ? $rate : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($company, $rate): void {
                    $taken = DB::table('acct_tax_rates')
                        ->where('company_id', $company->id)
                        ->when($rate, fn ($query) => $query->where('id', '!=', $rate->id))
                        ->whereRaw('lower(name) = ?', [mb_strtolower((string) $value)])
                        ->exists();

                    if ($taken) {
                        $fail('This tax rate name is already used in the current company.');
                    }
                },
            ],
            'code' => [
                'nullable',
                'string',
                'max:32',
                'regex:/^[A-Z0-9][A-Z0-9_-]{0,31}$/',
                Rule::unique('tax_rates', 'code')->where(fn ($query) => $query->where('company_id', $company->id))->ignore($rate),
            ],
            'tax_category_id' => [
                'required',
                'integer',
                Rule::exists('tax_categories', 'id')->where(function ($query) use ($company, $rate) {
                    $query->where('company_id', $company->id)->where(function ($query) use ($rate) {
                        $query->where('is_active', true);

                        if ($rate) {
                            $query->orWhere('id', $rate->tax_category_id);
                        }
                    });
                }),
            ],
            'cgst_rate' => ['required', 'numeric', 'min:0', 'decimal:0,4', 'max:100'],
            'sgst_rate' => ['required', 'numeric', 'min:0', 'decimal:0,4', 'max:100'],
            'igst_rate' => ['required', 'numeric', 'min:0', 'decimal:0,4', 'max:100'],
            'cess_rate' => ['required', 'numeric', 'min:0', 'decimal:0,4', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $calculator = app(TaxCalculationService::class);

            try {
                $cgst = $calculator->percentUnits((string) $this->input('cgst_rate'));
                $sgst = $calculator->percentUnits((string) $this->input('sgst_rate'));
                $igst = $calculator->percentUnits((string) $this->input('igst_rate'));
            } catch (InvalidArgumentException $exception) {
                $validator->errors()->add('cgst_rate', $exception->getMessage());

                return;
            }

            if ($cgst !== $sgst) {
                $validator->errors()->add('sgst_rate', 'SGST must equal CGST.');
            }

            if ($igst !== $cgst + $sgst) {
                $validator->errors()->add('igst_rate', 'IGST must equal CGST + SGST.');
            }
        });
    }

    public function company(): ?Company
    {
        $company = app(WorkspaceContext::class)->company();

        return $company?->is_active ? $company : null;
    }

    private function rate(string $field): string
    {
        $value = trim((string) $this->input($field));

        return $value === '' ? '0' : $value;
    }
}
