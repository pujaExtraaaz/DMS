<?php

namespace Tally\Http\Requests;

use Tally\Context\WorkspaceContext;
use Tally\Models\Company;
use Tally\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->company() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'symbol' => trim((string) $this->input('symbol')),
            'gst_uqc' => strtoupper(trim((string) $this->input('gst_uqc'))) ?: null,
            'decimal_places' => $this->input('decimal_places'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = $this->company();
        $unit = $this->unit();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($company, $unit): void {
                    if ($this->nameTaken($company, 'units', 'name', (string) $value, $unit?->id)) {
                        $fail('This unit name is already used in the current company.');
                    }
                },
            ],
            'symbol' => [
                'required',
                'string',
                'max:16',
                function (string $attribute, mixed $value, \Closure $fail) use ($company, $unit): void {
                    if ($this->nameTaken($company, 'units', 'symbol', (string) $value, $unit?->id)) {
                        $fail('This unit symbol is already used in the current company.');
                    }
                },
            ],
            'gst_uqc' => ['nullable', 'string', 'max:8', 'regex:/^[A-Z0-9]{1,8}$/'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:4'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function company(): ?Company
    {
        $company = app(WorkspaceContext::class)->company();

        return $company?->is_active ? $company : null;
    }

    public function unit(): ?Unit
    {
        $unit = $this->route('unit');

        return $unit instanceof Unit ? $unit : null;
    }

    private function nameTaken(Company $company, string $table, string $column, string $value, ?int $ignoreId): bool
    {
        if (! preg_match('/^[a-z_]+$/', $table) || ! preg_match('/^[a-z_]+$/', $column)) {
            throw new \InvalidArgumentException('The lookup column is not valid.');
        }

        return \Illuminate\Support\Facades\DB::table($table)
            ->where('company_id', $company->id)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereRaw('lower('.$column.') = ?', [mb_strtolower($value)])
            ->exists();
    }
}
