<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Inventory\Models\ProductBatch;
use App\Domains\Inventory\Models\ProductSerial;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SerialBatchService
{
    /**
     * @param  array<int, string>  $serials
     * @return array<int, ProductSerial>
     */
    public function assignSerials(
        Product $product,
        array $serials,
        ?int $warehouseId,
        ?Model $source = null,
        ?User $actor = null,
    ): array {
        $clean = collect($serials)
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->unique()
            ->values();

        if ($clean->isEmpty()) {
            return [];
        }

        $existing = ProductSerial::query()
            ->with(['product', 'warehouse', 'inwardItem.inward'])
            ->whereIn('serial_number', $clean->all())
            ->get()
            ->keyBy('serial_number');

        if ($existing->isNotEmpty()) {
            $details = $existing->map(function (ProductSerial $serial) {
                $inwardNo = $serial->inwardItem?->inward?->inward_no ?? 'n/a';
                $location = $serial->warehouse?->name ?? 'Unassigned';

                return "{$serial->serial_number} (product: {$serial->product?->name}, status: {$serial->status}, location: {$location}, inward: {$inwardNo})";
            })->implode('; ');

            throw ValidationException::withMessages([
                'serials' => "Duplicate serial(s) blocked: {$details}. Resolve existing serials before posting.",
            ]);
        }

        return DB::transaction(function () use ($clean, $product, $warehouseId, $source) {
            $created = [];
            foreach ($clean as $serialNumber) {
                $created[] = ProductSerial::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'serial_number' => $serialNumber,
                    'status' => 'in_stock',
                    'source_type' => $source?->getMorphClass(),
                    'source_id' => $source?->getKey(),
                    'purchase_inward_item_id' => $source instanceof \App\Domains\Purchasing\Models\PurchaseInwardItem
                        ? $source->id
                        : null,
                ]);
            }

            return $created;
        });
    }

    public function upsertBatch(
        Product $product,
        Uom $uom,
        string $batchNo,
        float $quantity,
        float $unitCost,
        ?int $warehouseId = null,
        ?string $expiryDate = null,
        ?Model $source = null,
        ?float $sellingPrice = null,
        ?float $mrp = null,
    ): ProductBatch {
        return DB::transaction(function () use ($product, $uom, $batchNo, $quantity, $unitCost, $warehouseId, $expiryDate, $source, $sellingPrice, $mrp) {
            $batch = ProductBatch::query()
                ->lockForUpdate()
                ->firstOrNew([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'batch_no' => $batchNo,
                ]);

            $batch->uom_id = $uom->id;
            $batch->quantity = (float) $batch->quantity + $quantity;
            $batch->unit_cost = $unitCost;
            $batch->expiry_date = $expiryDate;
            if ($sellingPrice !== null) {
                $batch->selling_price = $sellingPrice;
            }
            if ($mrp !== null) {
                $batch->mrp = $mrp;
            }
            $batch->source_type = $source?->getMorphClass();
            $batch->source_id = $source?->getKey();
            $batch->save();

            return $batch;
        });
    }

    public function findSerial(string $serialNumber): ?ProductSerial
    {
        return ProductSerial::query()
            ->with(['product', 'warehouse', 'inwardItem.inward', 'source', 'reservedFor'])
            ->where('serial_number', $serialNumber)
            ->first();
    }

    /**
     * @param  array<int, string>  $serialNumbers
     * @return array<int, ProductSerial>
     */
    public function reserveSerials(array $serialNumbers, ?Model $reservationOwner = null, ?string $note = null): array
    {
        return DB::transaction(function () use ($serialNumbers, $reservationOwner, $note) {
            $serials = ProductSerial::query()
                ->whereIn('serial_number', $serialNumbers)
                ->lockForUpdate()
                ->get();

            if ($serials->count() !== count(array_unique($serialNumbers))) {
                throw ValidationException::withMessages([
                    'serials' => 'One or more serial numbers were not found.',
                ]);
            }

            $owner = $reservationOwner ?? auth()->user();
            $note = $note ?: ('Manual reserve by '.($owner?->name ?? 'system'));

            foreach ($serials as $serial) {
                if (! in_array($serial->status, ['in_stock', 'available'], true)) {
                    throw ValidationException::withMessages([
                        'serials' => "Serial {$serial->serial_number} is {$serial->status} and cannot be reserved.",
                    ]);
                }

                $serial->update([
                    'status' => 'reserved',
                    'reserved_for_type' => $owner?->getMorphClass(),
                    'reserved_for_id' => $owner?->getKey(),
                    'reservation_note' => $note,
                ]);
            }

            return $serials->all();
        });
    }

    /**
     * @param  array<int, string>  $serialNumbers
     * @return array<int, ProductSerial>
     */
    public function deliverSerials(array $serialNumbers, ?Model $deliveryRef = null): array
    {
        return DB::transaction(function () use ($serialNumbers, $deliveryRef) {
            $serials = ProductSerial::query()
                ->whereIn('serial_number', $serialNumbers)
                ->lockForUpdate()
                ->get();

            foreach ($serials as $serial) {
                if (! in_array($serial->status, ['reserved', 'in_stock', 'available'], true)) {
                    throw ValidationException::withMessages([
                        'serials' => "Serial {$serial->serial_number} is {$serial->status} and cannot be delivered.",
                    ]);
                }

                $serial->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                    'sold_at' => now(),
                    'source_type' => $deliveryRef?->getMorphClass() ?? $serial->source_type,
                    'source_id' => $deliveryRef?->getKey() ?? $serial->source_id,
                ]);
            }

            return $serials->all();
        });
    }

    /**
     * @param  array<int, string>  $serialNumbers
     * @return array<int, ProductSerial>
     */
    public function returnSerials(array $serialNumbers, ?int $warehouseId = null): array
    {
        return DB::transaction(function () use ($serialNumbers, $warehouseId) {
            $serials = ProductSerial::query()
                ->whereIn('serial_number', $serialNumbers)
                ->lockForUpdate()
                ->get();

            foreach ($serials as $serial) {
                if (! in_array($serial->status, ['delivered', 'sold', 'reserved'], true)) {
                    throw ValidationException::withMessages([
                        'serials' => "Serial {$serial->serial_number} is {$serial->status} and cannot be returned.",
                    ]);
                }

                $serial->update([
                    'status' => 'in_stock',
                    'returned_at' => now(),
                    'sold_at' => null,
                    'delivered_at' => null,
                    'reserved_for_type' => null,
                    'reserved_for_id' => null,
                    'reservation_note' => null,
                    'warehouse_id' => $warehouseId ?? $serial->warehouse_id,
                ]);
            }

            return $serials->all();
        });
    }

    public function releaseReservation(Model $reservationOwner): int
    {
        return ProductSerial::query()
            ->where('reserved_for_type', $reservationOwner->getMorphClass())
            ->where('reserved_for_id', $reservationOwner->getKey())
            ->where('status', 'reserved')
            ->update([
                'status' => 'in_stock',
                'reserved_for_type' => null,
                'reserved_for_id' => null,
                'reservation_note' => null,
            ]);
    }
}
