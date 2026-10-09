<?php

namespace Tally\Http\Requests;

use Tally\Context\WorkspaceContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        $context = app(WorkspaceContext::class);

        return $context->company() !== null
            && $context->branch() !== null
            && $context->financialYear() !== null;
    }

    protected function prepareForValidation(): void
    {
        $entries = [];

        foreach ($this->input('entries', []) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $entries[] = [
                'ledger_id' => $entry['ledger_id'] ?? null,
                'debit' => $entry['debit'] === '' || $entry['debit'] === null ? '0' : $entry['debit'],
                'credit' => $entry['credit'] === '' || $entry['credit'] === null ? '0' : $entry['credit'],
                'narration' => $entry['narration'] ?? null,
                'reference' => $entry['reference'] ?? null,
                'cost_centre_id' => $entry['cost_centre_id'] ?? null,
            ];
        }

        $allocations = [];

        foreach ($this->input('allocations', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $allocations[] = [
                'bill_id' => $row['bill_id'] ?? null,
                'amount' => $row['amount'] ?? null,
            ];
        }

        $this->merge([
            'narration' => trim((string) $this->input('narration')),
            'reference_number' => trim((string) $this->input('reference_number')),
            'name_on_receipt' => trim((string) $this->input('name_on_receipt')),
            'is_post_dated' => $this->boolean('is_post_dated'),
            'is_optional' => $this->boolean('is_optional'),
            'is_memo' => $this->boolean('is_memo'),
            'voucher_class_id' => $this->input('voucher_class_id') ?: null,
            'nature_of_payment' => trim((string) $this->input('nature_of_payment')),
            'reverses_on' => $this->input('reverses_on') ?: null,
            'entries' => $entries,
            'allocations' => $allocations,
            'action' => $this->input('action') === 'post' ? 'post' : 'draft',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'voucher_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'narration' => ['nullable', 'string', 'max:1000'],
            'name_on_receipt' => ['nullable', 'string', 'max:160'],
            'is_post_dated' => ['nullable', 'boolean'],
            'is_optional' => ['nullable', 'boolean'],
            'is_memo' => ['nullable', 'boolean'],
            'voucher_class_id' => ['nullable', 'integer'],
            'nature_of_payment' => ['nullable', 'string', 'max:160'],
            'reverses_on' => ['nullable', 'date'],
            'action' => ['required', Rule::in(['draft', 'post'])],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.ledger_id' => ['nullable', 'integer'],
            'entries.*.debit' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'entries.*.credit' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'entries.*.narration' => ['nullable', 'string', 'max:500'],
            'entries.*.reference' => ['nullable', 'string', 'max:50'],
            'entries.*.cost_centre_id' => ['nullable', 'integer'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.bill_id' => ['nullable', 'integer'],
            'allocations.*.amount' => ['nullable', 'numeric', 'min:0'],
            'currency_id' => ['nullable', 'integer'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'payment_request_id' => ['nullable', 'integer'],
            'merchant_profile_id' => ['nullable', 'integer'],
        ];
    }
}
