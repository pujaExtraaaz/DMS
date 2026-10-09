<?php

namespace Tally\Http\Requests;

use Tally\Accounting\AccountNature;
use Tally\Http\Requests\Concerns\ResolvesActiveCompany;
use Tally\Models\AccountGroup;
use Tally\Models\Company;
use Tally\Rules\AccountGroupParentIsValid;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountGroupRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = strtoupper(trim((string) $this->input('code')));
        $parentId = $this->filled('parent_id') ? $this->input('parent_id') : null;
        $nature = $this->input('nature');

        if ($parentId && ($company = $this->company())) {
            $parent = AccountGroup::query()
                ->where('company_id', $company->id)
                ->whereKey($parentId)
                ->first();

            if ($parent) {
                $nature = $parent->nature->value;
            }
        }

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => $code === '' ? null : $code,
            'parent_id' => $parentId,
            'nature' => $nature,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = $this->company();

        if (! $company) {
            return [
                'name' => [function (string $attribute, mixed $value, \Closure $fail): void {
                    $fail('Select a company before saving.');
                }],
            ];
        }

        $group = $this->group();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($company, $group): void {
                    $taken = AccountGroup::query()
                        ->where('company_id', $company->id)
                        ->when($group, fn ($query) => $query->whereKeyNot($group->id))
                        ->whereRaw('lower(name) = ?', [mb_strtolower((string) $value)])
                        ->exists();

                    if ($taken) {
                        $fail('This group name is already used in the current company.');
                    }
                },
            ],
            'code' => [
                'nullable',
                'string',
                'max:32',
                'regex:/^[A-Z0-9][A-Z0-9_-]{0,31}$/',
                Rule::unique('account_groups', 'code')
                    ->where(fn ($query) => $query->where('company_id', $company->id))
                    ->ignore($group),
            ],
            'parent_id' => ['nullable', 'integer', new AccountGroupParentIsValid($company, $group)],
            'nature' => ['required_without:parent_id', 'nullable', Rule::enum(AccountNature::class)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'This group name is already used in the current company.',
            'code.unique' => 'This group code is already used in the current company.',
        ];
    }

    public function group(): ?AccountGroup
    {
        $group = $this->route('accountGroup');

        return $group instanceof AccountGroup ? $group : null;
    }
}
