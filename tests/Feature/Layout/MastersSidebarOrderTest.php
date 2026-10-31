<?php

namespace Tests\Feature\Layout;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MastersSidebarOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $clientAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $clientAdminRole = Role::firstOrCreate(['name' => 'client-admin', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@dms.test',
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->clientAdmin = User::factory()->create([
            'name' => 'Client Admin',
            'email' => 'clientadmin@dms.test',
        ]);
        $this->clientAdmin->assignRole($clientAdminRole);
    }

    public function test_masters_sidebar_has_products_before_product_types(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check relative positions of menu items
        $posBrands = strpos($content, '>Brands</span>');
        $posCategories = strpos($content, '>Categories</span>');
        $posSubCategories = strpos($content, '>Sub Categories</span>');
        $posProducts = strpos($content, '>Products</span>');
        $posProductTypes = strpos($content, '>Product Types</span>');
        $posParties = strpos($content, '>Parties</span>');
        $posPriceList = strpos($content, '>Price List</span>');
        $posBulkImport = strpos($content, '>Bulk Import</span>');

        $this->assertNotFalse($posBrands, 'Brands menu item should exist');
        $this->assertNotFalse($posCategories, 'Categories menu item should exist');
        $this->assertNotFalse($posSubCategories, 'Sub Categories menu item should exist');
        $this->assertNotFalse($posProducts, 'Products menu item should exist');
        $this->assertNotFalse($posProductTypes, 'Product Types menu item should exist');
        $this->assertNotFalse($posParties, 'Parties menu item should exist');
        $this->assertNotFalse($posPriceList, 'Price List menu item should exist');
        $this->assertNotFalse($posBulkImport, 'Bulk Import menu item should exist');

        $this->assertTrue(
            $posBrands < $posCategories,
            'Brands must appear before Categories'
        );
        $this->assertTrue(
            $posCategories < $posSubCategories,
            'Categories must appear before Sub Categories'
        );
        $this->assertTrue(
            $posSubCategories < $posProducts,
            'Sub Categories must appear before Products'
        );
        $this->assertTrue(
            $posProducts < $posProductTypes,
            'Products must appear directly before Product Types'
        );
        $this->assertTrue(
            $posProductTypes < $posParties,
            'Product Types must appear before Parties'
        );
        $this->assertTrue(
            $posParties < $posPriceList,
            'Parties must appear before Price List'
        );
        $this->assertTrue(
            $posPriceList < $posBulkImport,
            'Price List must appear before Bulk Import'
        );
    }

    public function test_masters_sidebar_order_for_client_admin(): void
    {
        $response = $this->actingAs($this->clientAdmin)->get(route('dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $posSubCategories = strpos($content, '>Sub Categories</span>');
        $posProducts = strpos($content, '>Products</span>');
        $posProductTypes = strpos($content, '>Product Types</span>');

        $this->assertTrue($posSubCategories < $posProducts, 'Sub Categories must appear before Products');
        $this->assertTrue($posProducts < $posProductTypes, 'Products must appear before Product Types');
    }

    public function test_products_page_renders_and_highlights_products_menu(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('masters.products.index'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Products link has active styling
        $this->assertMatchesRegularExpression(
            '/href="[^"]*masters\/products"[^>]*class="[^"]*bg-indigo-600\/90[^"]*"/',
            $content,
            'Products menu item should have active highlighting classes when on Products page'
        );
    }

    public function test_product_types_page_renders_and_highlights_product_types_menu(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('masters.product-types.index'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Product Types link has active styling
        $this->assertMatchesRegularExpression(
            '/href="[^"]*masters\/product-types"[^>]*class="[^"]*bg-indigo-600\/90[^"]*"/',
            $content,
            'Product Types menu item should have active highlighting classes when on Product Types page'
        );
    }
}

