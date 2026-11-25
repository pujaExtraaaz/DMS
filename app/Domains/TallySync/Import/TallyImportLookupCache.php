<?php

namespace App\Domains\TallySync\Import;

use App\Domains\Master\Models\Product as DmsProduct;
use App\Domains\Organization\Models\Company as OrganizationCompany;
use Illuminate\Support\Facades\DB;
use Tally\Models\AccountGroup;
use Tally\Models\Company;
use Tally\Models\CostCentre;
use Tally\Models\Godown;
use Tally\Models\Ledger;
use Tally\Models\Product;
use Tally\Models\ProductGroup;
use Tally\Models\Unit;

/**
 * In-memory name → id maps for one import run (point 3).
 */
final class TallyImportLookupCache
{
    /** @var array<string, array<string, int>> */
    private array $maps = [];

    /** @var array<string, array{0:?int,1:?int,2:?int}> */
    private array $dmsParentByName = [];

    /** @var array<string, int> tally_guid (lower) => DMS product id */
    private array $dmsProductGuid = [];

    public function warmDms(OrganizationCompany $company): void
    {
        $cid = (int) $company->id;
        $this->maps['dms_product:'.$cid] = $this->loadDmsProductMap($cid);
        $this->maps['dms_uom'] = $this->loadDmsUomMap();
        $this->maps['dms_brand:'.$cid] = $this->loadMap('brands', $cid, 'name');
        $this->warmDmsCatalogParents($cid);
        $this->warmDmsProductGuids();
    }

    public function warmBooks(Company $company): void
    {
        $cid = (int) $company->id;
        $this->maps['ledger:'.$cid] = $this->loadMap((new Ledger)->getTable(), $cid, 'name');
        $this->maps['group:'.$cid] = $this->loadMap((new AccountGroup)->getTable(), $cid, 'name');
        $this->maps['unit:'.$cid] = $this->loadUnitMap((new Unit)->getTable(), $cid);
        $this->maps['godown:'.$cid] = $this->loadMap((new Godown)->getTable(), $cid, 'name');
        $this->maps['product:'.$cid] = $this->loadMap((new Product)->getTable(), $cid, 'name');
        $this->maps['product_group:'.$cid] = $this->loadMap((new ProductGroup)->getTable(), $cid, 'name');
        $this->maps['cost_centre:'.$cid] = $this->loadMap((new CostCentre)->getTable(), $cid, 'name');
    }

    public function ledgerId(int $companyId, string $name): ?int
    {
        return $this->get('ledger:'.$companyId, $name);
    }

    public function rememberLedger(int $companyId, string $name, int $id): void
    {
        $this->put('ledger:'.$companyId, $name, $id);
    }

    public function groupId(int $companyId, string $name): ?int
    {
        return $this->get('group:'.$companyId, $name);
    }

    public function rememberGroup(int $companyId, string $name, int $id): void
    {
        $this->put('group:'.$companyId, $name, $id);
    }

    public function unitId(int $companyId, string $name): ?int
    {
        return $this->get('unit:'.$companyId, $name);
    }

    public function rememberUnit(int $companyId, string $name, int $id): void
    {
        $this->put('unit:'.$companyId, $name, $id);
    }

    public function godownId(int $companyId, string $name): ?int
    {
        return $this->get('godown:'.$companyId, $name);
    }

    public function rememberGodown(int $companyId, string $name, int $id): void
    {
        $this->put('godown:'.$companyId, $name, $id);
    }

    public function productId(int $companyId, string $name): ?int
    {
        return $this->get('product:'.$companyId, $name);
    }

    public function rememberProduct(int $companyId, string $name, int $id): void
    {
        $this->put('product:'.$companyId, $name, $id);
    }

    public function productGroupId(int $companyId, string $name): ?int
    {
        return $this->get('product_group:'.$companyId, $name);
    }

    public function rememberProductGroup(int $companyId, string $name, int $id): void
    {
        $this->put('product_group:'.$companyId, $name, $id);
    }

    public function dmsProductId(int $companyId, string $name): ?int
    {
        return $this->get('dms_product:'.$companyId, $name);
    }

    public function rememberDmsProduct(int $companyId, string $name, int $id): void
    {
        $this->put('dms_product:'.$companyId, $name, $id);
    }

    public function dmsProductIdByGuid(?string $guid): ?int
    {
        if ($guid === null || $guid === '') {
            return null;
        }
        $k = mb_strtolower(trim($guid));

        return $this->dmsProductGuid[$k] ?? null;
    }

    public function rememberDmsProductGuid(?string $guid, int $productId): void
    {
        if ($guid === null || $guid === '') {
            return;
        }
        $this->dmsProductGuid[mb_strtolower(trim($guid))] = $productId;
    }

    public function dmsUomId(string $unitName): ?int
    {
        return $this->get('dms_uom', $unitName);
    }

    public function rememberDmsUom(string $unitName, int $id): void
    {
        $this->put('dms_uom', $unitName, $id);
    }

    /**
     * @return array{0:?int,1:?int,2:?int}|null brand_id, category_id, sub_category_id
     */
    public function dmsProductParentIds(int $companyId, string $parent): ?array
    {
        $k = $companyId.':'.$this->norm($parent);

        return $this->dmsParentByName[$k] ?? null;
    }

    /**
     * @param  array{0:?int,1:?int,2:?int}  $ids
     */
    public function rememberDmsProductParent(int $companyId, string $parent, array $ids): void
    {
        $k = $companyId.':'.$this->norm($parent);
        if ($k === $companyId.':') {
            return;
        }
        $this->dmsParentByName[$k] = $ids;
    }

    public function dmsBrandId(int $companyId, string $name): ?int
    {
        return $this->get('dms_brand:'.$companyId, $name);
    }

    public function rememberDmsBrand(int $companyId, string $name, int $id): void
    {
        $this->put('dms_brand:'.$companyId, $name, $id);
    }

    public function costCentreId(int $companyId, string $name): ?int
    {
        return $this->get('cost_centre:'.$companyId, $name);
    }

    public function rememberCostCentre(int $companyId, string $name, int $id): void
    {
        $this->put('cost_centre:'.$companyId, $name, $id);
    }

    /**
     * Resolve Eloquent model from cache id when possible.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     * @param  class-string<T>  $class
     * @return T|null
     */
    public function findNamed(string $class, int $companyId, string $name, string $nameColumn = 'name'): ?\Illuminate\Database\Eloquent\Model
    {
        $key = match ($class) {
            Ledger::class => 'ledger:'.$companyId,
            AccountGroup::class => 'group:'.$companyId,
            Unit::class => 'unit:'.$companyId,
            Godown::class => 'godown:'.$companyId,
            Product::class => 'product:'.$companyId,
            ProductGroup::class => 'product_group:'.$companyId,
            CostCentre::class => 'cost_centre:'.$companyId,
            default => null,
        };
        if ($key === null) {
            return null;
        }
        $id = $this->get($key, $name);
        if (! $id) {
            return null;
        }

        return $class::query()->find($id);
    }

    public function rememberModel(string $class, int $companyId, string $name, int $id): void
    {
        match ($class) {
            Ledger::class => $this->rememberLedger($companyId, $name, $id),
            AccountGroup::class => $this->rememberGroup($companyId, $name, $id),
            Unit::class => $this->rememberUnit($companyId, $name, $id),
            Godown::class => $this->rememberGodown($companyId, $name, $id),
            Product::class => $this->rememberProduct($companyId, $name, $id),
            ProductGroup::class => $this->rememberProductGroup($companyId, $name, $id),
            CostCentre::class => $this->rememberCostCentre($companyId, $name, $id),
            default => null,
        };
    }

    private function get(string $map, string $name): ?int
    {
        $k = $this->norm($name);
        if ($k === '') {
            return null;
        }

        return $this->maps[$map][$k] ?? null;
    }

    private function put(string $map, string $name, int $id): void
    {
        $k = $this->norm($name);
        if ($k === '') {
            return;
        }
        $this->maps[$map][$k] = $id;
    }

    private function norm(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name));
    }

    /**
     * @return array<string, int>
     */
    private function loadMap(string $table, int $companyId, string $column): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
            return [];
        }
        $rows = DB::table($table)
            ->where('company_id', $companyId)
            ->select(['id', $column])
            ->get();
        $map = [];
        foreach ($rows as $row) {
            $map[$this->norm((string) $row->{$column})] = (int) $row->id;
        }

        return $map;
    }

    /**
     * @return array<string, int>
     */
    private function loadUnitMap(string $table, int $companyId): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
            return [];
        }
        $rows = DB::table($table)
            ->where('company_id', $companyId)
            ->select(['id', 'name', 'symbol'])
            ->get();
        $map = [];
        foreach ($rows as $row) {
            $map[$this->norm((string) $row->symbol)] = (int) $row->id;
            $map[$this->norm((string) $row->name)] = (int) $row->id;
        }

        return $map;
    }

    /**
     * @return array<string, int>
     */
    private function loadDmsProductMap(int $companyId): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('products')) {
            return [];
        }
        $rows = DB::table('products')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->select(['id', 'name'])
            ->get();
        $map = [];
        foreach ($rows as $row) {
            $name = (string) $row->name;
            $map[$this->norm($name)] = (int) $row->id;
            $formatted = \App\Support\TitleCase::format($name);
            if ($formatted !== $name) {
                $map[$this->norm($formatted)] = (int) $row->id;
            }
        }

        return $map;
    }

    /**
     * @return array<string, int>
     */
    private function loadDmsUomMap(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('uoms')) {
            return [];
        }
        $rows = DB::table('uoms')->select(['id', 'name', 'code'])->get();
        $map = [];
        foreach ($rows as $row) {
            $map[$this->norm((string) $row->code)] = (int) $row->id;
            $map[$this->norm((string) $row->name)] = (int) $row->id;
        }

        return $map;
    }

    private function warmDmsCatalogParents(int $companyId): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('sub_categories')) {
            return;
        }

        $subs = DB::table('sub_categories as sc')
            ->join('categories as c', 'c.id', '=', 'sc.category_id')
            ->join('brands as b', 'b.id', '=', 'c.brand_id')
            ->where('b.company_id', $companyId)
            ->select(['sc.id as sub_id', 'sc.name as sub_name', 'c.id as category_id', 'b.id as brand_id'])
            ->get();
        foreach ($subs as $row) {
            $this->dmsParentByName[$companyId.':'.$this->norm((string) $row->sub_name)] = [
                (int) $row->brand_id,
                (int) $row->category_id,
                (int) $row->sub_id,
            ];
        }

        $categories = DB::table('categories as c')
            ->join('brands as b', 'b.id', '=', 'c.brand_id')
            ->where('b.company_id', $companyId)
            ->select(['c.id as category_id', 'c.name as category_name', 'b.id as brand_id'])
            ->get();
        foreach ($categories as $row) {
            $k = $companyId.':'.$this->norm((string) $row->category_name);
            if (! isset($this->dmsParentByName[$k])) {
                $this->dmsParentByName[$k] = [(int) $row->brand_id, (int) $row->category_id, null];
            }
        }

        foreach ($this->maps['dms_brand:'.$companyId] ?? [] as $norm => $brandId) {
            $k = $companyId.':'.$norm;
            if (! isset($this->dmsParentByName[$k])) {
                $this->dmsParentByName[$k] = [$brandId, null, null];
            }
        }
    }

    private function warmDmsProductGuids(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('tally_sync_mappings')) {
            return;
        }

        $entityType = DmsProduct::class;
        $rows = DB::table('tally_sync_mappings')
            ->where('tally_type', 'stock_item')
            ->where('entity_type', $entityType)
            ->whereNotNull('tally_guid')
            ->select(['tally_guid', 'entity_id'])
            ->get();
        foreach ($rows as $row) {
            $this->rememberDmsProductGuid((string) $row->tally_guid, (int) $row->entity_id);
        }
    }
}
