<?php

namespace Tally\Http\Requests;

use Tally\Models\Branch;
use Tally\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $cleaned = [
            'name' => trim((string) $this->input('name')),
            'code' => strtoupper(trim((string) $this->input('code'))),
            'country' => trim((string) $this->input('country')),
            'is_active' => $this->boolean('is_active'),
        ];

        foreach (['address', 'city', 'state', 'pincode', 'phone', 'email'] as $field) {
            $value = $this->input($field);
            if (! is_string($value)) {
                $cleaned[$field] = null;

                continue;
            }

            $value = trim($value);
            $cleaned[$field] = $value === '' ? null : $value;
        }

        $this->merge($cleaned);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branch = $this->route('branch');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9][A-Z0-9_-]{0,19}$/',
                Rule::unique('branches', 'code')
                    ->where(fn ($query) => $query->where('company_id', $this->company()->id))
                    ->ignore($branch instanceof Branch ? $branch : null),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:12'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'This branch code is already used in this company.',
            'code.regex' => 'Use letters, numbers, hyphens, or underscores for the branch code.',
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
