<?php

namespace App\Domains\Tally\Services;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Master\Models\ProductUom;
use Illuminate\Support\Facades\DB;
use App\Support\CodeGenerator;
use RuntimeException;

class TallyImportService
{
    public function __construct(
        protected TallySyncMappingService $mappingService
    ) {
    }

    /**
     * Import UOMs received from Tally.
     *
     * Expected item structure:
     * [
     *     'name' => 'BOX',
     *     'original_name' => 'BOX',
     *     'decimal_places' => 0,
     * ]
     */
    public function syncUoms(array $items): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $items,
            &$created,
            &$updated,
            &$skipped
        ) {
            foreach ($items as $item) {
                $name = trim((string) ($item['name'] ?? ''));

                if ($name === '') {
                    $skipped++;
                    continue;
                }

                $originalName = trim(
                    (string) ($item['original_name'] ?? $name)
                );

                $decimalPlaces = isset($item['decimal_places'])
                    ? (int) $item['decimal_places']
                    : 0;

                /*
                 * First try the existing DMS UOM by name.
                 *
                 * We use case-insensitive comparison so:
                 * BOX
                 * box
                 *
                 * are treated as the same UOM.
                 */
                $uom = Uom::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->first();

                if ($uom) {
                    $uom->update([
                        'code' => $uom->code ?: strtoupper($name),
                        'is_active' => true,
                    ]);

                    $updated++;
                } else {
                    $uom = Uom::create([
                        'name' => $name,
                        'code' => strtoupper($name),
                        'is_active' => true,
                    ]);

                    $created++;
                }

                /*
                 * Store the Tally identity against the DMS UOM.
                 *
                 * For UOMs Tally does not expose a GUID in our current
                 * collection response, we use the normalized Tally name
                 * as the external identity.
                 */
                $tallyIdentity = $originalName !== ''
                    ? $originalName
                    : $name;

                $this->mappingService->createOrUpdate(
                    $uom,
                    Uom::class,
                    'uom',
                    $tallyIdentity,
                    $name,
                    'synced'
                );
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => count($items),
        ];
    }

    public function syncProducts(array $items, int $companyId): array
{
    $created = 0;
    $updated = 0;
    $skipped = 0;

    DB::transaction(function () use (
        $items,
        $companyId,
        &$created,
        &$updated,
        &$skipped
    ) {
        foreach ($items as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            $tallyGuid = trim((string) ($item['guid'] ?? ''));
            $baseUnits = trim((string) ($item['base_units'] ?? ''));

            /*
             * Tally GUID is the authoritative identity.
             */
            if ($name === '' || $tallyGuid === '') {
                $skipped++;
                continue;
            }

            /*
             * Resolve the Tally UOM to an existing DMS UOM.
             *
             * UOMs have already been synchronized from Tally,
             * so we should not silently create a new UOM here.
             */
            $uom = null;

            if ($baseUnits !== '') {
                $uom = Uom::query()
                    ->whereRaw(
                        'LOWER(name) = ?',
                        [mb_strtolower($baseUnits)]
                    )
                    ->orWhereRaw(
                        'LOWER(code) = ?',
                        [mb_strtolower($baseUnits)]
                    )
                    ->first();
            }

            /*
             * A Product without its Tally UOM cannot be imported
             * correctly when Tally supplied a BaseUnits value.
             */
            if ($baseUnits !== '' && ! $uom) {
                $skipped++;
                continue;
            }

            /*
             * First look for an existing Tally mapping.
             *
             * This is what makes the import idempotent:
             * importing the same Tally product again must update
             * the same DMS product instead of creating another one.
             */
            $mapping = $this->mappingService->findForTally(
                'stock_item',
                $tallyGuid
            );

            $product = null;

            if ($mapping) {
                $product = Product::query()->find($mapping->entity_id);

                /*
                 * If the mapping points to a deleted/non-existing
                 * DMS product, we'll recreate the product below.
                 */
                if (! $product) {
                    $mapping = null;
                }
            }

            if ($product) {
                $product->update([
                    'company_id' => $companyId,
                    'name' => $name,
                    'base_uom_id' => $uom?->id,
                    'tracking_type' => $product->tracking_type ?: 'none',
                    'is_active' => true,
                ]);

                $updated++;
            } else {
                $product = Product::create([
                    'company_id' => $companyId,
                    'name' => $name,
                    'sku' => CodeGenerator::forProduct($companyId),
                    'serial_no' => CodeGenerator::forProductSerial(),
                    'base_uom_id' => $uom?->id,
                    'tracking_type' => 'none',
                    'is_active' => true,
                ]);

                $created++;
            }

            /*
             * Keep ProductUom synchronized with the Product's
             * Tally BaseUnits.
             */
            if ($uom) {
                ProductUom::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'uom_id' => $uom->id,
                    ],
                    [
                        'conversion_factor' => 1,
                        'is_base' => true,
                        'label' => 'Base',
                        'selling_price' => $product->selling_price ?? 0,
                        'trade_price' => $product->trade_price ?? 0,
                        'purchase_price' => $product->purchase_price ?? 0,
                        'mrp' => $product->calculation_mrp ?? 0,
                        'is_default_sales' => true,
                        'is_active' => true,
                    ]
                );
            }

            /*
             * Store the permanent Tally → DMS relationship.
             */
            $this->mappingService->createOrUpdate(
                $product,
                Product::class,
                'stock_item',
                $tallyGuid,
                $name,
                'synced'
            );
        }
    });

    return [
        'created' => $created,
        'updated' => $updated,
        'skipped' => $skipped,
        'total' => count($items),
    ];
}
    public function syncGodowns(
    array $items,
    int $companyId,
    int $branchId
): array {
    $created = 0;
    $updated = 0;
    $skipped = 0;

    DB::transaction(function () use (
        $items,
        $companyId,
        $branchId,
        &$created,
        &$updated,
        &$skipped
    ) {
        foreach ($items as $item) {
            $name = trim((string) ($item['name'] ?? ''));

            if ($name === '') {
                $skipped++;
                continue;
            }

            /*
             * Tally godowns do not currently expose a GUID in our
             * collection, so use the normalized Tally name as
             * the external identity.
             */
            $tallyGuid = 'godown:' . mb_strtolower($name);

            $mapping = $this->mappingService->findForTally(
                'godown',
                $tallyGuid
            );

            if ($mapping) {
                $warehouse = Warehouse::query()->find(
                    $mapping->entity_id
                );

                if (! $warehouse) {
                    $mapping->delete();
                    $mapping = null;
                }
            }

            /*
             * Existing Tally → DMS mapping.
             * Update the same warehouse instead of creating
             * another one.
             */
            if ($mapping && isset($warehouse)) {
                $warehouse->update([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'name' => $name,
                    'is_active' => true,
                ]);

                $this->mappingService->createOrUpdate(
                    $warehouse,
                    Warehouse::class,
                    'godown',
                    $tallyGuid,
                    $name,
                    'synced'
                );

                $updated++;

                continue;
            }

            /*
             * First import:
             * create the warehouse inside the correct
             * company and branch.
             */
            $warehouse = Warehouse::query()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'name' => $name,
                ],
                [
                    'code' => $this->nextWarehouseCode($companyId),
                    'is_default' => false,
                    'is_active' => true,
                ]
            );

            if ($warehouse->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }

            /*
             * Store permanent Tally → DMS identity mapping.
             */
            $this->mappingService->createOrUpdate(
                $warehouse,
                Warehouse::class,
                'godown',
                $tallyGuid,
                $name,
                'synced'
            );
        }
    });

    return [
        'created' => $created,
        'updated' => $updated,
        'skipped' => $skipped,
        'total' => count($items),
    ];
}
    protected function nextWarehouseCode(int $companyId): string
{
    $prefix = "WH-{$companyId}-";

    $latest = Warehouse::query()
        ->where('company_id', $companyId)
        ->where('code', 'like', $prefix.'%')
        ->orderByDesc('id')
        ->limit(50)
        ->pluck('code')
        ->map(fn ($value) => (int) \Illuminate\Support\Str::afterLast($value, '-'))
        ->max();

    $next = (int) ($latest ?? 0) + 1;

    return sprintf('%s%05d', $prefix, $next);
}}