<?php

namespace Tally\Http\Requests;

use Tally\Context\WorkspaceContext;
use Tally\Models\Company;
use Tally\Models\Godown;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GodownRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->company() !== null;
    }

    protected function prepareForValidation(): void
    {
        $code = strtoupper(trim((string) $this->input('code')));
        $address = trim((string) $this->input('address'));

        $parent = $this->input('parent_id');

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => $code === '' ? null : $code,
            'address' => $address === '' ? null : $address,
            'parent_id' => $parent === '' || $parent === null ? null : $parent,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = $this->company();
        $godown = $this->godown();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($company, $godown): void {
                    $taken = DB::table('acct_godowns')
                        ->where('company_id', $company->id)
                        ->when($godown, fn ($query) => $query->where('id', '!=', $godown->id))
                        ->whereRaw('lower(name) = ?', [mb_strtolower((string) $value)])
                        ->exists();

                    if ($taken) {
                        $fail('This godown name is already used in the current company.');
                    }
                },
            ],
            'code' => [
                'nullable',
                'string',
                'max:32',
                'regex:/^[A-Z0-9][A-Z0-9_-]{0,31}$/',
                Rule::unique('godowns', 'code')
                    ->where(fn ($query) => $query->where('company_id', $company->id))
                    ->ignore($godown),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('acct_godowns', 'id')->where(fn ($query) => $query->where('company_id', $company->id)),
                function (string $attribute, mixed $value, \Closure $fail) use ($company, $godown): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $parentId = (int) $value;
                    if ($godown && $parentId === $godown->id) {
                        $fail('A godown cannot sit under itself.');

                        return;
                    }
                    $guard = 0;
                    while ($parentId && $guard < 30) {
                        if ($godown && $parentId === $godown->id) {
                            $fail('A godown cannot sit under one of its own godowns.');

                            return;
                        }
                        $parentId = (int) DB::table('acct_godowns')->where('company_id', $company->id)->where('id', $parentId)->value('parent_id');
                        $guard++;
                    }
                },
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function company(): ?Company
    {
        $company = app(WorkspaceContext::class)->company();

        return $company?->is_active ? $company : null;
    }

    public function godown(): ?Godown
    {
        $godown = $this->route('godown');

        return $godown instanceof Godown ? $godown : null;
    }
}
