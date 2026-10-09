<?php

namespace Tally\Http\Requests;

use Tally\Accounting\OpeningBalanceType;
use Tally\Banking\BankAccountService;
use Tally\Http\Requests\Concerns\ResolvesActiveCompany;
use Tally\Models\Company;
use Tally\Models\Ledger;
use Tally\Tax\GstRegistrationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLedgerRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = strtoupper(trim((string) $this->input('code')));
        $balance = trim((string) $this->input('opening_balance'));

        $cleaned = [
            'name' => trim((string) $this->input('name')),
            'code' => $code === '' ? null : $code,
            'opening_balance' => $balance === '' ? '0' : $balance,
            'opening_balance_type' => $this->input('opening_balance_type') ?: OpeningBalanceType::Debit->value,
            'is_active' => $this->exists('is_active') ? $this->boolean('is_active') : true,
        ];

        if ($this->exists('deduction_section_id')) {
            $section = $this->input('deduction_section_id');
            $cleaned['deduction_section_id'] = $section === '' || $section === null ? null : $section;
        }

        $flow = trim((string) $this->input('cash_flow_class'));
        $cleaned['cash_flow_class'] = in_array($flow, ['Operating', 'Investing', 'Financing'], true) ? $flow : null;

        foreach (['address', 'state', 'phone', 'email', 'gstin', 'gst_registration_type', 'pan', 'credit_limit', 'credit_days', 'bank_name', 'account_number', 'ifsc'] as $field) {
            $value = $this->input($field);

            if (! is_string($value) && ! is_numeric($value)) {
                $cleaned[$field] = null;

                continue;
            }

            $value = trim((string) $value);

            if (in_array($field, ['gstin', 'pan', 'ifsc'], true)) {
                $value = strtoupper((string) preg_replace('/\s+/', '', $value));
            }

            $cleaned[$field] = $value === '' ? null : $value;
        }

        $this->merge($cleaned);
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

        $ledger = $this->ledger();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($company, $ledger): void {
                    $taken = Ledger::query()
                        ->where('company_id', $company->id)
                        ->when($ledger, fn ($query) => $query->whereKeyNot($ledger->id))
                        ->whereRaw('lower(name) = ?', [mb_strtolower((string) $value)])
                        ->exists();

                    if ($taken) {
                        $fail('This ledger name is already used in the current company.');
                    }
                },
            ],
            'code' => [
                'nullable',
                'string',
                'max:32',
                'regex:/^[A-Z0-9][A-Z0-9_-]{0,31}$/',
                Rule::unique('ledgers', 'code')
                    ->where(fn ($query) => $query->where('company_id', $company->id))
                    ->ignore($ledger),
            ],
            'account_group_id' => [
                'required',
                'integer',
                Rule::exists('account_groups', 'id')->where(function ($query) use ($company, $ledger) {
                    $query->where('company_id', $company->id)->where(function ($query) use ($ledger) {
                        $query->where('is_active', true);

                        if ($ledger) {
                            $query->orWhere('id', $ledger->account_group_id);
                        }
                    });
                }),
            ],
            'opening_balance' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
            'opening_balance_type' => ['required', Rule::enum(OpeningBalanceType::class)],
            'address' => ['nullable', 'string', 'max:500'],
            'state' => ['nullable', 'string', 'max:255'],
            'gst_registration_type' => ['nullable', Rule::enum(GstRegistrationType::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'gstin' => [
                'nullable',
                'string',
                'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/',
                Rule::unique('ledgers', 'gstin')
                    ->where(fn ($query) => $query->where('company_id', $company->id))
                    ->ignore($ledger),
            ],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
            'credit_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'deduction_section_id' => [
                'nullable',
                'integer',
                Rule::exists('deduction_sections', 'id')->where(fn ($query) => $query->where('company_id', $company->id)),
            ],
            'cash_flow_class' => ['nullable', Rule::in(['Operating', 'Investing', 'Financing'])],
            'is_active' => ['required', 'boolean'],
            'bank_name' => [$this->bankRule(), 'string', 'max:120'],
            'account_number' => [
                $this->bankRule(),
                'string',
                'max:30',
                'regex:/^[A-Za-z0-9]{6,30}$/',
                Rule::unique('bank_accounts', 'account_number')
                    ->where(fn ($query) => $query->where('company_id', $company->id))
                    ->ignore($ledger?->bankAccount()->value('id')),
            ],
            'ifsc' => [$this->bankRule(), 'string', 'size:11', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
        ];
    }

    private function bankRule(): string
    {
        $company = $this->company();
        $groupId = (int) $this->input('account_group_id');
        $bank = $company && $groupId > 0 && app(BankAccountService::class)->isBankGroup($company, $groupId);

        return $bank ? 'required' : 'nullable';
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'This ledger name is already used in the current company.',
            'code.unique' => 'This ledger code is already used in the current company.',
            'account_group_id.exists' => 'Select an active group from the current company.',
            'opening_balance.min' => 'Opening balance cannot be negative. Choose Debit or Credit instead.',
        ];
    }

    public function ledger(): ?Ledger
    {
        $ledger = $this->route('ledger');

        return $ledger instanceof Ledger ? $ledger : null;
    }
}
