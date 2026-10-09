<?php

namespace Tally\Http\Requests;

use Tally\Context\WorkspaceContext;
use Tally\Models\Company;
use Tally\Models\ProductGroup;
use Tally\Rules\ProductGroupParentIsValid;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->company() !== null;
    }

    protected function prepareForValidation(): void
    {
        $code = strtoupper(trim((string) $this->input('code')));
        $company = $this->company();

        if ($code === '' && $company) {
            $code = ProductGroup::suggestCode($company, $this->group());
        }

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => $code === '' ? null : $code,
            'parent_id' => $this->filled('parent_id') ? $this->input('parent_id') : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = $this->company();
        $group = $this->group();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($company, $group): void {
                    $taken = DB::table('acct_product_groups')
                        ->where('company_id', $company->id)
                        ->when($group, fn ($query) => $query->where('id', '!=', $group->id))
                        ->whereRaw('lower(name) = ?', [mb_strtolower((string) $value)])
                        ->exists();

                    if ($taken) {
                        $fail('This product group name is already used in the current company.');
                    }
                },
            ],
            'code' => [
                'nullable',
                'string',
                'max:32',
                'regex:/^[A-Z0-9][A-Z0-9_-]{0,31}$/',
                Rule::unique('product_groups', 'code')
                    ->where(fn ($query) => $query->where('company_id', $company->id))
                    ->ignore($group),
            ],
            'parent_id' => ['nullable', 'integer', new ProductGroupParentIsValid($company, $group)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function company(): ?Company
    {
        $company = app(WorkspaceContext::class)->company();

        return $company?->is_active ? $company : null;
    }

    public function group(): ?ProductGroup
    {
        $group = $this->route('productGroup');

        return $group instanceof ProductGroup ? $group : null;
    }
}
