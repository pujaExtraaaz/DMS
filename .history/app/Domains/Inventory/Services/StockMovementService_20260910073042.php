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
            $attrs = [
                'product_id' => $product->id,
                'uom_id' => $uom->id,
                'warehouse_id' => $warehouseId,
            ];

            $stockLevel = StockLevel::query()
                ->lockForUpdate()
                ->firstOrCreate($attrs, ['quantity' => 0]);

            $newBalance = (float) $stockLevel->quantity + $quantity;

            if ($newBalance < 0) {
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

            if ($quantity > 0) {
                $cost = $unitCost ?? (float) ($product->purchase_price ?? 0);
                $this->valuationService->addLayer(
                    $product,
                    $uom,
                    $quantity,
                    $cost,
                    $warehouseId,
                    $reference,
                    $landedUnitCost,
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
                );
            }

            return $movement;
        });
    }
}
