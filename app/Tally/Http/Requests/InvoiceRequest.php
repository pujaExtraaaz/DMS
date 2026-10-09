<?php

namespace Tally\Http\Requests;

use Tally\Context\WorkspaceContext;
use Tally\Inventory\Quantity;
use Tally\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
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
                'item_name' => trim((string) ($line['item_name'] ?? '')),
                'product_id' => ($line['product_id'] ?? '') !== '' ? $line['product_id'] : null,
                'godown_id' => ($line['godown_id'] ?? '') !== '' ? $line['godown_id'] : null,
                'tax_rate_id' => ($line['tax_rate_id'] ?? '') !== '' ? $line['tax_rate_id'] : null,
                'hsn_sac_id' => ($line['hsn_sac_id'] ?? '') !== '' ? $line['hsn_sac_id'] : null,
                'quantity' => trim((string) ($line['quantity'] ?? '')),
                'rate' => trim((string) ($line['rate'] ?? '')),
                'discount' => trim((string) ($line['discount'] ?? '')),
                'tax_amount' => trim((string) ($line['tax_amount'] ?? '')),
            ];
        }

        $this->merge([
            'party_ledger_id' => $this->input('party_ledger_id') ?: null,
            'account_ledger_id' => $this->input('account_ledger_id') ?: null,
            'reference_number' => trim((string) $this->input('reference_number')),
            'narration' => trim((string) $this->input('narration')),
            'lines' => $lines,
            'action' => $this->input('action') === 'post' ? 'post' : 'draft',
            'sales_order_id' => $this->input('sales_order_id') ?: null,
            'reverse_charge' => $this->boolean('reverse_charge'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'invoice_date' => ['required', 'date'],
            'party_ledger_id' => ['required', 'integer'],
            'account_ledger_id' => ['required', 'integer'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'narration' => ['nullable', 'string', 'max:1000'],
            'action' => ['required', Rule::in(['draft', 'post'])],
            'sales_order_id' => ['nullable', 'integer'],
            'reverse_charge' => ['nullable', 'boolean'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.item_name' => ['nullable', 'string', 'max:200'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.godown_id' => ['nullable', 'integer'],
            'lines.*.tax_rate_id' => ['nullable', 'integer'],
            'lines.*.hsn_sac_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.rate' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $company = app(WorkspaceContext::class)->company();

            if (! $company) {
                return;
            }

            $ids = collect($this->input('lines', []))->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->unique();
            $products = Product::query()
                ->with('primaryUnit')
                ->where('company_id', $company->id)
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            foreach ($this->input('lines', []) as $index => $line) {
                if (! is_array($line)) {
                    continue;
                }

                $quantity = trim((string) ($line['quantity'] ?? ''));
                $product = $products->get((int) ($line['product_id'] ?? 0));

                if ($quantity === '' || ! $product) {
                    continue;
                }

                $places = (int) ($product->primaryUnit->decimal_places ?? 4);

                if (! Quantity::accepts($quantity, $places)) {
                    $validator->errors()->add(
                        "lines.$index.quantity",
                        'Enter a quantity with up to '.$places.' decimal places for this unit.',
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'invoice_date' => 'invoice date',
            'party_ledger_id' => 'party ledger',
            'account_ledger_id' => 'account ledger',
            'lines.*.item_name' => 'item',
            'lines.*.quantity' => 'quantity',
            'lines.*.rate' => 'rate',
            'lines.*.discount' => 'discount',
            'lines.*.tax_amount' => 'tax',
        ];
    }
}
