<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Imports\ProductsImport;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductBulkImportPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected Uom $pcs;
    protected Uom $box;
    protected Uom $kg;
    protected Brand $brand;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::firstOrCreate(
            ['code' => 'CMP-PERF-TEST'],
            ['name' => 'Performance Test Corp', 'is_active' => true]
        );

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR-PERF-TEST'],
            ['company_id' => $this->company->id, 'name' => 'Performance Test Branch', 'is_active' => true]
        );

        $this->user = User::firstOrCreate(
            ['email' => 'perf-admin@dms.test'],
            [
                'name' => 'Perf Admin',
                'password' => bcrypt('password'),
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
            ]
        );
        $this->user->assignRole($role);

        $this->pcs = Uom::firstOrCreate(['code' => 'PCS'], ['name' => 'Pieces', 'is_active' => true]);
        $this->box = Uom::firstOrCreate(['code' => 'BOX'], ['name' => 'Box', 'is_active' => true]);
        $this->kg = Uom::firstOrCreate(['code' => 'KG'], ['name' => 'Kilogram', 'is_active' => true]);

        $this->brand = Brand::firstOrCreate(
            ['name' => 'SONY'],
            ['company_id' => $this->company->id, 'is_active' => true]
        );

        $this->category = Category::firstOrCreate(
            ['name' => 'TELEVISION'],
            ['brand_id' => $this->brand->id, 'is_active' => true]
        );
    }

    public function test_import_100_products_executes_quickly_and_accurately(): void
    {
        $rows = new Collection();
        for ($i = 1; $i <= 100; $i++) {
            $rows->push([
                'name' => "Performance Test Product {$i}",
                'part_number' => sprintf('PERF-100-%05d', $i),
                'uom_code' => 'PCS',
                'selling_price' => 100 + $i,
                'purchase_price' => 80 + $i,
                'mrp' => 120 + $i,
                'brand' => 'SONY',
                'category' => 'TELEVISION',
            ]);
        }

        $import = new ProductsImport($this->company->id);
        $start = microtime(true);
        $import->collection($rows);
        $elapsed = microtime(true) - $start;

        $this->assertSame(100, $import->created);
        $this->assertSame(0, $import->updated);
        $this->assertSame(0, $import->skipped);
        $this->assertSame(0, $import->failed);
        $this->assertEmpty($import->errors);

        $this->assertLessThan(10.0, $elapsed, "100 rows should import well under 10 seconds (took {$elapsed}s)");
        $this->assertSame(100, Product::query()->where('sku', 'like', 'PERF-100-%')->count());
        $this->assertSame(100, ProductUom::query()->where('is_base', true)->count());
    }

    public function test_import_500_products_executes_quickly_and_accurately(): void
    {
        $rows = new Collection();
        for ($i = 1; $i <= 500; $i++) {
            $rows->push([
                'name' => "Bulk 500 Item {$i}",
                'part_number' => sprintf('PERF-500-%05d', $i),
                'uom_code' => ($i % 2 === 0) ? 'PCS' : 'BOX',
                'selling_price' => 500.0,
                'purchase_price' => 400.0,
                'mrp' => 600.0,
            ]);
        }

        $import = new ProductsImport($this->company->id);
        $start = microtime(true);
        $import->collection($rows);
        $elapsed = microtime(true) - $start;

        $this->assertSame(500, $import->created);
        $this->assertSame(0, $import->failed);
        $this->assertLessThan(20.0, $elapsed, "500 rows should import well under 20 seconds (took {$elapsed}s)");
        $this->assertSame(500, Product::query()->where('sku', 'like', 'PERF-500-%')->count());
    }

    public function test_import_1234_products_executes_without_timeout_and_accurately(): void
    {
        $rows = new Collection();
        for ($i = 1; $i <= 1234; $i++) {
            $rows->push([
                'name' => "Catalogue Product {$i}",
                'part_number' => sprintf('CAT-1234-%05d', $i),
                'uom_code' => ($i % 3 === 0) ? 'BOX' : (($i % 3 === 1) ? 'PCS' : 'KG'),
                'selling_price' => 1500.00,
                'purchase_price' => 1200.00,
                'mrp' => 1800.00,
                'tax_rate' => 18.0,
                'brand' => 'SONY',
                'category' => 'TELEVISION',
            ]);
        }

        $import = new ProductsImport($this->company->id);
        $start = microtime(true);
        $import->collection($rows);
        $elapsed = microtime(true) - $start;

        $this->assertSame(1234, $import->created);
        $this->assertSame(0, $import->updated);
        $this->assertSame(0, $import->skipped);
        $this->assertSame(0, $import->failed);
        $this->assertEmpty($import->errors);

        // Crucial requirement: Must complete well under 30 seconds
        $this->assertLessThan(25.0, $elapsed, "1,234 rows must complete well under 30 seconds (took {$elapsed}s)");
        $this->assertSame(1234, Product::query()->where('sku', 'like', 'CAT-1234-%')->count());
    }

    public function test_import_with_valid_and_invalid_uom_values(): void
    {
        $rows = new Collection([
            ['name' => 'Valid Item 1', 'part_number' => 'VAL-01', 'uom_code' => 'PCS'],
            ['name' => 'Valid Item 2', 'part_number' => 'VAL-02', 'uom_code' => 'PCS (PCS-PIECES)'],
            ['name' => 'Invalid Item 3', 'part_number' => 'INVAL-03', 'uom_code' => 'UNKNOWN_RANDOM_UOM_XYZ'],
            ['name' => 'Blank UOM Item 4', 'part_number' => 'BLANK-04', 'uom_code' => ''],
            ['name' => 'Valid Item 5', 'part_number' => 'VAL-05', 'uom_code' => 'BOX'],
        ]);

        $import = new ProductsImport($this->company->id);
        $import->collection($rows);

        $this->assertSame(3, $import->created);
        $this->assertSame(2, $import->failed);
        $this->assertSame(0, $import->skipped);
        $this->assertCount(2, $import->errors);

        $missing = $import->getMissingUoms();
        $this->assertArrayHasKey('UNKNOWN_RANDOM_UOM_XYZ', $missing);
        $this->assertSame(1, $missing['UNKNOWN_RANDOM_UOM_XYZ']['count']);
    }

    public function test_duplicate_products_within_file_and_reimport_skip_and_update(): void
    {
        // 1. Same SKU twice in the file
        $rows = new Collection([
            [
                'name' => 'Original Item',
                'part_number' => 'DUP-001',
                'uom_code' => 'PCS',
                'selling_price' => 100,
            ],
            [
                'name' => 'Original Item',
                'part_number' => 'DUP-001', // Identical row in same file
                'uom_code' => 'PCS',
                'selling_price' => 100,
            ],
            [
                'name' => 'Second Item',
                'part_number' => 'DUP-002',
                'uom_code' => 'PCS',
                'selling_price' => 200,
            ],
        ]);

        $import = new ProductsImport($this->company->id);
        $import->collection($rows);

        $this->assertSame(2, $import->created);
        $this->assertSame(1, $import->skipped, 'Second duplicate in same file should be skipped');
        $this->assertSame(0, $import->failed);

        // 2. Re-importing exact same rows should skip all of them
        $reimport = new ProductsImport($this->company->id);
        $reimport->collection(new Collection([
            ['name' => 'Original Item', 'part_number' => 'DUP-001', 'uom_code' => 'PCS', 'selling_price' => 100],
            ['name' => 'Second Item', 'part_number' => 'DUP-002', 'uom_code' => 'PCS', 'selling_price' => 200],
        ]));

        $this->assertSame(0, $reimport->created);
        $this->assertSame(0, $reimport->updated);
        $this->assertSame(2, $reimport->skipped);

        // 3. Re-importing with modified price should update
        $updateImport = new ProductsImport($this->company->id);
        $updateImport->collection(new Collection([
            ['name' => 'Original Item', 'part_number' => 'DUP-001', 'uom_code' => 'PCS', 'selling_price' => 150], // updated
            ['name' => 'Second Item', 'part_number' => 'DUP-002', 'uom_code' => 'PCS', 'selling_price' => 200], // skipped
        ]));

        $this->assertSame(0, $updateImport->created);
        $this->assertSame(1, $updateImport->updated);
        $this->assertSame(1, $updateImport->skipped);

        $product = Product::where('sku', 'DUP-001')->first();
        $this->assertEquals(150.00, (float) $product->selling_price);
    }

    public function test_import_with_missing_optional_fields(): void
    {
        $rows = new Collection([
            [
                'name' => 'Minimal Product Alpha',
                'part_number' => 'MIN-001',
                'uom_code' => 'PCS',
                // optional fields omitted or null
                'brand' => null,
                'category' => '',
                'sub_category' => null,
                'product_type' => null,
                'height_cm' => null,
                'net_weight' => '',
                'selling_price' => '',
                'purchase_price' => null,
            ],
            [
                'name' => 'Minimal Product Beta',
                'part_number' => 'MIN-002',
                'uom_code' => 'BOX',
            ],
        ]);

        $import = new ProductsImport($this->company->id);
        $import->collection($rows);

        $this->assertSame(2, $import->created);
        $this->assertSame(0, $import->failed);

        $p1 = Product::where('sku', 'MIN-001')->first();
        $this->assertNotNull($p1);
        $this->assertNull($p1->brand_id);
        $this->assertNull($p1->category_id);
        $this->assertTrue($p1->is_active);
        $this->assertNotNull($p1->serial_no);
        $this->assertStringStartsWith('PRD-', $p1->serial_no);
    }
}

