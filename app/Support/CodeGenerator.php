<?php

namespace App\Support;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Company;
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
    public static function forCompany(): string
    {
        return self::next(Company::class, 'CMP', null, 'code');
    }

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

    public static function forParty(?int $companyId = null): string
    {
        return self::next(Customer::class, 'PTY', $companyId, 'code');
    }

    public static function forEmployee(?int $companyId = null): string
    {
        return self::next(\App\Domains\Hrms\Models\Employee::class, 'EMP', $companyId, 'employee_code');
    }

    protected static function next(string $modelClass, string $prefix, ?int $companyId, string $column): string
    {
        $scope = $companyId ? (string) $companyId : 'GLOBAL';
        $needle = "{$prefix}-{$scope}-";

        $query = $modelClass::query()->where($column, 'like', $needle.'%');
        if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $existing = $query->pluck($column);

        $max = 0;
        foreach ($existing as $val) {
            $suffix = Str::afterLast($val, '-');
            if (is_numeric($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        $next = $max + 1;
        $candidate = sprintf('%s-%s-%05d', $prefix, $scope, $next);

        $existsQuery = fn ($val) => \Illuminate\Support\Facades\DB::transactionLevel() > 0
            ? $modelClass::query()->where($column, $val)->lockForUpdate()->exists()
            : $modelClass::query()->where($column, $val)->exists();

        while ($existsQuery($candidate)) {
            $next++;
            $candidate = sprintf('%s-%s-%05d', $prefix, $scope, $next);
        }

        return $candidate;
    }
}