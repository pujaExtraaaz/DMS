<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Master\Imports\PartiesImport;
use App\Domains\Master\Imports\ProductsImport;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\PartyBankAccount;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BulkImportUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Uom $uom;

    protected Uom $boxUom;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Bulk Import Co',
            'code' => 'BIC-01',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $this->uom = Uom::firstOrCreate(['code' => 'PCS'], [
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'is_active' => true,
        ]);

        $this->boxUom = Uom::firstOrCreate(['code' => 'BOX'], [
            'company_id' => $this->company->id,
            'name' => 'Box',
            'is_active' => true,
        ]);
    }

    public function test_products_template_includes_new_catalog_columns(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'products'));
        $response->assertOk();
        $content = $response->streamedContent();

        foreach ([
            'part_number', 'product_type', 'brand', 'category', 'sub_category',
            'alternate_uom_code', 'conversion_factor', 'track_serial', 'track_batch',
            'specification', 'warranty', 'catalog_link',
        ] as $header) {
            $this->assertStringContainsString($header, $content);
        }

        $this->assertStringNotContainsString('color_variant', $content);
        $this->assertStringNotContainsString('sender_warranty_months', $content);
    }

    public function test_parties_template_includes_new_party_columns(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.bulk-import.template', 'parties'));
        $response->assertOk();
        $content = $response->streamedContent();

        foreach ([
            'trade_name', 'opened_on', 'reference_by', 'udyam_number', 'website', 'location_link',
            'address_line_1', 'city', 'credit_status', 'credit_period_basis',
            'bank_account_holder', 'bank_ifsc',
        ] as $header) {
            $this->assertStringContainsString($header, $content);
        }
    }

    public function test_products_import_creates_type_brand_category_subcategory_and_fields(): void
    {
        $import = new ProductsImport($this->company->id);
        $import->collection(collect([
            [
                'name' => 'Imported Scanner',
                'part_number' => 'PN-SCAN-100',
                'model' => 'SCN-X',
                'product_type' => 'Hardware',
                'brand' => 'Acme Brand',
                'category' => 'Scanners',
                'sub_category' => 'Handheld',
                'uom_code' => 'PCS',
                'alternate_uom_code' => 'BOX',
                'conversion_factor' => 10,
                'supply_type' => 'goods',
                'hsn_code' => '847160',
                'tax_rate' => 18,
                'track_serial' => 1,
                'track_batch' => 0,
                'height_cm' => 5,
                'length_cm' => 20,
                'width_cm' => 10,
                'net_weight' => 0.8,
                'weight_unit' => 'KG',
                'purchase_price' => 1000,
                'trade_price' => 1200,
                'selling_price' => 1500,
                'mrp' => 1800,
                'purchase_price_tax_basis' => 'exclusive',
                'costing_method' => 'avg_cost',
                'market_valuation_method' => 'avg_price',
                'min_stock' => 2,
                'reorder_level' => 5,
                'specification' => 'USB-C',
                'warranty' => '12 months',
                'catalog_link' => 'https://example.com/scanner',
                'is_active' => 1,
            ],
        ]));

        $this->assertSame(0, $import->skipped, json_encode($import->errors));
        $this->assertSame(1, $import->created);

        $product = Product::query()->where('sku', 'PN-SCAN-100')->first();
        $this->assertNotNull($product);
        $this->assertSame(\App\Support\NameCase::format('SCN-X'), $product->model);
        $this->assertSame('serial', $product->tracking_type);
        $this->assertSame(12, $product->warranty_months);
        $this->assertSame($this->boxUom->id, $product->alternate_uom_id);
        $this->assertEquals(10, (float) $product->conversion_factor);

        $this->assertDatabaseHas('product_types', [
            'company_id' => $this->company->id,
            'name' => 'Hardware',
        ]);
        $this->assertDatabaseHas('brands', ['name' => 'Acme Brand']);
        $this->assertDatabaseHas('categories', ['name' => 'Scanners']);
        $this->assertDatabaseHas('sub_categories', ['name' => 'Handheld']);

        $type = ProductType::query()->where('name', 'Hardware')->first();
        $brand = Brand::query()->where('name', 'Acme Brand')->first();
        $category = Category::query()->where('name', 'Scanners')->first();
        $sub = SubCategory::query()->where('name', 'Handheld')->first();

        $this->assertSame($type->id, $product->product_type_id);
        $this->assertSame($brand->id, $product->brand_id);
        $this->assertSame($category->id, $product->category_id);
        $this->assertSame($sub->id, $product->sub_category_id);
        $this->assertSame($category->id, $sub->category_id);
    }

    public function test_parties_import_fills_new_fields_address_and_bank(): void
    {
        $this->actingAs($this->user);

        $import = new PartiesImport($this->company->id);
        $import->collection(collect([
            [
                'name' => 'North Distributors',
                'code' => 'ND-001',
                'party_type' => 'sundry_debtors',
                'classification' => 'Wholesale',
                'trade_name' => 'North Dist Pvt Ltd',
                'gstin' => '29AABCU9603R1ZM',
                'phone' => '9876543210',
                'email' => 'north@example.com',
                'opened_on' => '2024-04-01',
                'reference_by' => 'Walk-in',
                'udyam_number' => 'UDYAM-KR-01-0000001',
                'website' => 'https://north.example',
                'location_link' => 'https://maps.example/north',
                'is_active' => 1,
                'area' => 'North Zone',
                'route' => 'Route N1',
                'address_line_1' => '12 Ring Road',
                'address_line_2' => 'Block B',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560001',
                'credit_limit' => 75000,
                'credit_days' => 45,
                'credit_status' => 'open',
                'credit_period_basis' => 'invoice_date',
                'interest_rate' => 18,
                'contact_name' => 'Anita Rao',
                'contact_role' => 'Accounts',
                'contact_phone' => '9888777666',
                'contact_email' => 'anita@north.example',
                'bank_account_holder' => 'North Dist Pvt Ltd',
                'bank_name' => 'HDFC Bank',
                'bank_branch' => 'MG Road',
                'bank_account_number' => '50200099998888',
                'bank_account_type' => 'Current',
                'bank_ifsc' => 'HDFC0001234',
            ],
        ]));

        $this->assertSame(0, $import->skipped, json_encode($import->errors));
        $this->assertSame(1, $import->created);

        $party = Customer::query()->where('code', 'ND-001')->first();
        $this->assertNotNull($party);
        $this->assertSame('North Dist Pvt Ltd', $party->trade_name);
        $this->assertSame('UDYAM-KR-01-0000001', $party->udyam_number);
        $this->assertSame('invoice_date', $party->credit_period_basis);
        $this->assertSame('2024-04-01', $party->opened_on?->toDateString());

        $address = PartyAddress::query()->where('customer_id', $party->id)->first();
        $this->assertNotNull($address);
        $this->assertSame('12 Ring Road', $address->address_line_1);
        $this->assertSame('Bengaluru', $address->city);

        $bank = PartyBankAccount::query()->where('customer_id', $party->id)->first();
        $this->assertNotNull($bank);
        $this->assertSame('50200099998888', $bank->account_number);
        $this->assertSame('HDFC0001234', $bank->ifsc_code);
    }
}
