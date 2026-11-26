<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Imports\BranchesImport;
use App\Domains\Master\Imports\BrandsImport;
use App\Domains\Master\Imports\CategoriesImport;
use App\Domains\Master\Imports\CompaniesImport;
use App\Domains\Master\Imports\FinancialYearsImport;
use App\Domains\Master\Imports\ProductTypesImport;
use App\Domains\Master\Imports\SubCategoriesImport;
use App\Domains\Master\Imports\UomsImport;
use App\Domains\Master\Imports\WarehousesImport;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BulkImportMastersCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Coverage Co',
            'code' => 'COV-01',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->assignRole($role);
    }

    public function test_new_templates_are_downloadable(): void
    {
        foreach ([
            'companies', 'branches', 'warehouses', 'financial-years', 'uoms',
            'brands', 'categories', 'sub-categories', 'product-types', 'price-master',
        ] as $type) {
            $response = $this->actingAs($this->user)->get(route('masters.bulk-import.template', $type));
            $response->assertOk();
            $content = $response->streamedContent();
            $this->assertNotEmpty($content, "Empty template for {$type}");
        }

        $price = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'price-master'))->streamedContent();
        $this->assertStringContainsString('rate_includes_gst', $price);
        $this->assertStringContainsString('part_number', $price);

        $products = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'products'))->streamedContent();
        $this->assertStringContainsString('alt_purchase_price', $products);
        $this->assertStringContainsString('series', $products);
        $this->assertStringContainsString('discount_percent', $products);

        $parties = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'parties'))->streamedContent();
        $this->assertStringContainsString('level', $parties);

        $leads = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'leads'))->streamedContent();
        $this->assertStringContainsString('lead_source', $leads);
        $this->assertStringContainsString('interested_product', $leads);

        $phone = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'phone-directory'))->streamedContent();
        $this->assertStringContainsString('primary_mobile', $phone);
        $this->assertStringContainsString('contact_type', $phone);

        $pos = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'purchase-orders'))->streamedContent();
        $this->assertStringContainsString('po_no', $pos);
        $this->assertStringContainsString('supplier_code', $pos);
        $this->assertStringContainsString('part_number', $pos);
        $this->assertStringContainsString('unit_cost', $pos);

        $branchesTpl = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'branches'))->streamedContent();
        $this->assertStringContainsString('branch_type', $branchesTpl);
        $this->assertStringContainsString('address_line_2', $branchesTpl);
        $this->assertStringNotContainsString('gstin', $branchesTpl);

        $warehousesTpl = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'warehouses'))->streamedContent();
        $this->assertStringContainsString('warehouse_type', $warehousesTpl);
        $this->assertStringContainsString('contact_person', $warehousesTpl);
        $this->assertStringContainsString('city', $warehousesTpl);

        $fyTpl = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'financial-years'))->streamedContent();
        $this->assertStringContainsString('starts_on', $fyTpl);
        $this->assertStringContainsString('ends_on', $fyTpl);
    }

    public function test_setup_and_catalog_imports_happy_path(): void
    {
        $companies = new CompaniesImport();
        $companies->collection(collect([[
            'code' => 'IMP-01',
            'name' => 'Import Co',
            'legal_name' => 'Import Co Pvt Ltd',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'is_active' => 1,
            'is_default' => 1,
        ]]));
        $this->assertSame(0, $companies->skipped, json_encode($companies->errors));
        $company = Company::query()->where('code', 'IMP-01')->first();
        $this->assertNotNull($company);

        $branches = new BranchesImport();
        $branches->collection(collect([[
            'company_code' => 'IMP-01',
            'code' => 'BR-01',
            'name' => 'HQ',
            'branch_type' => 'Head Office',
            'phone' => '9999900002',
            'address' => '#1 Main Road',
            'address_line_2' => 'Near Metro',
            'state' => 'Karnataka',
            'city' => 'Bengaluru',
            'country' => 'India',
            'pincode' => '560001',
            'email' => 'hq@import.test',
            'is_default' => 1,
            'is_active' => 1,
        ]]));
        $this->assertSame(0, $branches->skipped, json_encode($branches->errors));
        $branch = Branch::query()->where('code', 'BR-01')->first();
        $this->assertNotNull($branch);
        $this->assertSame('Head Office', $branch->branch_type);
        $this->assertSame('Bengaluru', $branch->city);

        $warehouses = new WarehousesImport();
        $warehouses->collection(collect([[
            'company_code' => 'IMP-01',
            'branch_code' => 'BR-01',
            'code' => 'WH-01',
            'name' => 'Main WH',
            'warehouse_type' => 'Main Warehouse',
            'contact_person' => 'Store Manager',
            'contact_phone' => '9999900003',
            'address' => 'Godown Lane',
            'address_line_2' => 'Unit 2',
            'state' => 'Karnataka',
            'city' => 'Bengaluru',
            'country' => 'India',
            'pincode' => '560002',
            'is_default' => 1,
            'is_active' => 1,
        ]]));
        $this->assertSame(0, $warehouses->skipped, json_encode($warehouses->errors));
        $this->assertDatabaseHas('warehouses', ['code' => 'WH-01', 'city' => 'Bengaluru']);

        $years = new FinancialYearsImport();
        $years->collection(collect([[
            'company_code' => 'IMP-01',
            'name' => 'FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => 1,
            'is_closed' => 0,
        ]]));
        $this->assertSame(0, $years->skipped, json_encode($years->errors));
        $this->assertTrue(FinancialYear::query()->where('company_id', $company->id)->where('is_current', true)->exists());

        $uoms = new UomsImport();
        $uoms->collection(collect([
            ['code' => 'PCS', 'name' => 'Pieces', 'is_active' => 1],
            ['code' => 'BOX', 'name' => 'Box', 'is_active' => 1],
        ]));
        $this->assertSame(0, $uoms->skipped);
        $this->assertTrue(Uom::query()->where('code', 'PCS')->exists());

        $brands = new BrandsImport($company->id);
        $brands->collection(collect([[
            'company_code' => 'IMP-01',
            'name' => 'Acme',
            'detail' => 'OEM',
            'is_active' => 1,
        ]]));
        $this->assertSame(0, $brands->skipped, json_encode($brands->errors));
        $brand = Brand::query()->where('name', 'Acme')->first();
        $this->assertNotNull($brand);

        $categories = new CategoriesImport($company->id);
        $categories->collection(collect([[
            'brand_name' => 'Acme',
            'name' => 'Widgets',
            'is_active' => 1,
        ]]));
        $this->assertSame(0, $categories->skipped, json_encode($categories->errors));
        $category = Category::query()->where('name', 'Widgets')->first();
        $this->assertNotNull($category);

        $subs = new SubCategoriesImport($company->id);
        $subs->collection(collect([[
            'brand_name' => 'Acme',
            'category_name' => 'Widgets',
            'name' => 'Standard',
            'is_active' => 1,
        ]]));
        $this->assertSame(0, $subs->skipped, json_encode($subs->errors));
        $this->assertTrue(SubCategory::query()->where('name', 'Standard')->exists());

        $types = new ProductTypesImport($company->id);
        $types->collection(collect([[
            'company_code' => 'IMP-01',
            'name' => 'Serialized Goods',
            'code' => 'SG',
            'description' => 'Sellable',
            'track_serial' => 1,
            'is_active' => 1,
        ]]));
        $this->assertSame(0, $types->skipped, json_encode($types->errors));
        $type = ProductType::query()
            ->where('company_id', $company->id)
            ->where('code', 'SG')
            ->first();
        $this->assertNotNull($type);
        $this->assertTrue((bool) $type->track_serial);
        $this->assertInstanceOf(Warehouse::class, Warehouse::query()->where('code', 'WH-01')->first());
    }
}
