<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockCostLayer;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockMovementService
{
    public function __construct(
        protected ?StockValuationService $valuationService = null,
    ) {
        $this->valuationService ??= app(StockValuationService::class);
    }

    public function recordIn(
        Product $product,
        Uom $uom,
        float $quantity,
        string $type,
        ?Model $reference = null,
        ?string $notes = null,
        ?User $user = null,
        ?int $warehouseId = null,
        ?float $unitCost = null,
        ?float $landedUnitCost = null,
        ?string $batchNo = null,
    ): StockMovement {
        return $this->record(
            $product,
            $uom,
            abs($quantity),
            $type,
            $reference,
            $notes,
            $user,
            $warehouseId,
            $unitCost,
            $landedUnitCost,
            $batchNo,
        );
    }

    public function recordOut(
        Product $product,
        Uom $uom,
        float $quantity,
        string $type,
        ?Model $reference = null,
        ?string $notes = null,
        ?User $user = null,
        ?int $warehouseId = null,
    ): StockMovement {
        return $this->record($product, $uom, -abs($quantity), $type, $reference, $notes, $user, $warehouseId);
    }

    public function record(
        Product $product,
        Uom $uom,
        float $quantity,
        string $type,
        ?Model $reference = null,
        ?string $notes = null,
        ?User $user = null,
        ?int $warehouseId = null,
        ?float $unitCost = null,
        ?float $landedUnitCost = null,
        ?string $batchNo = null,
    ): StockMovement {
        if ($quantity == 0) {
            throw new InvalidArgumentException('Stock movement quantity cannot be zero.');
        }

        return DB::transaction(function () use ($product, $uom, $quantity, $type, $reference, $notes, $user, $warehouseId, $unitCost, $landedUnitCost, $batchNo) {
            $product = Product::query()->findOrFail($product->id);
            [$effectiveUom, $effectiveQty, $effectiveCost, $effectiveLandedCost, $notes] = $this->convertToBase(
                $product,
                $uom,
                $quantity,
                $unitCost,
                $landedUnitCost,
                $notes,
            );

            if ($product->usesBehaviour('ignore_physical_count_diff') && $this->isPhysicalCount($type, $reference)) {
                return $this->ignorePhysicalCount($product, $effectiveUom, $effectiveQty, $type, $reference, $notes, $user, $warehouseId);
            }

            if ($effectiveQty < 0 && $type === 'sale' && $product->usesBehaviour('treat_sales_as_new_manufacture')) {
                $made = $this->postMovement(
                    $product,
                    $effectiveUom,
                    abs($effectiveQty),
                    'manufacture',
                    $reference,
                    'Manufactured on sale'.($notes ? " ({$notes})" : ''),
                    $user,
                    $warehouseId,
                    $this->manufactureCost($product),
                    null,
                    $batchNo,
                );
                $sold = $this->postMovement(
                    $product,
                    $effectiveUom,
                    $effectiveQty,
                    'sale',
                    $reference,
                    $notes,
                    $user,
                    $warehouseId,
                    null,
                    null,
                    $batchNo,
                    $made['layer']?->id,
                );
                $this->rememberLastSalePrice($product, $reference);

                return $sold['movement'];
            }

            $posted = $this->postMovement(
                $product,
                $effectiveUom,
                $effectiveQty,
                $type,
                $reference,
                $notes,
                $user,
                $warehouseId,
                $effectiveCost,
                $effectiveLandedCost,
                $batchNo,
            );

            $autoOut = null;
            if ($effectiveQty > 0 && in_array($type, ['purchase', 'inward', 'purchase_invoice'], true) && $product->usesBehaviour('treat_purchases_as_consumed')) {
                $autoOut = ['consumption', 'Automatically consumed on purchase'];
            } elseif ($effectiveQty > 0 && in_array($type, ['return', 'rejection_in'], true) && $product->usesBehaviour('treat_rejections_inward_as_scrap')) {
                $autoOut = ['scrap', 'Rejection inward treated as scrap'];
            }

            if ($autoOut) {
                $this->postMovement(
                    $product,
                    $effectiveUom,
                    -abs($effectiveQty),
                    $autoOut[0],
                    $reference,
                    $autoOut[1].($notes ? " ({$notes})" : ''),
                    $user,
                    $warehouseId,
                    null,
                    null,
                    $batchNo,
                    $posted['layer']?->id,
                );
            }

            if ($effectiveQty < 0 && $type === 'sale') {
                $this->rememberLastSalePrice($product, $reference);
            }

            return $posted['movement'];
        });
    }

    /**
     * @return array{0: Uom, 1: float, 2: ?float, 3: ?float, 4: ?string}
     */
    private function convertToBase(Product $product, Uom $uom, float $quantity, ?float $unitCost, ?float $landedUnitCost, ?string $notes): array
    {
        $baseUom = $product->baseUom ?? ($product->base_uom_id ? Uom::find($product->base_uom_id) : null);
        $effectiveUom = $baseUom ?? $uom;
        $effectiveQty = $quantity;
        $effectiveCost = $unitCost;
        $effectiveLandedCost = $landedUnitCost;

        if ($baseUom && (int) $uom->id !== (int) $baseUom->id) {
            $conversionService = app(\App\Domains\Master\Services\UomConversionService::class);
            $factor = $conversionService->getConversionFactor($product, $uom);
            $effectiveQty = $quantity * $factor;
            if ($unitCost !== null && $factor > 0) {
                $effectiveCost = $unitCost / $factor;
            }
            if ($landedUnitCost !== null && $factor > 0) {
                $effectiveLandedCost = $landedUnitCost / $factor;
            }
            $uomCode = $uom->code ?? $uom->name;
            $baseCode = $baseUom->code ?? $baseUom->name;
            $uomNote = "[{$quantity} {$uomCode} = {$effectiveQty} {$baseCode}]";
            $notes = $notes ? ($notes.' '.$uomNote) : $uomNote;
        }

        return [$effectiveUom, $effectiveQty, $effectiveCost, $effectiveLandedCost, $notes];
    }

    /**
     * @return array{movement: StockMovement, layer: ?StockCostLayer}
     */
    private function postMovement(
        Product $product,
        Uom $uom,
        float $quantity,
        string $type,
        ?Model $reference,
        ?string $notes,
        ?User $user,
        ?int $warehouseId,
        ?float $unitCost,
        ?float $landedUnitCost,
        ?string $batchNo,
        ?int $onlyLayerId = null,
    ): array {
        $attrs = [
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'warehouse_id' => $warehouseId,
        ];

        $stockLevel = StockLevel::query()
            ->lockForUpdate()
            ->firstOrCreate($attrs, ['quantity' => 0]);

        $newBalance = (float) $stockLevel->quantity + $quantity;

        if ($newBalance < -0.0001 && ! config('dms.allow_negative_stock', true) && ! $product->usesBehaviour('ignore_negative_balances')) {
            $available = (float) $stockLevel->quantity;
            $required = abs($quantity);
            $warehouseLabel = $warehouseId ? " warehouse #{$warehouseId}" : '';

            throw new InvalidArgumentException(
                "Insufficient stock: {$product->name} ({$uom->name}){$warehouseLabel}. "
                ."Available: {$available}, required: {$required}."
            );
        }

        $stockLevel->update(['quantity' => $newBalance]);

        $movement = StockMovement::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $newBalance,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'notes' => $notes,
            'created_by' => $user?->id,
        ]);

        $layer = null;
        if ($quantity > 0) {
            $cost = $unitCost ?? $product->exclusivePurchasePrice();
            $landed = $landedUnitCost;
            if ($product->costingMethod() === 'at_zero_cost') {
                $cost = 0.0;
                $landed = 0.0;
            }

            $layer = $this->valuationService->addLayer(
                $product,
                $uom,
                $quantity,
                $cost,
                $warehouseId,
                $reference,
                $landed,
                $batchNo,
            );
        } else {
            $this->valuationService->consume(
                $product,
                $uom,
                abs($quantity),
                $warehouseId,
                $movement,
                $reference,
                null,
                $onlyLayerId,
            );
        }

        return ['movement' => $movement, 'layer' => $layer];
    }

    private function ignorePhysicalCount(
        Product $product,
        Uom $uom,
        float $quantity,
        string $type,
        ?Model $reference,
        ?string $notes,
        ?User $user,
        ?int $warehouseId,
    ): StockMovement {
        $balance = (float) StockLevel::query()
            ->where('product_id', $product->id)
            ->where('uom_id', $uom->id)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId), fn ($q) => $q->whereNull('warehouse_id'))
            ->value('quantity');

        $direction = $quantity > 0 ? '+' : '';
        $note = trim(($notes ? $notes.' ' : '')."Ignored difference due to physical counting ({$direction}{$quantity}).");

        return StockMovement::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'type' => $type,
            'quantity' => 0,
            'balance_after' => $balance,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'notes' => $note,
            'created_by' => $user?->id,
        ]);
    }

    private function isPhysicalCount(string $type, ?Model $reference): bool
    {
        return $type === 'adjustment'
            && $reference instanceof StockAdjustment
            && $reference->reason === 'recount';
    }

    private function manufactureCost(Product $product): float
    {
        if ($product->costingMethod() === 'at_zero_cost') {
            return 0.0;
        }

        return $this->valuationService->issueRate($product) ?? $product->exclusivePurchasePrice();
    }

    private function rememberLastSalePrice(Product $product, ?Model $reference): void
    {
        if (! $reference instanceof Invoice) {
            return;
        }

        $price = InvoiceItem::query()
            ->where('invoice_id', $reference->getKey())
            ->where('product_id', $product->id)
            ->orderByDesc('id')
            ->value('unit_price');

        if ($price === null) {
            return;
        }

        $product->forceFill(['last_sale_price' => $price])->save();
    }
}
