<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductTypeModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $unauthorizedUser;
    protected Company $company;
    protected Uom $baseUom;
    protected Uom $altUom;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard permissions
        foreach ([
            'masters.view',
            'masters.create',
            'masters.edit',
            'masters.delete',
            'masters.manage',
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
        ] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'guest-user', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Acme Test Corp',
            'code' => 'ACME',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin Test User',
            'email' => 'admin.test@example.com',
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($superAdminRole);

        $this->unauthorizedUser = User::factory()->create([
            'name' => 'Unauthorized User',
            'email' => 'unauth.test@example.com',
            'company_id' => $this->company->id,
        ]);
        $this->unauthorizedUser->assignRole($userRole);

        $this->baseUom = Uom::create([
            'name' => 'Pieces',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->altUom = Uom::create([
            'name' => 'Box',
            'code' => 'BOX',
            'is_active' => true,
        ]);
    }

    public function test_sidebar_contains_product_types_under_masters(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Product Types');
        $response->assertSee(route('masters.product-types.index'));
    }

    public function test_product_types_page_renders_with_listing_and_metrics(): void
    {
        // Seed a sample product type
        ProductType::create([
            'name' => 'Finished Goods',
            'code' => 'FG',
            'description' => 'Manufactured inventory ready for sale',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.product-types.index'));

        $response->assertStatus(200);
        $response->assertViewIs('masters.product-types.index');
        $response->assertSee('Product Types');
        $response->assertSee('+ Add Product Type');
        $response->assertSee('Finished Goods');
        $response->assertSee('FG');
        $response->assertSee('Manufactured inventory ready for sale');
        $response->assertSee('Total Types');
        $response->assertSee('Active Types');
    }

    public function test_can_create_new_product_type_with_title_case_formatting(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.product-types.store'), [
                'name' => 'electronic spare parts',
                'description' => 'Replacement parts and electronic accessories',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('masters.product-types.index'));

        // Verify Title Case formatting was automatically applied
        $created = ProductType::whereRaw('LOWER(name) = ?', ['electronic spare parts'])->first();
        $this->assertNotNull($created);
        $this->assertEquals('Electronic Spare Parts', $created->name);
        $this->assertEquals('Replacement parts and electronic accessories', $created->description);
        $this->assertTrue($created->is_active);
    }

    public function test_duplicate_product_type_names_are_rejected(): void
    {
        ProductType::create([
            'name' => 'Raw Materials',
            'code' => 'RM-01',
            'is_active' => true,
        ]);

        // Attempt to create duplicate with lowercase and leading/trailing spaces
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('masters.product-types.store'), [
                'name' => '   raw materials   ',
                'description' => 'Duplicate attempt',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
        $this->assertStringContainsString('already exists', $response->json('message'));
    }

    public function test_can_search_product_types(): void
    {
        ProductType::create(['name' => 'Consumer Electronics', 'code' => 'CE-01', 'is_active' => true]);
        ProductType::create(['name' => 'Automotive Parts', 'code' => 'AP-01', 'is_active' => true]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.product-types.index', ['search' => 'Consumer']));

        $response->assertStatus(200);
        $response->assertSee('Consumer Electronics');
        $response->assertDontSee('Automotive Parts');
    }

    public function test_can_view_product_type_details_via_json(): void
    {
        $type = ProductType::create([
            'name' => 'Industrial Hardware',
            'code' => 'IND-HW',
            'description' => 'Heavy industrial fasteners and nuts',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('masters.product-types.show', $type));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'item' => [
                'id' => $type->id,
                'name' => 'Industrial Hardware',
                'code' => 'IND-HW',
                'description' => 'Heavy industrial fasteners and nuts',
                'is_active' => true,
            ],
        ]);
    }

    public function test_can_edit_and_update_product_type(): void
    {
        $type = ProductType::create([
            'name' => 'Old Type Name',
            'code' => 'OLD-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('masters.product-types.update', $type), [
                'name' => 'updated type name',
                'description' => 'Newly updated description',
                'is_active' => false,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $type->refresh();
        $this->assertEquals('Updated Type Name', $type->name);
        $this->assertEquals('Newly updated description', $type->description);
        $this->assertFalse($type->is_active);
    }

    public function test_can_delete_unused_product_type(): void
    {
        $type = ProductType::create([
            'name' => 'Temporary Type',
            'code' => 'TEMP-01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.product-types.destroy', $type));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'archived' => false]);
        $this->assertDatabaseMissing('product_types', ['id' => $type->id]);
    }

    public function test_referenced_product_type_is_safely_deactivated_instead_of_deleted(): void
    {
        $type = ProductType::create([
            'name' => 'Standard Goods',
            'code' => 'SG-01',
            'is_active' => true,
        ]);

        // Create product referencing this type
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Dell Latitude Laptop',
            'sku' => 'DELL-LAT-001',
            'serial_no' => 'PRD-TEST-0001',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 10,
            'product_type_id' => $type->id,
            'product_type' => $type->name,
            'height_cm' => 2.0,
            'length_cm' => 35.0,
            'width_cm' => 24.0,
            'net_weight' => 1.5,
            'is_active' => true,
        ]);

        // Attempt deletion
        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('masters.product-types.destroy', $type));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'archived' => true]);

        // Record must still exist in DB to preserve product association, but deactivated
        $type->refresh();
        $this->assertFalse($type->is_active);
        $this->assertDatabaseHas('product_types', ['id' => $type->id]);

        $product->refresh();
        $this->assertEquals($type->id, $product->product_type_id);
    }

    public function test_create_product_page_does_not_contain_product_type_dropdown_or_add_button(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.products.create'));

        $response->assertStatus(200);
        // The Create Product form must NOT contain the inline product type dropdown or + Add Product Type button
        $response->assertDontSee('+ Add Product Type');
        $response->assertDontSee('product_type_search_input');
    }

    public function test_can_create_product_without_inline_product_type_field(): void
    {
        $payload = [
            'name' => 'Logitech Wireless Mouse M185',
            'sku' => 'LOGI-M185',
            'model' => 'M185',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 10,
            'height_cm' => 4.0,
            'length_cm' => 10.0,
            'width_cm' => 6.0,
            'net_weight' => 0.15,
            'selling_price' => 799.00,
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('masters.products.store'), $payload);

        $response->assertRedirect(route('masters.products.index'));

        $this->assertDatabaseHas('products', [
            'sku' => 'LOGI-M185',
        ]);
    }

    public function test_edit_product_page_allows_assigning_product_type(): void
    {
        $type = ProductType::create(['name' => 'Computer Peripherals', 'code' => 'COMP-PER', 'is_active' => true]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Mechanical Keyboard',
            'sku' => 'MECH-KB-01',
            'serial_no' => 'PRD-TEST-0002',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 5,
            'height_cm' => 3.5,
            'length_cm' => 44.0,
            'width_cm' => 13.0,
            'net_weight' => 0.9,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.products.edit', $product));

        $response->assertStatus(200);
        $response->assertSee('Computer Peripherals');
        $response->assertSee('product_type_id');
    }

    public function test_can_quick_assign_product_type_via_endpoint(): void
    {
        $type = ProductType::create(['name' => 'Networking Hardware', 'code' => 'NET-HW', 'is_active' => true]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Cisco Router 2901',
            'sku' => 'CISCO-RTR-01',
            'serial_no' => 'PRD-TEST-0003',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'height_cm' => 9.0,
            'length_cm' => 45.0,
            'width_cm' => 40.0,
            'net_weight' => 7.0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->patchJson(route('masters.products.assign-type', $product), [
                'product_type_id' => $type->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $product->refresh();
        $this->assertEquals($type->id, $product->product_type_id);
        $this->assertEquals('Networking Hardware', $product->product_type);
    }

    public function test_product_type_modal_layout_is_responsive_and_aligned_to_main_content(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('masters.product-types.index'));

        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Verify modal positioning respects the main content area (never behind sidebar)
        $this->assertStringContainsString('fixed inset-y-0 right-0 z-50', $content);
        $this->assertStringContainsString("left-0 lg:left-64", $content);
        $this->assertStringContainsString("left-0 lg:left-[72px]", $content);

        // 2. Verify responsive max dimensions (max 700px width and max 85vh height)
        $this->assertStringContainsString('max-w-[700px]', $content);
        $this->assertStringContainsString('max-h-[85vh]', $content);

        // 3. Verify sticky header and sticky footer structure
        $this->assertStringContainsString('sticky top-0 z-10', $content);
        $this->assertStringContainsString('sticky bottom-0 z-10', $content);
        $this->assertStringContainsString('flex-1 overflow-y-auto', $content);

        // 4. Verify essential form controls are present in the modal
        $this->assertStringContainsString('Product Type Name', $content);
        $this->assertStringContainsString('form_name', $content);
        $this->assertStringContainsString('form_code', $content);
        $this->assertStringContainsString('form_description', $content);
        $this->assertStringContainsString('closeFormModal()', $content);
        $this->assertStringContainsString('submitForm()', $content);
    }
}

