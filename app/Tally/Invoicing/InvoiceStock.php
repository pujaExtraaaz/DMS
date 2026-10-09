<?php

namespace Tally\Invoicing;

use Tally\Accounting\Money;
use Tally\Inventory\InventoryValuation;
use Tally\Inventory\Quantity;
use Tally\Inventory\StockMovementService;
use Tally\Inventory\StockMovementType;
use Tally\Models\Godown;
use Tally\Models\Invoice;
use Tally\Models\Product;
use Tally\Models\StockMovement;
use Illuminate\Validation\ValidationException;

class InvoiceStock implements InventoryEffect
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly InventoryValuation $valuation,
    ) {}

    public function apply(Invoice $invoice): void
    {
        $invoice->loadMissing(['lines', 'company', 'branch', 'financialYear']);

        if ($this->movementsFor($invoice, false)->exists()) {
            throw ValidationException::withMessages([
                'lines' => 'Stock has already been posted for this invoice.',
            ]);
        }

        foreach ($invoice->lines as $line) {
            if (! $line->product_id) {
                continue;
            }

            $product = Product::query()->with('primaryUnit')->where('company_id', $invoice->company_id)->whereKey($line->product_id)->first();
            $godown = Godown::query()->where('company_id', $invoice->company_id)->whereKey($line->godown_id)->first();

            if (! $product || ! $product->is_active || ! $godown || ! $godown->is_active) {
                throw ValidationException::withMessages([
                    'lines' => 'Each stock item needs an active product and godown from the current company.',
                ]);
            }

            $places = (int) ($product->primaryUnit->decimal_places ?? 4);

            try {
                $scaled = Quantity::scale((string) $line->quantity, $places);
            } catch (\InvalidArgumentException $exception) {
                throw ValidationException::withMessages([
                    'lines' => $exception->getMessage(),
                ]);
            }

            $out = $invoice->kind->stockLeaves();

            try {
                $this->movements->move(
                    $invoice->company,
                    $invoice->branch,
                    $invoice->financialYear,
                    $product,
                    $godown,
                    $invoice->invoice_date->toDateString(),
                    $out ? -$scaled : $scaled,
                    $this->movementRate($invoice, $line, $product, $scaled),
                    $out ? StockMovementType::Out : StockMovementType::In,
                    $invoice,
                );
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages([
                    'lines' => collect($exception->errors())->flatten()->first() ?? 'Stock could not be posted.',
                ]);
            }
        }
    }

    public function reverse(Invoice $invoice): void
    {
        $movements = $this->movementsFor($invoice, false)->orderBy('id')->get();

        if ($movements->isEmpty()) {
            return;
        }

        if ($this->movementsFor($invoice, true)->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Stock for this invoice has already been reversed.',
            ]);
        }

        foreach ($movements as $movement) {
            $this->movements->reverse($movement);
        }
    }

    private function movementRate(Invoice $invoice, mixed $line, Product $product, int $scaled): string
    {
        if ($invoice->kind->usesCostOfGoods()) {
            return $this->valuation->averageRate($product);
        }

        $cost = Money::cents((string) ($line->taxable_amount ?? '0'));

        if ($cost <= 0) {
            $cost = max(0, Money::cents((string) $line->line_total) - Money::cents((string) $line->tax_amount));
        }

        if ($scaled <= 0 || $cost <= 0) {
            return '0.00';
        }

        return Money::format(intdiv($cost * 10000, $scaled));
    }

    private function movementsFor(Invoice $invoice, bool $reversal)
    {
        return StockMovement::query()
            ->where('reference_type', $invoice->getMorphClass())
            ->where('reference_id', $invoice->id)
            ->where('is_reversal', $reversal);
    }
}
