<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
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
                $notes = $notes ? ($notes . ' ' . $uomNote) : $uomNote;
            }

            $attrs = [
                'product_id' => $product->id,
                'uom_id' => $effectiveUom->id,
                'warehouse_id' => $warehouseId,
            ];

            $stockLevel = StockLevel::query()
                ->lockForUpdate()
                ->firstOrCreate($attrs, ['quantity' => 0]);

            $newBalance = (float) $stockLevel->quantity + $effectiveQty;

            if ($newBalance < 0 && ! config('dms.allow_negative_stock', true)) {
                $available = (float) $stockLevel->quantity;
                $required = abs($effectiveQty);
                $warehouseLabel = $warehouseId ? " warehouse #{$warehouseId}" : '';

                throw new InvalidArgumentException(
                    "Insufficient stock: {$product->name} ({$effectiveUom->name}){$warehouseLabel}. "
                    ."Available: {$available}, required: {$required}."
                );
            }

            $stockLevel->update(['quantity' => $newBalance]);

            $movement = StockMovement::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $product->id,
                'uom_id' => $effectiveUom->id,
                'type' => $type,
                'quantity' => $effectiveQty,
                'balance_after' => $newBalance,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'notes' => $notes,
                'created_by' => $user?->id,
            ]);

            if ($effectiveQty > 0) {
                $cost = $effectiveCost ?? (float) ($product->purchase_price ?? 0);
                $this->valuationService->addLayer(
                    $product,
                    $effectiveUom,
                    $effectiveQty,
                    $cost,
                    $warehouseId,
                    $reference,
                    $effectiveLandedCost,
                    $batchNo,
                );
            } else {
                $this->valuationService->consume(
                    $product,
                    $effectiveUom,
                    abs($effectiveQty),
                    $warehouseId,
                    $movement,
                    $reference,
                );
            }

            return $movement;
        });
    }
}
