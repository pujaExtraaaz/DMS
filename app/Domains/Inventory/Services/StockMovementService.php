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
    public function recordIn(
        Product $product,
        Uom $uom,
        float $quantity,
        string $type,
        ?Model $reference = null,
        ?string $notes = null,
        ?User $user = null,
    ): StockMovement {
        return $this->record($product, $uom, abs($quantity), $type, $reference, $notes, $user);
    }

    public function recordOut(
        Product $product,
        Uom $uom,
        float $quantity,
        string $type,
        ?Model $reference = null,
        ?string $notes = null,
        ?User $user = null,
    ): StockMovement {
        return $this->record($product, $uom, -abs($quantity), $type, $reference, $notes, $user);
    }

    public function record(
        Product $product,
        Uom $uom,
        float $quantity,
        string $type,
        ?Model $reference = null,
        ?string $notes = null,
        ?User $user = null,
    ): StockMovement {
        if ($quantity == 0) {
            throw new InvalidArgumentException('Stock movement quantity cannot be zero.');
        }

        return DB::transaction(function () use ($product, $uom, $quantity, $type, $reference, $notes, $user) {
            $stockLevel = StockLevel::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'uom_id' => $uom->id,
                    ],
                    ['quantity' => 0]
                );

            $newBalance = (float) $stockLevel->quantity + $quantity;

            if ($newBalance < 0) {
                throw new InvalidArgumentException(
                    "Insufficient stock for product [{$product->id}] in UOM [{$uom->id}]."
                );
            }

            $stockLevel->update(['quantity' => $newBalance]);

            return StockMovement::create([
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
        });
    }
}
