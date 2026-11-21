<?php

namespace App\Domains\TallySync\Import;

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
}
