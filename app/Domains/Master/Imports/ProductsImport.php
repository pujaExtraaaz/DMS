<?php

namespace App\Domains\Master\Imports;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Sync\Support\SyncGuard;
use App\Support\NameCase;
use App\Support\WarrantyPeriod;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk import Products from CSV/XLSX.
 *
 * Template columns (case-insensitive):
 *   name, part_number, model, product_type, brand, category, sub_category,
 *   uom_code, alternate_uom_code, conversion_factor, supply_type, hsn_code, tax_rate,
 *   track_serial, track_batch,
 *   height_cm, length_cm, width_cm, net_weight, weight_unit,
 *   purchase_price, trade_price, selling_price, mrp, purchase_price_tax_basis,
 *   costing_method, market_valuation_method,
 *   min_stock, reorder_level, specification, warranty, catalog_link, is_active
 *
 * Aliases: sku / part_no / partnumber → part_number; uom → uom_code.
 * Match on part number. When the part number is blank, match on name and model
 * so a second import updates the same product instead of creating another.
 * An existing product is updated only when a value changed; an identical row
 * is skipped. A UOM is used when it already exists and created from the sheet
 * when it does not.
 * Brand, category, sub-category and product type are linked only when they
 * already exist. Unknown names are left blank and are not created.
 */
class ProductsImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /** @var array<string, int> */
    protected array $productsBySku = [];

    /** @var array<string, int> */
    protected array $productsByIdentity = [];

    /** @var array<int, array<string, mixed>> */
    protected array $productRows = [];

    /** @var array<string, Uom> */
    protected array $uomNames = [];

    /** @var array<int, array<string, mixed>> */
    protected array $insertQueue = [];

    /** @var array<string, array<int, array<string, mixed>>> */
    protected array $pendingUoms = [];

    /** @var array<string, array<string, mixed>> */
    protected array $pendingBySku = [];

    /** @var array<int, array<string, mixed>> */
    protected array $updateQueue = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    protected array $updateUoms = [];

    protected int $progressTotal = 0;

    protected int $progressDone = 0;

    protected int $nextSkuSeq = 1;

    protected int $nextSerialSeq = 1;

    protected string $skuPrefix = 'PROD-0-';

    /** @var array<string, Brand> */
    protected array $brandIndex = [];

    /** @var array<string, Category> */
    protected array $categoryByBrand = [];

    /** @var array<string, array<int, Category>> */
    protected array $categoryByName = [];

    /** @var array<string, SubCategory> */
    protected array $subIndex = [];

    /** @var array<string, ProductType> */
    protected array $typeIndex = [];

    public function __construct(
        protected ?int $companyId = null,
        protected ?string $progressId = null,
    ) {}

    public function collection(Collection $rows): void
    {
        if (! SyncGuard::isSyncing()) {
            set_time_limit(0);
            SyncGuard::run(fn () => $this->collection($rows));

            return;
        }

        DB::disableQueryLog();
        $uomsByCode = Uom::query()->get()->keyBy(fn ($u) => strtoupper((string) $u->code));
        foreach ($uomsByCode as $indexedUom) {
            $this->rememberUomName($indexedUom);
        }
        $companyId = $this->companyId ?? (int) Company::query()->value('id');
        $this->bootLookups();
        $this->bootSequences((int) $companyId);
        $this->progressTotal = $rows->count();
        $this->progressDone = 0;
        $this->reportProgress();

        foreach ($rows as $index => $row) {
            try {
            $this->assertNotCancelled();
            $rowNo = $index + 2;
            $data = collect($row)->mapWithKeys(fn ($v, $k) => [strtolower((string) $k) => $v])->toArray();

            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                $this->skipped++;
                $this->errors[] = ['row' => $rowNo, 'field' => 'name', 'message' => 'Name is required'];
                continue;
            }
            $name = \App\Support\NameCase::format($name);

            $uomRaw = trim((string) ($data['uom_code'] ?? $data['uom'] ?? ''));
            $uom = $this->resolveUom($uomsByCode, $uomRaw);
            if (! $uom) {
                $this->skipped++;
                $this->errors[] = ['row' => $rowNo, 'field' => 'uom_code', 'message' => 'UOM code is required'];
                continue;
            }

            $providedSku = trim((string) ($data['part_number'] ?? $data['part_no'] ?? $data['partnumber'] ?? $data['sku'] ?? ''));
            $sheetModel = trim((string) ($data['model'] ?? '')) ?: null;
            $located = $this->locateProduct($providedSku, $name, $sheetModel);
            $sku = $located
                ? ($providedSku !== '' ? $providedSku : (string) ($located['sku'] ?? ''))
                : ($providedSku !== '' ? $providedSku : $this->allocateSku());

            $brand = $this->matchBrand((string) ($data['brand'] ?? ''));
            $category = $this->matchCategory($brand, (string) ($data['category'] ?? ''));
            $subCategory = $this->matchSubCategory($category, (string) ($data['sub_category'] ?? ''));
            $productType = $this->matchProductType((string) ($data['product_type'] ?? ''));

            $altUom = null;
            $altRaw = trim((string) ($data['alternate_uom_code'] ?? ''));
            if ($altRaw !== '') {
                $altUom = $this->resolveUom($uomsByCode, $altRaw);
                if (! $altUom) {
                    $this->skipped++;
                    $this->errors[] = ['row' => $rowNo, 'field' => 'alternate_uom_code', 'message' => 'Alternate UOM code is required'];
                    continue;
                }
            }

            $conversion = filled($data['conversion_factor'] ?? null)
                ? (float) $data['conversion_factor']
                : ($altUom ? 1.0 : 1.0);

            $tracks = [];
            if ($this->isTruthy($data['track_serial'] ?? null)) {
                $tracks[] = 'serial';
            }
            if ($this->isTruthy($data['track_batch'] ?? null)) {
                $tracks[] = 'batch';
            }
            if ($productType?->track_serial && ! in_array('serial', $tracks, true)) {
                $tracks[] = 'serial';
            }
            $trackingType = empty($tracks) ? 'none' : implode(',', $tracks);

            $warrantyText = trim((string) ($data['warranty'] ?? '')) ?: null;
            $warrantyMonths = $warrantyText !== null ? WarrantyPeriod::months($warrantyText) : null;

            $supplyType = strtolower(trim((string) ($data['supply_type'] ?? 'goods')));
            if (! in_array($supplyType, ['capital_goods', 'goods', 'services'], true)) {
                $supplyType = 'goods';
            }

            $taxBasis = strtolower(trim((string) ($data['purchase_price_tax_basis'] ?? 'exclusive')));
            if (! in_array($taxBasis, ['exclusive', 'inclusive'], true)) {
                $taxBasis = 'exclusive';
            }

            $costing = strtolower(trim((string) ($data['costing_method'] ?? 'avg_cost')));
            if (! array_key_exists($costing, Product::COSTING_METHODS)) {
                $costing = 'avg_cost';
            }

            $market = strtolower(trim((string) ($data['market_valuation_method'] ?? 'avg_price')));
            if (! array_key_exists($market, Product::MARKET_VALUATION_METHODS)) {
                $market = 'avg_price';
            }

            $weightUnit = strtoupper(trim((string) ($data['weight_unit'] ?? 'KG')));
            if (! in_array($weightUnit, ['KG', 'GRAMS'], true)) {
                $weightUnit = 'KG';
            }

            $isActive = array_key_exists('is_active', $data) && $data['is_active'] !== null && $data['is_active'] !== ''
                ? $this->isTruthy($data['is_active'])
                : true;

            $payload = [
                'company_id' => $companyId,
                'name' => $name,
                'sku' => $sku,
                'model' => trim((string) ($data['model'] ?? '')) ?: null,
                'product_type_id' => $productType?->id,
                'product_type' => $productType?->name,
                'hsn_code' => trim((string) ($data['hsn_code'] ?? '')) ?: null,
                'base_uom_id' => $uom->id,
                'alternate_uom_id' => $altUom?->id,
                'conversion_factor' => $altUom ? max(0.0001, $conversion) : 1,
                'brand_id' => $brand?->id,
                'category_id' => $category?->id,
                'sub_category_id' => $subCategory?->id,
                'supply_type' => $supplyType,
                'tax_rate' => (float) ($data['tax_rate'] ?? 0),
                'purchase_price' => (float) ($data['purchase_price'] ?? 0),
                'selling_price' => (float) ($data['selling_price'] ?? 0),
                'trade_price' => (float) ($data['trade_price'] ?? $data['selling_price'] ?? 0),
                'calculation_mrp' => (float) ($data['mrp'] ?? 0),
                'purchase_price_tax_basis' => $taxBasis,
                'alt_purchase_price' => filled($data['alt_purchase_price'] ?? null) ? (float) $data['alt_purchase_price'] : null,
                'alt_trade_price' => filled($data['alt_trade_price'] ?? null) ? (float) $data['alt_trade_price'] : null,
                'alt_selling_price' => filled($data['alt_selling_price'] ?? null) ? (float) $data['alt_selling_price'] : null,
                'alt_calculation_mrp' => filled($data['alt_mrp'] ?? null) ? (float) $data['alt_mrp'] : null,
                'costing_method' => $costing,
                'market_valuation_method' => $market,
                'height_cm' => filled($data['height_cm'] ?? null) ? (float) $data['height_cm'] : null,
                'length_cm' => filled($data['length_cm'] ?? null) ? (float) $data['length_cm'] : null,
                'width_cm' => filled($data['width_cm'] ?? null) ? (float) $data['width_cm'] : null,
                'net_weight' => filled($data['net_weight'] ?? null) ? (float) $data['net_weight'] : null,
                'weight_unit' => filled($data['net_weight'] ?? null) || filled($data['weight_unit'] ?? null) ? $weightUnit : null,
                'min_stock' => filled($data['min_stock'] ?? null) ? (float) $data['min_stock'] : 0,
                'reorder_level' => filled($data['reorder_level'] ?? null) ? (float) $data['reorder_level'] : 0,
                'specification' => trim((string) ($data['specification'] ?? '')) ?: null,
                'warranty' => $warrantyText,
                'warranty_months' => $warrantyMonths,
                'catalog_link' => trim((string) ($data['catalog_link'] ?? '')) ?: null,
                'series' => trim((string) ($data['series'] ?? '')) ?: null,
                'color_variant' => trim((string) ($data['color_variant'] ?? '')) ?: null,
                'sender_warranty_months' => filled($data['sender_warranty_months'] ?? null) ? (int) $data['sender_warranty_months'] : null,
                'customer_warranty_months' => filled($data['customer_warranty_months'] ?? null) ? (int) $data['customer_warranty_months'] : null,
                'discount_percent' => (float) ($data['discount_percent'] ?? 0),
                'discount_type' => 'percent',
                'discount_value' => (float) ($data['discount_percent'] ?? 0),
                'selling_discount_type' => 'percent',
                'selling_discount_value' => 0,
                'tracking_type' => $trackingType,
                'is_active' => $isActive,
            ];

            $uomRows = [[
                'uom_id' => $uom->id,
                'conversion_factor' => 1,
                'is_base' => 1,
                'label' => 'Base',
                'selling_price' => $payload['selling_price'],
                'trade_price' => $payload['trade_price'],
                'purchase_price' => $payload['purchase_price'],
                'mrp' => $payload['calculation_mrp'],
                'is_default_sales' => 1,
                'is_active' => 1,
            ]];
            if ($altUom) {
                $uomRows[] = [
                    'uom_id' => $altUom->id,
                    'conversion_factor' => max(0.0001, $conversion),
                    'is_base' => 0,
                    'label' => 'Alternate',
                    'selling_price' => (float) ($data['alt_selling_price'] ?? $payload['selling_price']),
                    'trade_price' => (float) ($data['alt_trade_price'] ?? $payload['trade_price']),
                    'purchase_price' => (float) ($data['alt_purchase_price'] ?? $payload['purchase_price']),
                    'mrp' => (float) ($data['alt_mrp'] ?? $payload['calculation_mrp']),
                    'is_default_sales' => 0,
                    'is_active' => 1,
                ];
            }

            if ($located && ! empty($located['pending'])) {
                if (! $this->arrayChanged($located, $payload)) {
                    $this->skipped++;

                    continue;
                }
                $this->replacePending($located['sku'], $payload, $uomRows, $name);
                $this->updated++;

                continue;
            }

            if ($located) {
                if (! $this->arrayChanged($located, $payload)) {
                    $this->skipped++;

                    continue;
                }
                $this->queueUpdate((int) $located['id'], $payload, $uomRows);
                $this->updated++;

                continue;
            }

            $payload['serial_no'] = $this->allocateSerial();
            $this->queueInsert($payload, $uomRows, $name);
            $this->created++;
            } finally {
                $this->progressDone++;
                if ($this->progressDone === $this->progressTotal || $this->progressDone % 50 === 0) {
                    $this->reportProgress();
                }
            }
        }

        $this->flushInserts();
        $this->flushUpdates();
        $this->reportProgress();
    }

    protected function resolveUom(Collection $uomsByCode, string $raw): ?Uom
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        foreach ($this->uomCandidates($raw) as $candidate) {
            $code = strtoupper($candidate);
            if (isset($uomsByCode[$code])) {
                return $uomsByCode[$code];
            }
        }

        foreach ($this->uomCandidates($raw) as $candidate) {
            $nameKey = strtolower($candidate);
            if (isset($this->uomNames[$nameKey])) {
                return $this->uomNames[$nameKey];
            }
            $formatted = strtolower((string) NameCase::format($candidate));
            if ($formatted !== '' && isset($this->uomNames[$formatted])) {
                return $this->uomNames[$formatted];
            }
        }

        $code = $this->uomCodeFromSheet($raw);
        if ($code === '') {
            return null;
        }
        if (isset($uomsByCode[$code])) {
            return $uomsByCode[$code];
        }

        try {
            $uom = Uom::create([
                'code' => $code,
                'name' => $this->uomNameFromSheet($raw, $code),
                'is_active' => true,
            ]);
        } catch (QueryException) {
            $uom = Uom::query()->whereRaw('UPPER(code) = ?', [$code])->first();
            if (! $uom) {
                return null;
            }
        }

        $uomsByCode->put(strtoupper((string) $uom->code), $uom);
        $this->rememberUomName($uom);

        return $uom;
    }

    protected function rememberUomName(Uom $uom): void
    {
        $stored = strtolower(trim((string) ($uom->getRawOriginal('name') ?? '')));
        if ($stored === '') {
            return;
        }
        $this->uomNames[$stored] ??= $uom;
        $formatted = strtolower((string) NameCase::format($stored));
        if ($formatted !== '') {
            $this->uomNames[$formatted] ??= $uom;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function uomCandidates(string $raw): array
    {
        $raw = trim($raw);
        $parts = [$raw];
        if (preg_match('/^(.+?)\s*\((.+)\)\s*$/', $raw, $matches)) {
            $parts[] = trim($matches[1]);
            $parts[] = trim($matches[2]);
        }

        return array_values(array_unique(array_filter($parts, fn ($part) => $part !== '')));
    }

    protected function uomCodeFromSheet(string $raw): string
    {
        $preferred = [];
        if (preg_match('/^(.+?)\s*\((.+)\)\s*$/', trim($raw), $matches)) {
            $preferred[] = trim($matches[1]);
            $preferred[] = trim($matches[2]);
        }
        $preferred[] = trim($raw);

        foreach ($preferred as $token) {
            $code = strtoupper(preg_replace('/\s+/', ' ', $token) ?? '');
            if ($code !== '' && strlen($code) <= 20) {
                return $code;
            }
        }

        $compact = strtoupper(preg_replace('/[^A-Z0-9\-]/', '', $raw) ?? '');

        return substr($compact, 0, 20);
    }

    protected function uomNameFromSheet(string $raw, string $code): string
    {
        $raw = trim($raw);
        if (preg_match('/^(.+?)\s*\((.+)\)\s*$/', $raw, $matches)) {
            $left = trim($matches[1]);
            $right = trim($matches[2]);
            if (strtoupper($left) === $code && $right !== '') {
                return $right;
            }
            if (strtoupper($right) === $code && $left !== '') {
                return $left;
            }
        }

        return $raw !== '' ? $raw : $code;
    }

    protected function bootLookups(): void
    {
        foreach (Brand::query()->get() as $brand) {
            foreach ($this->nameKeys($this->rawName($brand)) as $key) {
                $this->brandIndex[$key] ??= $brand;
            }
        }

        foreach (Category::query()->get() as $category) {
            foreach ($this->nameKeys($this->rawName($category)) as $key) {
                $this->categoryByBrand[($category->brand_id ?? '0').'|'.$key] ??= $category;
                $this->categoryByName[$key][$category->id] = $category;
            }
        }

        foreach (SubCategory::query()->get() as $subCategory) {
            foreach ($this->nameKeys($this->rawName($subCategory)) as $key) {
                $this->subIndex[$subCategory->category_id.'|'.$key] ??= $subCategory;
            }
        }

        foreach (ProductType::query()->get() as $type) {
            foreach ($this->nameKeys($this->rawName($type)) as $key) {
                $this->typeIndex[$key] ??= $type;
            }
        }
    }

    protected function bootSequences(int $companyId): void
    {
        $this->skuPrefix = 'PROD-'.$companyId.'-';
        $this->nextSkuSeq = $this->maxSuffix('sku', $this->skuPrefix) + 1;
        $this->nextSerialSeq = $this->maxSuffix('serial_no', 'PRD-GLOBAL-') + 1;

        $columns = array_values(array_unique(array_merge(
            ['id', 'sku', 'name', 'model', 'serial_no'],
            $this->comparedColumns(),
        )));
        $loaded = 0;
        foreach (DB::table('products')->whereNull('deleted_at')->select($columns)->cursor() as $row) {
            if (++$loaded % 200 === 0) {
                $this->assertNotCancelled();
            }
            $attrs = (array) $row;
            $id = (int) $row->id;
            $this->productRows[$id] = $attrs;
            $key = strtolower(trim((string) $row->sku));
            if ($key !== '') {
                $this->productsBySku[$key] = $id;
            }
            $this->productsByIdentity[$this->identityKey((string) $row->name, $row->model)] ??= $id;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function locateProduct(string $sku, string $name, ?string $model): ?array
    {
        if ($sku !== '') {
            $found = $this->rowForSku($sku);
            if ($found) {
                return $found;
            }
        }

        $identity = $this->identityKey($name, $model);
        $id = $this->productsByIdentity[$identity] ?? null;
        if ($id === -1) {
            $pendingSku = null;
            foreach ($this->pendingBySku as $pending) {
                if ($this->identityKey((string) $pending['name'], $pending['model'] ?? null) === $identity) {
                    $pendingSku = (string) $pending['sku'];
                    break;
                }
            }
            if ($pendingSku !== null && isset($this->pendingBySku[strtolower($pendingSku)])) {
                return $this->pendingBySku[strtolower($pendingSku)] + ['pending' => true, 'sku' => $pendingSku];
            }
        }
        if (is_int($id) && $id > 0 && isset($this->productRows[$id])) {
            return $this->productRows[$id];
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function rowForSku(string $sku): ?array
    {
        $key = strtolower($sku);
        if (! array_key_exists($key, $this->productsBySku)) {
            return null;
        }
        $id = $this->productsBySku[$key];
        if ($id === -1 && isset($this->pendingBySku[$key])) {
            return $this->pendingBySku[$key] + ['pending' => true, 'sku' => $this->pendingBySku[$key]['sku']];
        }
        if (is_int($id) && $id > 0 && isset($this->productRows[$id])) {
            return $this->productRows[$id];
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $uomRows
     */
    protected function queueInsert(array $payload, array $uomRows, string $name): void
    {
        $payload['is_active'] = ! empty($payload['is_active']) ? 1 : 0;
        $now = now()->toDateTimeString();
        $payload['created_at'] = $now;
        $payload['updated_at'] = $now;
        $skuKey = strtolower((string) $payload['sku']);
        $this->insertQueue[] = $payload;
        $this->pendingBySku[$skuKey] = $payload;
        $this->pendingUoms[$skuKey] = $uomRows;
        $this->productsBySku[$skuKey] = -1;
        $this->productsByIdentity[$this->identityKey($name, $payload['model'] ?? null)] = -1;
        if (count($this->insertQueue) >= 400) {
            $this->flushInserts();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $uomRows
     */
    protected function replacePending(string $sku, array $payload, array $uomRows, string $name): void
    {
        $payload['is_active'] = ! empty($payload['is_active']) ? 1 : 0;
        $skuKey = strtolower($sku);
        $payload['sku'] = $this->pendingBySku[$skuKey]['sku'] ?? $sku;
        $payload['serial_no'] = $this->pendingBySku[$skuKey]['serial_no'] ?? $payload['serial_no'] ?? $this->allocateSerial();
        $payload['created_at'] = $this->pendingBySku[$skuKey]['created_at'] ?? now()->toDateTimeString();
        $payload['updated_at'] = now()->toDateTimeString();
        foreach ($this->insertQueue as $index => $queued) {
            if (strtolower((string) $queued['sku']) === $skuKey) {
                $this->insertQueue[$index] = $payload;
                break;
            }
        }
        $this->pendingBySku[$skuKey] = $payload;
        $this->pendingUoms[$skuKey] = $uomRows;
        $this->productsByIdentity[$this->identityKey($name, $payload['model'] ?? null)] = -1;
    }

    /**
     * @param  array<int, array<string, mixed>>  $uomRows
     */
    protected function queueUpdate(int $id, array $payload, array $uomRows): void
    {
        $existing = $this->productRows[$id] ?? [];
        $payload['id'] = $id;
        $payload['serial_no'] = $existing['serial_no'] ?? ($payload['serial_no'] ?? null);
        $payload['created_at'] = $existing['created_at'] ?? now()->toDateTimeString();
        $payload['is_active'] = ! empty($payload['is_active']) ? 1 : 0;
        $payload['updated_at'] = now()->toDateTimeString();
        $this->updateQueue[$id] = $payload;
        $this->updateUoms[$id] = $uomRows;
        $this->productRows[$id] = array_merge($this->productRows[$id] ?? [], $payload);
        $skuKey = strtolower((string) $payload['sku']);
        if ($skuKey !== '') {
            $this->productsBySku[$skuKey] = $id;
        }
        $this->productsByIdentity[$this->identityKey((string) $payload['name'], $payload['model'] ?? null)] = $id;
        if (count($this->updateQueue) >= 400) {
            $this->flushUpdates();
        }
    }

    protected function flushInserts(): void
    {
        $this->assertNotCancelled();
        if ($this->insertQueue === []) {
            return;
        }

        foreach (array_chunk($this->insertQueue, 200) as $chunk) {
            DB::table('products')->insert($chunk);
        }

        $skus = array_column($this->insertQueue, 'sku');
        $ids = DB::table('products')->whereIn('sku', $skus)->pluck('id', 'sku');
        $now = now()->toDateTimeString();
        $today = now()->toDateString();
        $userId = auth()->id();
        $uomRows = [];
        $histories = [];

        foreach ($this->insertQueue as $payload) {
            $id = (int) ($ids[$payload['sku']] ?? 0);
            if ($id === 0) {
                continue;
            }
            $skuKey = strtolower((string) $payload['sku']);
            $this->productsBySku[$skuKey] = $id;
            $this->productsByIdentity[$this->identityKey((string) $payload['name'], $payload['model'] ?? null)] = $id;
            $this->productRows[$id] = $payload + ['id' => $id];
            unset($this->pendingBySku[$skuKey]);
            foreach ($this->pendingUoms[$skuKey] ?? [] as $uom) {
                $uomRows[] = $uom + [
                    'product_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            unset($this->pendingUoms[$skuKey]);
            $histories[] = [
                'product_id' => $id,
                'uom_id' => $payload['base_uom_id'],
                'trade_price' => $payload['trade_price'],
                'selling_price' => $payload['selling_price'],
                'purchase_price' => $payload['purchase_price'],
                'mrp' => $payload['calculation_mrp'],
                'effective_from' => $today,
                'effective_to' => null,
                'change_reason' => 'created',
                'source' => 'create',
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($uomRows, 400) as $chunk) {
            DB::table('product_uoms')->insert($chunk);
        }
        foreach (array_chunk($histories, 400) as $chunk) {
            DB::table('product_price_histories')->insert($chunk);
        }

        $this->insertQueue = [];
    }

    protected function flushUpdates(): void
    {
        $this->assertNotCancelled();
        if ($this->updateQueue === []) {
            return;
        }

        $rows = array_values($this->updateQueue);
        $updateColumns = array_values(array_diff(array_keys($rows[0]), ['id', 'created_at', 'serial_no']));
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('products')->upsert($chunk, ['id'], $updateColumns);
        }

        $now = now()->toDateTimeString();
        $uomRows = [];
        foreach ($this->updateUoms as $productId => $rowsForProduct) {
            foreach ($rowsForProduct as $uom) {
                $uomRows[] = $uom + [
                    'product_id' => $productId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        if ($uomRows !== []) {
            $uomColumns = array_values(array_diff(array_keys($uomRows[0]), ['product_id', 'uom_id', 'created_at']));
            foreach (array_chunk($uomRows, 400) as $chunk) {
                DB::table('product_uoms')->upsert($chunk, ['product_id', 'uom_id'], $uomColumns);
            }
        }

        $this->updateQueue = [];
        $this->updateUoms = [];
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $payload
     */
    protected function arrayChanged(array $existing, array $payload): bool
    {
        foreach ($payload as $key => $incoming) {
            if (in_array($key, ['created_at', 'updated_at', 'serial_no'], true)) {
                continue;
            }
            if ($this->canonical($key, $existing[$key] ?? null) !== $this->canonical($key, $incoming)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function comparedColumns(): array
    {
        return [
            'company_id', 'name', 'sku', 'model', 'product_type_id', 'product_type', 'hsn_code',
            'base_uom_id', 'alternate_uom_id', 'conversion_factor', 'brand_id', 'category_id', 'sub_category_id',
            'supply_type', 'tax_rate', 'purchase_price', 'selling_price', 'trade_price', 'calculation_mrp',
            'purchase_price_tax_basis', 'alt_purchase_price', 'alt_trade_price', 'alt_selling_price', 'alt_calculation_mrp',
            'costing_method', 'market_valuation_method', 'height_cm', 'length_cm', 'width_cm', 'net_weight', 'weight_unit',
            'min_stock', 'reorder_level', 'specification', 'warranty', 'warranty_months', 'catalog_link', 'series',
            'color_variant', 'sender_warranty_months', 'customer_warranty_months', 'discount_percent', 'discount_type',
            'discount_value', 'selling_discount_type', 'selling_discount_value', 'tracking_type', 'is_active',
        ];
    }

    protected function identityKey(string $name, ?string $model): string
    {
        $name = strtolower((string) NameCase::format(trim($name)));
        $model = strtolower((string) NameCase::format(trim((string) $model)));

        return $name.'|'.$model;
    }

    protected function assertNotCancelled(): void
    {
        if ($this->progressId === null || $this->progressId === '') {
            return;
        }

        $path = Storage::disk('local')->path('imports/progress/'.$this->progressId.'.cancel');
        clearstatcache(true, $path);
        if (is_file($path) || connection_aborted()) {
            throw new ImportCancelledException();
        }
    }

    protected function reportProgress(): void
    {
        if ($this->progressId === null || $this->progressId === '') {
            return;
        }

        $percent = $this->progressTotal === 0
            ? 100
            : (int) floor(($this->progressDone / $this->progressTotal) * 100);

        Storage::disk('local')->put('imports/progress/'.$this->progressId.'.json', json_encode([
            'processed' => $this->progressDone,
            'total' => $this->progressTotal,
            'percent' => min(100, $percent),
            'status' => $this->progressDone >= $this->progressTotal && $this->progressTotal > 0 ? 'done' : 'running',
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
        ]));
    }

    protected function maxSuffix(string $column, string $prefix): int
    {
        $max = 0;
        foreach (DB::table('products')->where($column, 'like', $prefix.'%')->pluck($column) as $value) {
            $suffix = Str::afterLast((string) $value, '-');
            if (ctype_digit($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        return $max;
    }

    protected function allocateSku(): string
    {
        do {
            $sku = sprintf('%s%05d', $this->skuPrefix, $this->nextSkuSeq);
            $this->nextSkuSeq++;
        } while (isset($this->productsBySku[strtolower($sku)]));

        $this->productsBySku[strtolower($sku)] = 0;

        return $sku;
    }

    protected function allocateSerial(): string
    {
        return sprintf('PRD-GLOBAL-%05d', $this->nextSerialSeq++);
    }

    protected function matchBrand(string $label): ?Brand
    {
        return $this->findIndexed($this->brandIndex, $label);
    }

    protected function matchCategory(?Brand $brand, string $label): ?Category
    {
        $label = trim($label);
        if ($label === '') {
            return null;
        }

        if ($brand) {
            foreach ($this->nameKeys($label) as $key) {
                $underBrand = $this->categoryByBrand[$brand->id.'|'.$key] ?? null;
                if ($underBrand) {
                    return $underBrand;
                }
            }

            $unbranded = $this->namedCategories($label, true);

            return count($unbranded) === 1 ? $unbranded[0] : null;
        }

        $named = $this->namedCategories($label, false);

        return count($named) === 1 ? $named[0] : null;
    }

    /**
     * @return array<int, Category>
     */
    protected function namedCategories(string $label, bool $unbrandedOnly): array
    {
        $found = [];
        foreach ($this->nameKeys($label) as $key) {
            foreach ($this->categoryByName[$key] ?? [] as $id => $category) {
                if ($unbrandedOnly && $category->brand_id !== null) {
                    continue;
                }
                $found[$id] = $category;
            }
        }

        return array_values($found);
    }

    protected function matchSubCategory(?Category $category, string $label): ?SubCategory
    {
        if (! $category || trim($label) === '') {
            return null;
        }

        foreach ($this->nameKeys($label) as $key) {
            $match = $this->subIndex[$category->id.'|'.$key] ?? null;
            if ($match) {
                return $match;
            }
        }

        return null;
    }

    protected function matchProductType(string $label): ?ProductType
    {
        return $this->findIndexed($this->typeIndex, $label);
    }

    protected function findIndexed(array $index, string $label): mixed
    {
        foreach ($this->nameKeys($label) as $key) {
            if (array_key_exists($key, $index)) {
                return $index[$key];
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function nameKeys(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return [];
        }

        $formatted = trim((string) NameCase::format($name));

        return array_values(array_unique(array_filter([
            strtolower($name),
            $formatted !== '' ? strtolower($formatted) : null,
        ])));
    }

    protected function rawName(object $model): string
    {
        if (method_exists($model, 'getRawOriginal')) {
            return trim((string) ($model->getRawOriginal('name') ?? ''));
        }

        return trim((string) ($model->name ?? ''));
    }

    protected function payloadChanged(Product $existing, array $payload): bool
    {
        foreach ($payload as $key => $incoming) {
            if ($this->canonical($key, $existing->getAttribute($key)) !== $this->canonical($key, $incoming)) {
                return true;
            }
        }

        return false;
    }

    protected function canonical(string $key, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($this->isNumericField($key) && is_numeric($value)) {
            return number_format((float) $value, 4, '.', '');
        }

        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        if (NameCase::isApplicableField($key)) {
            return NameCase::format($string);
        }

        return $string;
    }

    protected function isNumericField(string $key): bool
    {
        return str_ends_with($key, '_id')
            || in_array($key, [
                'conversion_factor',
                'tax_rate',
                'purchase_price',
                'selling_price',
                'trade_price',
                'calculation_mrp',
                'alt_purchase_price',
                'alt_trade_price',
                'alt_selling_price',
                'alt_calculation_mrp',
                'height_cm',
                'length_cm',
                'width_cm',
                'net_weight',
                'min_stock',
                'reorder_level',
                'discount_percent',
                'discount_value',
                'selling_discount_value',
                'warranty_months',
                'sender_warranty_months',
                'customer_warranty_months',
            ], true);
    }

    protected function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true);
    }
}
