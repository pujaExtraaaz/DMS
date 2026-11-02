<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use App\Domains\Sync\Support\SyncNames;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\Company;
use Tally\Models\HsnSac;
use Tally\Models\Product as BooksProduct;
use Tally\Models\TaxCategory;
use Tally\Models\TaxRate;
use Tally\Models\Unit;
use Tally\Tax\HsnCatalogue;
use Tally\Tax\HsnKind;

class ProductSync
{
    public const KEY = 'product';

    public function __construct(
        private readonly UomSync $uoms,
        private readonly BrandSync $brands,
        private readonly CategorySync $categories,
        private readonly SubCategorySync $subCategories,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof Product) {
            $this->push($model);

            return;
        }

        if ($model instanceof BooksProduct) {
            $this->pull($model);
        }
    }

    public function push(Product $product): void
    {
        if (! $product->company_id) {
            return;
        }

        $company = BooksCompany::forOrganization((int) $product->company_id);
        if (! $company) {
            return;
        }

        $uom = Uom::query()->find($product->base_uom_id);
        if (! $uom) {
            throw new \RuntimeException('Product '.$product->id.' has no unit of measure.');
        }

        $unit = $this->uoms->pushToCompany($uom, $company);
        $alternateUnitId = null;
        if ($product->alternate_uom_id) {
            $alt = Uom::query()->find($product->alternate_uom_id);
            if ($alt) {
                $alternateUnitId = $this->uoms->pushToCompany($alt, $company)->id;
            }
        }

        $books = BooksProduct::query()->find(SyncLinks::acctId(self::KEY, $product));
        $name = SyncNames::unique(
            BooksProduct::query()->where('company_id', $company->id),
            'name',
            $product->name,
            $books?->id
        );
        $code = SyncNames::unique(
            BooksProduct::query()->where('company_id', $company->id),
            'code',
            SyncNames::code($product->sku ?: ('P'.$product->id)),
            $books?->id
        );

        $groupId = $this->resolveProductGroupId($product, $company);
        [$hsnId, $taxRateId] = $this->resolveHsnAndTax($company, $product);
        $supply = $this->booksSupplyType($product->supply_type);

        $attributes = [
            'company_id' => $company->id,
            'product_group_id' => $groupId,
            'primary_unit_id' => $unit->id,
            'alternate_unit_id' => $alternateUnitId,
            'name' => $name,
            'code' => $code,
            'part_number' => $product->sku ?: $product->serial_no,
            'description' => $product->description,
            'type_of_supply' => $supply,
            'hsn_sac_id' => $hsnId,
            'tax_rate_id' => $taxRateId,
            'conversion_factor' => $product->conversion_factor ?? 1,
            'purchase_rate' => $product->purchase_price ?? 0,
            'sales_rate' => $product->selling_price ?? 0,
            'minimum_stock' => $product->min_stock ?? 0,
            'reorder_level' => $product->reorder_level ?? 0,
            'track_batch' => $product->isBatchTracked(),
            'track_serial' => $product->isSerialTracked(),
            'is_active' => (bool) $product->is_active,
        ];

        $books ? $books->update($attributes) : $books = BooksProduct::query()->create($attributes);
        SyncLinks::store(self::KEY, $product, $books);
    }

    public function pull(BooksProduct $books): void
    {
        $organizationId = BooksCompany::organizationId($books->company_id);
        if (! $organizationId) {
            return;
        }

        $unit = Unit::query()->find($books->primary_unit_id);
        if (! $unit) {
            throw new \RuntimeException('Books product '.$books->id.' has no unit.');
        }

        $this->uoms->pull($unit);
        $uomId = SyncLinks::dmsId($this->uoms->entityKey($books->company_id), $unit);
        $product = Product::query()->find(SyncLinks::dmsId(self::KEY, $books));
        $sku = SyncNames::unique(
            Product::query(),
            'sku',
            SyncNames::code($books->part_number ?: $books->code ?: ('B'.$books->id), 50),
            $product?->id,
            50
        );

        $books->loadMissing(['hsnSac', 'taxRate']);
        $tracking = [];
        if ($books->track_batch) {
            $tracking[] = 'batch';
        }
        if ($books->track_serial) {
            $tracking[] = 'serial';
        }

        $attributes = [
            'company_id' => $organizationId,
            'name' => $books->name,
            'sku' => $sku,
            'serial_no' => $product?->serial_no ?: mb_substr('B'.$books->id, 0, 30),
            'base_uom_id' => $uomId,
            'description' => $books->description,
            'hsn_code' => $books->hsnSac?->code,
            'tax_rate' => $books->taxRate?->igst_rate ?? $product?->tax_rate ?? 0,
            'supply_type' => $this->dmsSupplyType($books->type_of_supply),
            'purchase_price' => $books->purchase_rate ?? 0,
            'selling_price' => $books->sales_rate ?? 0,
            'min_stock' => $books->minimum_stock ?? 0,
            'reorder_level' => $books->reorder_level ?? 0,
            'tracking_type' => $tracking !== [] ? $tracking : ($product?->tracking_type ?? null),
            'is_active' => (bool) $books->is_active,
        ];

        $product ? $product->update($attributes) : $product = Product::query()->create($attributes);
        SyncLinks::store(self::KEY, $product, $books);
    }

    private function booksSupplyType(?string $supplyType): string
    {
        return match (strtolower(trim((string) $supplyType))) {
            'services', 'service' => 'service',
            default => 'goods',
        };
    }

    private function dmsSupplyType(?string $supplyType): string
    {
        return match (strtolower(trim((string) $supplyType))) {
            'service', 'services' => 'services',
            'capital_goods' => 'capital_goods',
            default => 'goods',
        };
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function resolveHsnAndTax(Company $company, Product $product): array
    {
        $hsnCode = preg_replace('/\D/', '', (string) ($product->hsn_code ?? '')) ?: '';
        $percent = (float) ($product->tax_rate ?? 0);
        $hsnId = null;
        $taxRateId = null;

        if ($hsnCode !== '') {
            $adopted = HsnCatalogue::adopt($company, $hsnCode);
            if ($adopted) {
                $hsnId = $adopted->id;
                $taxRateId = $adopted->tax_rate_id;
            }
        }

        if ($percent > 0) {
            $taxRateId = $this->ensureTaxRate($company, $percent)->id;
        }

        if ($hsnCode !== '' && $hsnId === null) {
            $hsn = HsnSac::query()->firstOrCreate(
                ['company_id' => $company->id, 'code' => $hsnCode],
                [
                    'tax_rate_id' => $taxRateId,
                    'kind' => strlen($hsnCode) >= 6 ? HsnKind::Hsn : HsnKind::Sac,
                    'description' => $product->description ?: ('HSN '.$hsnCode),
                    'is_active' => true,
                ]
            );
            if ($taxRateId && $hsn->tax_rate_id === null) {
                $hsn->update(['tax_rate_id' => $taxRateId]);
            }
            $hsnId = $hsn->id;
            $taxRateId = $taxRateId ?: $hsn->tax_rate_id;
        }

        return [$hsnId, $taxRateId];
    }

    private function ensureTaxRate(Company $company, float $percent): TaxRate
    {
        $igst = number_format($percent, 4, '.', '');
        $half = number_format($percent / 2, 4, '.', '');
        $found = TaxRate::query()
            ->where('company_id', $company->id)
            ->where('igst_rate', $igst)
            ->where('cess_rate', '0.0000')
            ->first();
        if ($found) {
            return $found;
        }

        $category = TaxCategory::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => 'GST'],
            ['code' => 'GST', 'is_active' => true],
        );

        return TaxRate::query()->create([
            'company_id' => $company->id,
            'tax_category_id' => $category->id,
            'name' => 'GST '.rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.'),
            'code' => 'GST'.(int) $percent,
            'cgst_rate' => $half,
            'sgst_rate' => $half,
            'igst_rate' => $igst,
            'cess_rate' => '0.0000',
            'is_active' => true,
        ]);
    }

    private function resolveProductGroupId(Product $product, \Tally\Models\Company $company): int
    {
        $product->loadMissing(['subCategory.category.brand', 'category.brand', 'brand']);

        if ($product->subCategory) {
            $this->subCategories->push($product->subCategory);
            $id = SyncLinks::acctId(SubCategorySync::KEY, $product->subCategory);
            if ($id) {
                return $id;
            }
        }

        if ($product->category) {
            $this->categories->push($product->category);
            $id = SyncLinks::acctId(CategorySync::KEY, $product->category);
            if ($id) {
                return $id;
            }
        }

        if ($product->brand) {
            $this->brands->push($product->brand);
            $id = SyncLinks::acctId(BrandSync::KEY, $product->brand);
            if ($id) {
                return $id;
            }
        }

        return BooksCompany::generalGroup($company)->id;
    }
}
