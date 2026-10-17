<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductDeleteAndColumnsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Uom $uom;
    protected Brand $brand;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Test Tech Ltd',
            'code' => 'TTL-01',
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

        $this->uom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Lenovo',
            'code' => 'LEN',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Laptops',
            'code' => 'LAP',
            'is_active' => true,
        ]);
        $this->boxUom = Uom::create([
            'name' => 'Box',
            'code' => 'BOX',
            'is_active' => true,
        ]);
    }

    protected function createProduct(array $overrides = []): Product
    {
        static $counter = 100;
        $counter++;

        return Product::create(array_merge([
            'company_id' => $this->company->id,
            'name' => "Product {$counter}",
            'sku' => "SKU-{$counter}",
            'serial_no' => "SN-{$counter}",
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'tax_rate' => 18,
            'is_active' => true,
        ], $overrides));
    }

    public function test_products_table_has_series_model_product_type_and_soft_deletes_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'series'));
        $this->assertTrue(Schema::hasColumn('products', 'model'));
        $this->assertTrue(Schema::hasColumn('products', 'product_type'));
        $this->assertTrue(Schema::hasColumn('products', 'deleted_at'));
    }

    public function test_can_create_product_with_series_model_and_product_type(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'ThinkPad T14 Gen 4',
            'sku' => 'SKU-TP-T14-G4',
            'series' => 'ThinkPad T Series',
            'model' => 'T14 Gen 4 Intel',
            'product_type' => 'Business Laptop',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'selling_price' => 95000,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'SKU-TP-T14-G4')->first();
        $this->assertNotNull($product);
        $this->assertSame('ThinkPad T Series', $product->series);
        $this->assertSame('T14 Gen 4 Intel', $product->model);
        $this->assertSame('T14 Gen 4 Intel', $product->models); // alias accessor
        $this->assertSame('Business Laptop', $product->product_type);
    }

    public function test_can_update_product_series_model_and_product_type(): void
    {
        $product = $this->createProduct([
            'name' => 'Latitude 7420',
            'sku' => 'SKU-LAT-7420',
            'series' => 'Latitude',
            'model' => '7420',
            'product_type' => 'Laptop',
        ]);

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), [
            'name' => 'Latitude 7420 Updated',
            'sku' => 'SKU-LAT-7420',
            'series' => 'Latitude 7000 Series',
            'model' => '7420 Ultrabook',
            'product_type' => 'Premium Laptop',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_id' => $this->boxUom->id,
            'conversion_factor' => 10,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertSame('Latitude 7000 Series', $product->series);
        $this->assertSame('7420 Ultrabook', $product->model);
        $this->assertSame('Premium Laptop', $product->product_type);
    }

    public function test_products_listing_displays_new_columns_and_fallback_dash(): void
    {
        $this->createProduct([
            'name' => 'Galaxy S24 Ultra',
            'sku' => 'SKU-S24U',
            'serial_no' => 'SN-S24U-001',
            'series' => 'Galaxy S',
            'model' => 'SM-S928B',
            'product_type' => 'Smartphone',
        ]);

        $this->createProduct([
            'name' => 'Generic USB Cable',
            'sku' => 'SKU-USB-CABLE',
            'serial_no' => 'SN-USB-002',
            'series' => null,
            'model' => null,
            'product_type' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.products.index'));
        $response->assertOk();

        // Check headers
        $response->assertSee('Models');
        $response->assertSee('Product Type');

        // Check values for product with details
        $response->assertSee('SM-S928B');
        $response->assertSee('Smartphone');

        // Check fallback dash '—' appears
        $response->assertSee('—');
    }

    public function test_sorting_by_series_model_and_product_type_works(): void
    {
        $this->createProduct([
            'name' => 'Alpha Device',
            'sku' => 'SKU-A',
            'series' => 'Series Z',
            'model' => 'Model 9',
            'product_type' => 'Type Beta',
        ]);

        $this->createProduct([
            'name' => 'Beta Device',
            'sku' => 'SKU-B',
            'series' => 'Series A',
            'model' => 'Model 1',
            'product_type' => 'Type Alpha',
        ]);

        // Sort by series asc
        $responseSeries = $this->actingAs($this->user)->get(route('masters.products.index', [
            'sort' => 'series',
            'direction' => 'asc',
        ]));
        $responseSeries->assertOk();
        $responseSeries->assertSeeInOrder(['Model 1', 'Model 9']);

        // Sort by model asc
        $responseModel = $this->actingAs($this->user)->get(route('masters.products.index', [
            'sort' => 'model',
            'direction' => 'asc',
        ]));
        $responseModel->assertOk();
        $responseModel->assertSeeInOrder(['Model 1', 'Model 9']);

        // Sort by models (alias) asc
        $responseModels = $this->actingAs($this->user)->get(route('masters.products.index', [
            'sort' => 'models',
            'direction' => 'asc',
        ]));
        $responseModels->assertOk();
        $responseModels->assertSeeInOrder(['Model 1', 'Model 9']);

        // Sort by product_type asc
        $responseType = $this->actingAs($this->user)->get(route('masters.products.index', [
            'sort' => 'product_type',
            'direction' => 'asc',
        ]));
        $responseType->assertOk();
        $responseType->assertSeeInOrder(['Type Alpha', 'Type Beta']);
    }

    public function test_searching_by_series_model_and_product_type_works(): void
    {
        $this->createProduct([
            'name' => 'EliteBook 840',
            'sku' => 'SKU-HP-840',
            'series' => 'HP EliteBook Series',
            'model' => '840 G10',
            'product_type' => 'Enterprise Laptop',
        ]);

        $this->createProduct([
            'name' => 'MacBook Pro 16',
            'sku' => 'SKU-MBP-16',
            'series' => 'Apple Silicon',
            'model' => 'M3 Max',
            'product_type' => 'Workstation Laptop',
        ]);

        // Search by series
        $resSeries = $this->actingAs($this->user)->get(route('masters.products.index', ['search' => 'EliteBook Series']));
        $resSeries->assertOk();
        $resSeries->assertSee('ELITEBOOK 840');
        $resSeries->assertDontSee('MACBOOK PRO 16');

        // Search by model
        $resModel = $this->actingAs($this->user)->get(route('masters.products.index', ['search' => 'M3 Max']));
        $resModel->assertOk();
        $resModel->assertSee('MACBOOK PRO 16');
        $resModel->assertDontSee('ELITEBOOK 840');

        // Search by product_type
        $resType = $this->actingAs($this->user)->get(route('masters.products.index', ['search' => 'Workstation Laptop']));
        $resType->assertOk();
        $resType->assertSee('MACBOOK PRO 16');
        $resType->assertDontSee('ELITEBOOK 840');
    }

    public function test_delete_button_and_confirmation_modal_appear_in_listing(): void
    {
        $product = $this->createProduct([
            'name' => 'Test Product For Delete',
            'sku' => 'SKU-DEL-01',
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.products.index'));
        $response->assertOk();

        // Delete button
        $response->assertSee('Delete');
        $response->assertSee('data-delete-product="'.$product->id.'"', false);

        // Confirmation modal requirements
        $response->assertSee('Delete Product');
        $response->assertSee('Are you sure you want to delete this product? This action cannot be undone.');
        $response->assertSee('Cancel');
    }

    public function test_product_deletion_soft_deletes_by_id_and_removes_from_listing(): void
    {
        $product = $this->createProduct([
            'name' => 'Product To Be Deleted',
            'sku' => 'SKU-DELETE-ME',
        ]);

        $response = $this->actingAs($this->user)->delete(route('masters.products.destroy', $product), [], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        // Assert soft deleted
        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);

        // Assert not present in normal query
        $this->assertNull(Product::find($product->id));
        $this->assertNotNull(Product::withTrashed()->find($product->id));

        // Listing no longer shows deleted product
        $listingResponse = $this->actingAs($this->user)->get(route('masters.products.index'));
        $listingResponse->assertDontSee('SKU-DELETE-ME');
    }

    public function test_product_deletion_works_for_both_active_and_inactive_products(): void
    {
        $activeProduct = $this->createProduct([
            'name' => 'Active Product',
            'sku' => 'SKU-ACT-01',
            'is_active' => true,
        ]);

        $inactiveProduct = $this->createProduct([
            'name' => 'Inactive Product',
            'sku' => 'SKU-INACT-01',
            'is_active' => false,
        ]);

        $res1 = $this->actingAs($this->user)->delete(route('masters.products.destroy', $activeProduct), [], ['Accept' => 'application/json']);
        $res1->assertOk();
        $this->assertSoftDeleted('products', ['id' => $activeProduct->id]);

        $res2 = $this->actingAs($this->user)->delete(route('masters.products.destroy', $inactiveProduct), [], ['Accept' => 'application/json']);
        $res2->assertOk();
        $this->assertSoftDeleted('products', ['id' => $inactiveProduct->id]);
    }

    public function test_historical_records_remain_intact_when_product_is_deleted(): void
    {
        $customerType = CustomerType::create([
            'company_id' => $this->company->id,
            'name' => 'Vendor',
            'code' => 'VEN',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'BR-01',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'name' => 'Acme Vendor',
            'code' => 'CUST-001',
            'is_active' => true,
        ]);

        $product = $this->createProduct([
            'name' => 'Historical Product',
            'sku' => 'SKU-HIST-01',
            'purchase_price' => 500,
        ]);

        // Create historical purchase invoice and invoice item
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'invoice_no' => 'PI-HIST-001',
            'invoice_date' => now()->toDateString(),
            'status' => 'posted',
            'subtotal' => 5000,
            'grand_total' => 5000,
        ]);

        $invoiceItem = PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 10,
            'unit_cost' => 500,
            'line_total' => 5000,
        ]);

        // Create stock movement
        $movement = StockMovement::create([
            'product_id' => $product->id,
            'uom_id' => $this->uom->id,
            'type' => 'purchase',
            'quantity' => 10,
            'balance_after' => 10,
            'notes' => 'Initial purchase stock',
        ]);

        $this->assertTrue($product->hasHistoricalRecords());

        // Perform soft delete
        $response = $this->actingAs($this->user)->delete(route('masters.products.destroy', $product), [], [
            'Accept' => 'application/json',
        ]);
        $response->assertOk();

        // Verify product is soft deleted
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        // Verify historical invoice, invoice item, and stock movement are untouched!
        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('purchase_invoice_items', ['id' => $invoiceItem->id, 'product_id' => $product->id]);
        $this->assertDatabaseHas('stock_movements', ['id' => $movement->id, 'product_id' => $product->id]);

        // Verify relationship continues to load withTrashed
        $invoiceItem->refresh();
        $this->assertNotNull($invoiceItem->product);
        $this->assertSame($product->id, $invoiceItem->product->id);
        $this->assertSame('HISTORICAL PRODUCT', $invoiceItem->product->name);

        $movement->refresh();
        $this->assertNotNull($movement->product);
        $this->assertSame($product->id, $movement->product->id);
    }

    public function test_standard_web_redirect_delete_succeeds(): void
    {
        $product = $this->createProduct([
            'name' => 'Web Delete Product',
            'sku' => 'SKU-WEB-DEL',
        ]);

        $response = $this->actingAs($this->user)->delete(route('masters.products.destroy', $product));
        $response->assertRedirect(route('masters.products.index'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_unauthorized_user_cannot_delete_product(): void
    {
        $restrictedUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        // No roles or permissions assigned

        $product = $this->createProduct([
            'name' => 'Protected Product',
            'sku' => 'SKU-PROT-01',
        ]);

        $response = $this->actingAs($restrictedUser)->delete(route('masters.products.destroy', $product), [], [
            'Accept' => 'application/json',
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    public function test_deleting_non_existent_product_returns_404(): void
    {
        $response = $this->actingAs($this->user)->delete('/masters/products/999999', [], [
            'Accept' => 'application/json',
        ]);
        $response->assertNotFound();
    }
}
