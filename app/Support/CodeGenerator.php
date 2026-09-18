<?php

namespace App\Support;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use Illuminate\Support\Str;

/**
 * Deterministic, collision-safe auto-code generator for master records.
 *
 * Format: PREFIX-{company or GLOBAL}-{5-digit zero-padded seq}
 * Uses the highest existing numeric suffix in the same prefix scope so gaps do
 * not cause duplicates when rows are deleted.
 */
class CodeGenerator
{
    public static function forBrand(?int $companyId = null): string
    {
        return self::next(Brand::class, 'BR', $companyId, 'code');
    }

    public static function forCategory(?int $companyId = null): string
    {
        return self::next(Category::class, 'CAT', $companyId, 'code');
    }

    public static function forSubCategory(?int $companyId = null): string
    {
        return self::next(SubCategory::class, 'SCAT', $companyId, 'code');
    }

    public static function forProduct(?int $companyId = null): string
    {
        return self::next(Product::class, 'PROD', $companyId, 'sku');
    }

    public static function forProductSerial(): string
    {
        return self::next(Product::class, 'PRD', null, 'serial_no');
    }

    public static function forCustomer(?int $companyId = null): string
    {
        return self::next(Customer::class, 'PTY', $companyId, 'code');
    }

    protected static function next(string $modelClass, string $prefix, ?int $companyId, string $column): string
    {
        $scope = $companyId ? (string) $companyId : 'GLOBAL';
        $needle = "{$prefix}-{$scope}-";

        $latest = $modelClass::query()
            ->where($column, 'like', $needle.'%')
            ->orderByDesc('id')
            ->limit(50)
            ->pluck($column)
            ->map(fn ($v) => (int) Str::afterLast($v, '-'))
            ->max();

        $next = (int) ($latest ?? 0) + 1;

        return sprintf('%s-%s-%05d', $prefix, $scope, $next);
    }
}
