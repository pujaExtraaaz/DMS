<?php

namespace Tally\Http\Requests;

use Tally\Context\WorkspaceContext;
use Tally\Inventory\StockTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockTransactionRequest extends FormRequest
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
        $lines = [];

        foreach ($this->input('lines', []) as $line) {
            if (! is_array($line)) {
                continue;
            }

            $direction = ($line['direction'] ?? 'increase') === 'decrease' ? 'decrease' : 'increase';

            if (($line['side'] ?? '') === 'source') {
                $direction = 'decrease';
            }

            if (($line['side'] ?? '') === 'destination') {
                $direction = 'increase';
            }

            $lines[] = [
                'product_id' => ($line['product_id'] ?? '') !== '' ? $line['product_id'] : null,
                'godown_id' => ($line['godown_id'] ?? '') !== '' ? $line['godown_id'] : null,
                'quantity' => trim((string) ($line['quantity'] ?? '')),
                'rate' => trim((string) ($line['rate'] ?? '')),
                'direction' => $direction,
                'batch_number' => trim((string) ($line['batch_number'] ?? '')),
                'manufactured_on' => ($line['manufactured_on'] ?? '') !== '' ? $line['manufactured_on'] : null,
                'expires_on' => ($line['expires_on'] ?? '') !== '' ? $line['expires_on'] : null,
                'serial_number' => trim((string) ($line['serial_number'] ?? '')),
            ];
        }

        $this->merge([
            'narration' => trim((string) $this->input('narration')),
            'source_godown_id' => $this->input('source_godown_id') ?: null,
            'destination_godown_id' => $this->input('destination_godown_id') ?: null,
            'lines' => $lines,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $transfer = $this->type() === StockTransactionType::Transfer;

        return [
            'transaction_date' => ['required', 'date'],
            'narration' => ['nullable', 'string', 'max:1000'],
            'source_godown_id' => [$transfer ? 'required' : 'nullable', 'integer'],
            'destination_godown_id' => [$transfer ? 'required' : 'nullable', 'integer', 'different:source_godown_id'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.godown_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.rate' => ['nullable', 'numeric', 'min:0'],
            'lines.*.direction' => ['nullable', Rule::in(['increase', 'decrease'])],
            'lines.*.batch_number' => ['nullable', 'string', 'max:40'],
            'lines.*.manufactured_on' => ['nullable', 'date'],
            'lines.*.expires_on' => ['nullable', 'date'],
            'lines.*.serial_number' => ['nullable', 'string', 'max:60'],
            'lines.*.side' => ['nullable', Rule::in(['source', 'destination'])],
        ];
    }

    private function type(): StockTransactionType
    {
        $name = (string) $this->route()?->getName();

        foreach (StockTransactionType::cases() as $type) {
            if (preg_match('/(?:^|\.)stock\.'.preg_quote($type->value, '/').'\./', $name) === 1) {
                return $type;
            }
        }

        return StockTransactionType::In;
    }
}
