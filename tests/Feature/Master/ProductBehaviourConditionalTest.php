<?php

namespace Tests\Feature\Master;

use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductBehaviourConditionalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Uom $baseUom;
    protected Uom $altUom;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Behaviour Test Enterprise',
            'code' => 'BTE-01',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->assignRole($role);

        $this->baseUom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Numbers',
            'code' => 'NOS',
            'is_active' => true,
        ]);

        $this->altUom = Uom::create([
            'company_id' => $this->company->id,
            'name' => 'Box',
            'code' => 'BOX',
            'is_active' => true,
        ]);
    }

    public function test_product_create_page_renders_with_behaviour_panel_and_dropdowns(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.create'));
        $response->assertOk();

        // Check Provide Behaviour Options field
        $response->assertSee('Provide Behaviour Options', false);
        $response->assertSee('name="provide_behaviour"', false);

        // Check all 5 Behaviour settings labels are present
        foreach (Product::BEHAVIOUR_FLAGS as $flag => $label) {
            $response->assertSee($label, false);
            $response->assertSee('name="'.$flag.'"', false);
            $response->assertSee('id="behaviour_'.$flag.'"', false);
        }

        // Verify Alpine.js conditional panel binding
        $response->assertSee('x-show="provideBehaviour === \'1\'"', false);
    }

    public function test_new_product_gets_default_behaviour_values(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Standard Valve',
            'sku' => 'VALVE-STD-01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'dimensions_not_applicable' => 1,
            'has_dimensions' => 1,
            'tax_rate' => 18,
            'purchase_price' => 200,
            'selling_price' => 350,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'VALVE-STD-01')->first();
        $this->assertNotNull($product);

        // Provide behaviour should be false by default
        $this->assertFalse($product->provide_behaviour);

        // Default values: treat_purchases_as_consumed is Yes (true), others are No (false)
        $this->assertTrue($product->treat_purchases_as_consumed);
        $this->assertFalse($product->ignore_physical_count_diff);
        $this->assertFalse($product->ignore_negative_balances);
        $this->assertFalse($product->treat_sales_as_new_manufacture);
        $this->assertFalse($product->treat_rejections_inward_as_scrap);

        // When provide_behaviour is false, usesBehaviour returns false for all
        $this->assertFalse($product->usesBehaviour('treat_purchases_as_consumed'));
        $this->assertFalse($product->usesBehaviour('ignore_negative_balances'));
        $this->assertFalse($product->usesBehaviour('treat_sales_as_new_manufacture'));
    }

    public function test_all_five_options_can_be_independently_configured_and_saved(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Custom Behaviour Unit',
            'sku' => 'CBU-99',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'dimensions_not_applicable' => 1,
            'has_dimensions' => 1,
            'tax_rate' => 18,
            'purchase_price' => 500,
            'selling_price' => 800,
            'provide_behaviour' => 1,
            'ignore_physical_count_diff' => 1,
            'ignore_negative_balances' => 1,
            'treat_sales_as_new_manufacture' => 1,
            'treat_purchases_as_consumed' => 0,
            'treat_rejections_inward_as_scrap' => 1,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'CBU-99')->first();
        $this->assertNotNull($product);
        $this->assertTrue($product->provide_behaviour);
        $this->assertTrue($product->ignore_physical_count_diff);
        $this->assertTrue($product->ignore_negative_balances);
        $this->assertTrue($product->treat_sales_as_new_manufacture);
        $this->assertFalse($product->treat_purchases_as_consumed);
        $this->assertTrue($product->treat_rejections_inward_as_scrap);

        // usesBehaviour reflects configured values when provide_behaviour is active
        $this->assertTrue($product->usesBehaviour('ignore_physical_count_diff'));
        $this->assertTrue($product->usesBehaviour('ignore_negative_balances'));
        $this->assertTrue($product->usesBehaviour('treat_sales_as_new_manufacture'));
        $this->assertFalse($product->usesBehaviour('treat_purchases_as_consumed'));
        $this->assertTrue($product->usesBehaviour('treat_rejections_inward_as_scrap'));
    }

    public function test_changing_provide_behaviour_to_no_does_not_silently_delete_saved_configurations(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Safe Settings Product',
            'sku' => 'SAFE-01',
            'serial_no' => 'SN-SAFE-01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'purchase_price' => 100,
            'selling_price' => 150,
            'tax_rate' => 18,
            'provide_behaviour' => true,
            'ignore_physical_count_diff' => true,
            'ignore_negative_balances' => true,
            'treat_sales_as_new_manufacture' => true,
            'treat_purchases_as_consumed' => false,
            'treat_rejections_inward_as_scrap' => true,
            'is_active' => true,
        ]);

        // User updates product with provide_behaviour = 0 (No)
        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), [
            'name' => 'Safe Settings Product Renamed',
            'sku' => 'SAFE-01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'dimensions_not_applicable' => 1,
            'has_dimensions' => 1,
            'tax_rate' => 18,
            'purchase_price' => 100,
            'selling_price' => 150,
            'provide_behaviour' => 0,
            // Hidden behaviour flags are submitted from Alpine state or preserved
            'ignore_physical_count_diff' => 1,
            'ignore_negative_balances' => 1,
            'treat_sales_as_new_manufacture' => 1,
            'treat_purchases_as_consumed' => 0,
            'treat_rejections_inward_as_scrap' => 1,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertFalse($product->provide_behaviour);

        // Settings are safely preserved in the database
        $this->assertTrue($product->ignore_physical_count_diff);
        $this->assertTrue($product->ignore_negative_balances);
        $this->assertTrue($product->treat_sales_as_new_manufacture);
        $this->assertFalse($product->treat_purchases_as_consumed);
        $this->assertTrue($product->treat_rejections_inward_as_scrap);

        // But usesBehaviour returns false because provide_behaviour is No
        $this->assertFalse($product->usesBehaviour('ignore_physical_count_diff'));
        $this->assertFalse($product->usesBehaviour('ignore_negative_balances'));
        $this->assertFalse($product->usesBehaviour('treat_sales_as_new_manufacture'));
        $this->assertFalse($product->usesBehaviour('treat_rejections_inward_as_scrap'));

        // When user turns provide_behaviour back to 1 (Yes)
        $this->actingAs($this->user)->put(route('masters.products.update', $product), [
            'name' => 'Safe Settings Product Renamed',
            'sku' => 'SAFE-01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'dimensions_not_applicable' => 1,
            'has_dimensions' => 1,
            'tax_rate' => 18,
            'purchase_price' => 100,
            'selling_price' => 150,
            'provide_behaviour' => 1,
            'is_active' => 1,
        ])->assertRedirect();

        $product->refresh();
        $this->assertTrue($product->provide_behaviour);
        $this->assertTrue($product->usesBehaviour('ignore_physical_count_diff'));
        $this->assertTrue($product->usesBehaviour('ignore_negative_balances'));
        $this->assertTrue($product->usesBehaviour('treat_sales_as_new_manufacture'));
        $this->assertTrue($product->usesBehaviour('treat_rejections_inward_as_scrap'));
    }

    public function test_updating_unrelated_product_fields_preserves_behaviour_settings(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
            'sku' => 'ORIG-01',
            'serial_no' => 'SN-ORIG-01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'purchase_price' => 50,
            'selling_price' => 80,
            'tax_rate' => 18,
            'provide_behaviour' => true,
            'ignore_physical_count_diff' => true,
            'ignore_negative_balances' => true,
            'treat_sales_as_new_manufacture' => false,
            'treat_purchases_as_consumed' => true,
            'treat_rejections_inward_as_scrap' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)->put(route('masters.products.update', $product), [
            'name' => 'Updated Product Name Only',
            'sku' => 'ORIG-01',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'dimensions_not_applicable' => 1,
            'has_dimensions' => 1,
            'tax_rate' => 18,
            'purchase_price' => 60,
            'selling_price' => 90,
            'is_active' => 1,
        ])->assertRedirect();

        $product->refresh();
        $this->assertSame('Updated Product Name Only', $product->name);
        $this->assertEquals(60.0, (float) $product->purchase_price);
        $this->assertTrue($product->provide_behaviour);
        $this->assertTrue($product->ignore_physical_count_diff);
        $this->assertTrue($product->ignore_negative_balances);
        $this->assertFalse($product->treat_sales_as_new_manufacture);
        $this->assertTrue($product->treat_purchases_as_consumed);
        $this->assertFalse($product->treat_rejections_inward_as_scrap);
    }

    public function test_inventory_workflows_when_behaviour_is_disabled_vs_enabled(): void
    {
        $service = app(StockMovementService::class);

        // Product with provide_behaviour = false, but treat_purchases_as_consumed = true
        $disabledProd = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Disabled Behaviour Prod',
            'sku' => 'DIS-01',
            'serial_no' => 'SN-DIS-01',
            'base_uom_id' => $this->baseUom->id,
            'purchase_price' => 10,
            'selling_price' => 20,
            'tax_rate' => 18,
            'provide_behaviour' => false,
            'treat_purchases_as_consumed' => true,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
        $service->recordIn($disabledProd, $this->baseUom, 5, 'purchase', null, 'Purchase Inward', $this->user, null, 10);

        // Stock SHOULD increase by 5 because provide_behaviour is false
        $level = StockLevel::where('product_id', $disabledProd->id)->value('quantity');
        $this->assertEquals(5.0, (float) $level);
        $this->assertFalse(StockMovement::where('product_id', $disabledProd->id)->where('type', 'consumption')->exists());

        // Product with provide_behaviour = true, and treat_purchases_as_consumed = true
        $enabledProd = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Enabled Behaviour Prod',
            'sku' => 'EN-01',
            'serial_no' => 'SN-EN-01',
            'base_uom_id' => $this->baseUom->id,
            'purchase_price' => 10,
            'selling_price' => 20,
            'tax_rate' => 18,
            'provide_behaviour' => true,
            'treat_purchases_as_consumed' => true,
            'is_active' => true,
        ]);

        $service->recordIn($enabledProd, $this->baseUom, 5, 'purchase', null, 'Purchase Inward', $this->user, null, 10);

        // Stock should be 0 because consumption movement was auto-created
        $levelEnabled = StockLevel::where('product_id', $enabledProd->id)->value('quantity');
        $this->assertEquals(0.0, (float) $levelEnabled);
        $this->assertTrue(StockMovement::where('product_id', $enabledProd->id)->where('type', 'consumption')->exists());
    }

    public function test_product_show_page_displays_behaviour_settings_when_active(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Display Test Product',
            'sku' => 'DISP-01',
            'serial_no' => 'SN-DISP-01',
            'base_uom_id' => $this->baseUom->id,
            'purchase_price' => 10,
            'selling_price' => 20,
            'tax_rate' => 18,
            'provide_behaviour' => true,
            'treat_purchases_as_consumed' => true,
            'ignore_negative_balances' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.products.show', $product));
        $response->assertOk();
        $response->assertSee('Inventory Behaviour', false);
        $response->assertSee('Provide Behaviour Options', false);
        $response->assertSee('Yes (Active)', false);
        $response->assertSee('Treat all purchases as consumed', false);
        $response->assertSee('Ignore negative balances', false);
    }
}
