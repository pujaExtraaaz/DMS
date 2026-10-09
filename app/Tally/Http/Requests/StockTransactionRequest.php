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

            $lines[] = [
                'product_id' => ($line['product_id'] ?? '') !== '' ? $line['product_id'] : null,
                'godown_id' => ($line['godown_id'] ?? '') !== '' ? $line['godown_id'] : null,
                'quantity' => trim((string) ($line['quantity'] ?? '')),
                'rate' => trim((string) ($line['rate'] ?? '')),
                'direction' => ($line['direction'] ?? 'increase') === 'decrease' ? 'decrease' : 'increase',
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
        ];
    }

    private function type(): StockTransactionType
    {
        $name = (string) $this->route()?->getName();

        foreach (StockTransactionType::cases() as $type) {
            if (str_starts_with($name, 'stock.'.$type->value.'.')) {
                return $type;
            }
        }

        return StockTransactionType::In;
    }
}
