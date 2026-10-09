<?php

namespace Tally\Http\Requests;

use Tally\Context\WorkspaceContext;
use Tally\Models\Company;
use Tally\Models\TaxCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaxCategoryRequest extends FormRequest
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
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = $this->company();
        $category = $this->route('taxCategory');
        $category = $category instanceof TaxCategory ? $category : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($company, $category): void {
                    $taken = DB::table('acct_tax_categories')
                        ->where('company_id', $company->id)
                        ->when($category, fn ($query) => $query->where('id', '!=', $category->id))
                        ->whereRaw('lower(name) = ?', [mb_strtolower((string) $value)])
                        ->exists();

                    if ($taken) {
                        $fail('This tax category name is already used in the current company.');
                    }
                },
            ],
            'code' => [
                'nullable',
                'string',
                'max:32',
                'regex:/^[A-Z0-9][A-Z0-9_-]{0,31}$/',
                Rule::unique('tax_categories', 'code')->where(fn ($query) => $query->where('company_id', $company->id))->ignore($category),
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
