<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
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
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductHideAndUnhideTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Company $company;
    protected Uom $uom;
    protected Brand $brand;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $superRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $permissions = [
            'masters.view',
            'masters.create',
            'masters.edit',
            'masters.delete',
            'masters.manage',
            'products.view',
            'products.create',
            'products.edit',
            'products.manage',
            'products.view-hidden',
            'products.hide',
            'products.unhide',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $superRole->syncPermissions(Permission::all());

        $this->company = Company::create([
            'name' => 'Apex Tech Solutions',
            'code' => 'ATS-01',
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->superAdmin->assignRole($superRole);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $this->uom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Apex Brand',
            'code' => 'AB-01',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brand->id,
            'name' => 'Apex Electronics',
            'code' => 'AE-01',
            'is_active' => true,
        ]);
    }

    protected function createProduct(array $attributes = []): Product
    {
        static $counter = 100;
        $counter++;

        return Product::create(array_merge([
            'company_id' => $this->company->id,
            'name' => "Apex Test Product {$counter}",
            'sku' => "SKU-TEST-{$counter}",
            'serial_no' => "SN-TEST-{$counter}",
            'base_uom_id' => $this->uom->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'selling_price' => 1500.00,
            'is_active' => true,
            'is_hidden' => false,
        ], $attributes));
    }

    public function test_visible_product_can_be_hidden_and_preserves_active_status(): void
    {
        $product = $this->createProduct(['name' => 'Standard Gaming Mouse', 'is_active' => true, 'is_hidden' => false]);

        $response = $this->actingAs($this->superAdmin)
            ->patchJson(route('masters.products.hide', $product));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $product->refresh();
        $this->assertTrue($product->is_hidden, 'Product should be marked as hidden.');
        $this->assertTrue($product->is_active, 'Active status must remain unchanged when hiding a product.');
    }

    public function test_inactive_product_can_be_hidden_and_preserves_inactive_status(): void
    {
        $product = $this->createProduct(['name' => 'Legacy Trackball', 'is_active' => false, 'is_hidden' => false]);

        $response = $this->actingAs($this->superAdmin)
            ->patchJson(route('masters.products.hide', $product));

        $response->assertOk();

        $product->refresh();
        $this->assertTrue($product->is_hidden);
        $this->assertFalse($product->is_active, 'Inactive status must remain unchanged when hiding a product.');
    }

    public function test_hidden_products_disappear_from_regular_product_listing(): void
    {
        $visibleProduct = $this->createProduct(['name' => 'Visible Wireless Keyboard', 'is_hidden' => false]);
        $hiddenProduct = $this->createProduct(['name' => 'Discontinued Mechanical Keyboard', 'is_hidden' => true]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('masters.products.index'));

        $response->assertOk()
            ->assertSee($visibleProduct->name)
            ->assertDontSee($hiddenProduct->name);
    }

    public function test_hidden_products_can_be_found_through_explicit_search_and_show_hidden_badge(): void
    {
        $hiddenProduct = $this->createProduct([
            'name' => 'Discontinued Mechanical Keyboard',
            'sku' => 'DMK-9900',
            'is_active' => true,
            'is_hidden' => true,
        ]);

        // Search by Product Name
        $responseName = $this->actingAs($this->superAdmin)
            ->get(route('masters.products.index', ['search' => 'Discontinued Mechanical']));

        $responseName->assertOk()
            ->assertSee($hiddenProduct->name)
            ->assertSee('Hidden Product');

        // Search by SKU / Part Number
        $responseSku = $this->actingAs($this->superAdmin)
            ->get(route('masters.products.index', ['search' => 'DMK-9900']));

        $responseSku->assertOk()
            ->assertSee($hiddenProduct->name)
            ->assertSee('Hidden Product')
            ->assertSee('data-unhide-product="' . $hiddenProduct->id . '"', false);
    }

    public function test_product_both_hidden_and_inactive_displays_both_badges_in_search(): void
    {
        $product = $this->createProduct([
            'name' => 'Old Obsolete Cable',
            'sku' => 'OOC-1234',
            'is_active' => false,
            'is_hidden' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('masters.products.index', ['search' => 'Old Obsolete Cable']));

        $response->assertOk()
            ->assertSee($product->name)
            ->assertSee('Hidden Product')
            ->assertSee('Inactive Product');
    }

    public function test_hidden_products_can_be_unhidden_and_return_to_regular_listing(): void
    {
        $product = $this->createProduct(['name' => 'Re-released Speaker', 'is_active' => true, 'is_hidden' => true]);

        // Verify not in regular listing
        $listingBefore = $this->actingAs($this->superAdmin)->get(route('masters.products.index'));
        $listingBefore->assertDontSee($product->name);

        // Unhide
        $response = $this->actingAs($this->superAdmin)
            ->patchJson(route('masters.products.unhide', $product));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $product->refresh();
        $this->assertFalse($product->is_hidden, 'Product should now be visible (is_hidden = false).');
        $this->assertTrue($product->is_active, 'Active status must remain intact.');

        // Verify it returns to regular listing
        $listingAfter = $this->actingAs($this->superAdmin)->get(route('masters.products.index'));
        $listingAfter->assertSee($product->name);
    }

    public function test_visibility_filter_options(): void
    {
        $visibleActive = $this->createProduct(['name' => 'Visible Active Monitor', 'is_active' => true, 'is_hidden' => false]);
        $visibleInactive = $this->createProduct(['name' => 'Visible Inactive Monitor', 'is_active' => false, 'is_hidden' => false]);
        $hiddenActive = $this->createProduct(['name' => 'Hidden Active Monitor', 'is_active' => true, 'is_hidden' => true]);
        $hiddenInactive = $this->createProduct(['name' => 'Hidden Inactive Monitor', 'is_active' => false, 'is_hidden' => true]);

        // 1. All Visible (default)
        $resAll = $this->actingAs($this->superAdmin)->get(route('masters.products.index'));
        $resAll->assertSee($visibleActive->name)
            ->assertSee($visibleInactive->name)
            ->assertDontSee($hiddenActive->name)
            ->assertDontSee($hiddenInactive->name);

        // 2. Active (visible active only)
        $resActive = $this->actingAs($this->superAdmin)->get(route('masters.products.index', ['status' => 'active']));
        $resActive->assertSee($visibleActive->name)
            ->assertDontSee($visibleInactive->name)
            ->assertDontSee($hiddenActive->name)
            ->assertDontSee($hiddenInactive->name);

        // 3. Inactive (visible inactive only)
        $resInactive = $this->actingAs($this->superAdmin)->get(route('masters.products.index', ['status' => 'inactive']));
        $resInactive->assertDontSee($visibleActive->name)
            ->assertSee($visibleInactive->name)
            ->assertDontSee($hiddenActive->name)
            ->assertDontSee($hiddenInactive->name);

        // 4. Hidden (both active and inactive hidden)
        $resHidden = $this->actingAs($this->superAdmin)->get(route('masters.products.index', ['status' => 'hidden']));
        $resHidden->assertDontSee($visibleActive->name)
            ->assertDontSee($visibleInactive->name)
            ->assertSee($hiddenActive->name)
            ->assertSee($hiddenInactive->name);
    }

    public function test_hidden_products_excluded_from_transaction_dropdowns_by_default(): void
    {
        $visibleProduct = $this->createProduct(['name' => 'Available Office Chair', 'is_hidden' => false]);
        $hiddenProduct = $this->createProduct(['name' => 'Archived Office Chair', 'is_hidden' => true]);

        // Order search products endpoint
        $searchRes = $this->actingAs($this->superAdmin)
            ->getJson(route('orders.search.products', ['q' => 'Office Chair']));

        $searchRes->assertOk();
        $ids = collect($searchRes->json())->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains($visibleProduct->id, $ids);
        $this->assertNotContains($hiddenProduct->id, $ids, 'Hidden products must not appear in search dropdown by default.');

        // Authorized request with include_hidden=1
        $authorizedRes = $this->actingAs($this->superAdmin)
            ->getJson(route('orders.search.products', ['q' => 'Office Chair', 'include_hidden' => 1]));

        $authorizedRes->assertOk();
        $authorizedIds = collect($authorizedRes->json())->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains($hiddenProduct->id, $authorizedIds, 'Authorized include_hidden request should find hidden product.');
    }

    public function test_historical_transactions_containing_hidden_products_remain_intact(): void
    {
        $customerType = CustomerType::create([
            'company_id' => $this->company->id,
            'name' => 'Retail',
            'code' => 'RET-01',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'name' => 'Acme Corporation',
            'code' => 'ACME-001',
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        $product = $this->createProduct(['name' => 'Historical Component', 'is_hidden' => false]);

        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Depot',
            'code' => 'CD-01',
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-HIST-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 1770.00,
            'balance_due' => 1770.00,
            'status' => 'issued',
            'payment_status' => 'unpaid',
            'subtotal' => 1500.00,
            'tax_amount' => 270.00,
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
        ]);

        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1500.00,
            'tax_rate' => 18,
            'tax_amount' => 270.00,
            'line_total' => 1770.00,
            'total_amount' => 1770.00,
            'uom_id' => $this->uom->id,
        ]);

        // Hide the product
        $product->hide();
        $this->assertTrue($product->fresh()->is_hidden);

        // Historical invoice item relation must remain intact
        $reloadedItem = InvoiceItem::find($item->id);
        $this->assertNotNull($reloadedItem->product, 'Historical invoice item product relation must load successfully.');
        $this->assertEquals($product->id, $reloadedItem->product->id);
        $this->assertEquals('Historical Component', $reloadedItem->product->name);
    }

    public function test_permissions_enforced_for_hide_and_unhide(): void
    {
        $viewerRole = Role::firstOrCreate(['name' => 'viewer-only', 'guard_name' => 'web']);
        $viewerRole->syncPermissions(['products.view']);

        $restrictedUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $restrictedUser->assignRole($viewerRole);

        $product = $this->createProduct(['name' => 'Protected Product', 'is_hidden' => false]);

        // Hide without permission -> 403
        $hideResponse = $this->actingAs($restrictedUser)
            ->patchJson(route('masters.products.hide', $product));

        $hideResponse->assertForbidden();
        $this->assertFalse($product->fresh()->is_hidden);

        // Hide product as superAdmin
        $product->hide();

        // Unhide without permission -> 403
        $unhideResponse = $this->actingAs($restrictedUser)
            ->patchJson(route('masters.products.unhide', $product));

        $unhideResponse->assertForbidden();
        $this->assertTrue($product->fresh()->is_hidden);
    }

    public function test_unauthorized_user_cannot_view_hidden_products_even_with_search(): void
    {
        $viewerRole = Role::firstOrCreate(['name' => 'viewer-only-2', 'guard_name' => 'web']);
        $viewerRole->syncPermissions(['products.view']);

        $restrictedUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $restrictedUser->assignRole($viewerRole);

        $hiddenProduct = $this->createProduct([
            'name' => 'Secret Prototype Device',
            'is_hidden' => true,
        ]);

        // Restricted user searching for secret prototype should NOT see it
        $response = $this->actingAs($restrictedUser)
            ->get(route('masters.products.index', ['search' => 'Secret Prototype']));

        $response->assertOk()
            ->assertDontSee($hiddenProduct->name);
    }
}
