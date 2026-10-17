<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductTrackingTypeSelectAllTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Uom $baseUom;
    protected Uom $altUom;
    protected Brand $brand;
    protected Category $category;
    protected ProductType $productType;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Apex Automation Corp',
            'code' => 'AAC-01',
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

        $this->baseUom = Uom::firstOrCreate(['code' => 'PCS'], [
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'is_active' => true,
        ]);

        $this->altUom = Uom::firstOrCreate(['code' => 'BOX'], [
            'company_id' => $this->company->id,
            'name' => 'Box',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Siemens',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'PLCs',
            'code' => 'PLC',
            'is_active' => true,
        ]);

        $this->productType = ProductType::firstOrCreate(['name' => 'FINISHED GOODS'], [
            'company_id' => $this->company->id,
            'code' => 'FG',
            'is_active' => true,
        ]);
    }

    protected function createProduct(array $overrides = []): Product
    {
        static $counter = 800;
        $counter++;

        return Product::create(array_merge([
            'company_id' => $this->company->id,
            'name' => "PLC Controller {$counter}",
            'sku' => "PLC-SKU-{$counter}",
            'serial_no' => "PLC-SN-{$counter}",
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'height_cm' => 10.00,
            'length_cm' => 20.00,
            'width_cm' => 15.00,
            'net_weight' => 1.500,
            'tracking_type' => 'none',
            'is_active' => true,
        ], $overrides));
    }

    public function test_create_product_form_renders_tracking_type_with_select_all_and_options(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.create'));

        $response->assertOk();
        $response->assertSee('Tracking Type');
        $response->assertSee('Select All');
        $response->assertSee('x-ref="selectAllCheckbox"', false);
        $response->assertSee(':checked="allSelected"', false);
        $response->assertSee('@change="toggleSelectAll()"', false);
        $response->assertSee('toggleSelectAll()', false);
        $response->assertSee('toggleOption(opt.value)', false);
        $response->assertSee(':checked="selected.includes(opt.value)"', false);
        $response->assertSee('name="tracking_type[]"', false);
        $response->assertSee('selected: []', false);
    }

    public function test_edit_product_form_prefills_tracking_type_when_both_options_selected(): void
    {
        $product = $this->createProduct([
            'name' => 'Siemens S7-1200 CPU',
            'sku' => 'S7-1200-CPU',
            'tracking_type' => 'serial,batch',
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.products.edit', $product));

        $response->assertOk();
        $response->assertSee('Tracking Type');
        $response->assertSee('selected: ["serial","batch"]', false);
    }

    public function test_edit_product_form_prefills_tracking_type_when_single_option_selected(): void
    {
        $productSerial = $this->createProduct([
            'name' => 'Siemens S7-1500 CPU',
            'sku' => 'S7-1500-CPU',
            'tracking_type' => 'serial',
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.products.edit', $productSerial));

        $response->assertOk();
        $response->assertSee('selected: ["serial"]', false);

        $productBatch = $this->createProduct([
            'name' => 'Siemens ET200SP Base',
            'sku' => 'ET200SP-BASE',
            'tracking_type' => 'batch',
        ]);

        $responseBatch = $this->actingAs($this->user)->get(route('masters.products.edit', $productBatch));

        $responseBatch->assertOk();
        $responseBatch->assertSee('selected: ["batch"]', false);
    }

    public function test_can_create_product_with_both_tracking_types_selected_via_select_all(): void
    {
        $sku = 'PRD-TRACK-ALL-01';
        $payload = [
            'name' => 'Servo Drive High Torque',
            'sku' => $sku,
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'height_cm' => 25.00,
            'length_cm' => 30.00,
            'width_cm' => 12.00,
            'net_weight' => 3.200,
            'tracking_type' => ['serial', 'batch'],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', $sku)->first();
        $this->assertNotNull($product);
        $this->assertSame('serial,batch', $product->tracking_type);
        $this->assertTrue($product->isSerialTracked());
        $this->assertTrue($product->isBatchTracked());
        $this->assertEquals(['serial', 'batch'], $product->tracking_types);
    }

    public function test_can_create_product_with_select_all_unchecked_saving_none(): void
    {
        $sku = 'PRD-TRACK-NONE-02';
        $payload = [
            'name' => 'Standard Terminal Block',
            'sku' => $sku,
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'height_cm' => 5.00,
            'length_cm' => 10.00,
            'width_cm' => 4.00,
            'net_weight' => 0.150,
            // tracking_type omitted because both unchecked
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', $sku)->first();
        $this->assertNotNull($product);
        $this->assertSame('none', $product->tracking_type);
        $this->assertFalse($product->isSerialTracked());
        $this->assertFalse($product->isBatchTracked());
        $this->assertEmpty($product->tracking_types);
    }

    public function test_can_create_product_with_only_serial_wise_selected(): void
    {
        $sku = 'PRD-TRACK-SER-03';
        $payload = [
            'name' => 'Industrial Gateway Router',
            'sku' => $sku,
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'height_cm' => 8.00,
            'length_cm' => 15.00,
            'width_cm' => 10.00,
            'net_weight' => 0.850,
            'tracking_type' => ['serial'],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', $sku)->first();
        $this->assertNotNull($product);
        $this->assertSame('serial', $product->tracking_type);
        $this->assertTrue($product->isSerialTracked());
        $this->assertFalse($product->isBatchTracked());
        $this->assertEquals(['serial'], $product->tracking_types);
    }

    public function test_can_create_product_with_only_batch_wise_selected(): void
    {
        $sku = 'PRD-TRACK-BAT-04';
        $payload = [
            'name' => 'Heat Shrink Tube Reel',
            'sku' => $sku,
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'height_cm' => 12.00,
            'length_cm' => 20.00,
            'width_cm' => 20.00,
            'net_weight' => 1.100,
            'tracking_type' => ['batch'],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), $payload);
        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', $sku)->first();
        $this->assertNotNull($product);
        $this->assertSame('batch', $product->tracking_type);
        $this->assertFalse($product->isSerialTracked());
        $this->assertTrue($product->isBatchTracked());
        $this->assertEquals(['batch'], $product->tracking_types);
    }

    public function test_can_update_product_to_uncheck_select_all(): void
    {
        $product = $this->createProduct([
            'name' => 'Smart Sensor Transmitter',
            'sku' => 'SENS-TX-99',
            'tracking_type' => 'serial,batch',
        ]);

        $updatePayload = [
            'name' => 'Smart Sensor Transmitter Updated',
            'sku' => 'SENS-TX-99',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'height_cm' => 10.00,
            'length_cm' => 20.00,
            'width_cm' => 15.00,
            'net_weight' => 1.500,
            // tracking_type empty when Select All is unchecked
            'tracking_type' => [],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), $updatePayload);
        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertSame('none', $product->tracking_type);
        $this->assertFalse($product->isSerialTracked());
        $this->assertFalse($product->isBatchTracked());
    }

    public function test_can_update_product_unchecking_serial_wise_while_preserving_batch_wise(): void
    {
        $product = $this->createProduct([
            'name' => 'Fiber Optic Patch Panel',
            'sku' => 'FO-PP-48',
            'tracking_type' => 'serial,batch',
        ]);

        $updatePayload = [
            'name' => 'Fiber Optic Patch Panel',
            'sku' => 'FO-PP-48',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'height_cm' => 10.00,
            'length_cm' => 20.00,
            'width_cm' => 15.00,
            'net_weight' => 1.500,
            'tracking_type' => ['batch'], // Only batch-wise preserved
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), $updatePayload);
        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertSame('batch', $product->tracking_type);
        $this->assertFalse($product->isSerialTracked());
        $this->assertTrue($product->isBatchTracked());
    }

    public function test_can_update_product_unchecking_batch_wise_while_preserving_serial_wise(): void
    {
        $product = $this->createProduct([
            'name' => 'Managed Ethernet Switch',
            'sku' => 'ETH-SW-24',
            'tracking_type' => 'serial,batch',
        ]);

        $updatePayload = [
            'name' => 'Managed Ethernet Switch',
            'sku' => 'ETH-SW-24',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'height_cm' => 10.00,
            'length_cm' => 20.00,
            'width_cm' => 15.00,
            'net_weight' => 1.500,
            'tracking_type' => ['serial'], // Only serial-wise preserved
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), $updatePayload);
        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertSame('serial', $product->tracking_type);
        $this->assertTrue($product->isSerialTracked());
        $this->assertFalse($product->isBatchTracked());
    }

    public function test_can_update_product_from_none_to_both_via_select_all(): void
    {
        $product = $this->createProduct([
            'name' => 'Power Supply 24V 10A',
            'sku' => 'PSU-24V-10A',
            'tracking_type' => 'none',
        ]);

        $updatePayload = [
            'name' => 'Power Supply 24V 10A',
            'sku' => 'PSU-24V-10A',
            'base_uom_id' => $this->baseUom->id,
            'alternate_uom_id' => $this->altUom->id,
            'conversion_factor' => 1,
            'height_cm' => 10.00,
            'length_cm' => 20.00,
            'width_cm' => 15.00,
            'net_weight' => 1.500,
            'tracking_type' => ['serial', 'batch'], // Both checked
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), $updatePayload);
        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertSame('serial,batch', $product->tracking_type);
        $this->assertTrue($product->isSerialTracked());
        $this->assertTrue($product->isBatchTracked());
    }

    public function test_javascript_tracking_state_machine_logic(): void
    {
        $tempJs = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tracking_test_' . uniqid() . '.js';
        $jsCode = <<<'JS'
const state = {
    options: [
        { value: 'serial', label: 'Serial-wise' },
        { value: 'batch', label: 'Batch-wise' }
    ],
    selected: [],
    searchQuery: '',
    get filteredOptions() {
        if (!this.searchQuery || !this.searchQuery.trim()) return this.options;
        const q = this.searchQuery.toLowerCase().trim();
        return this.options.filter(opt => opt.label.toLowerCase().includes(q) || opt.value.toLowerCase().includes(q));
    },
    get allSelected() {
        const visible = this.filteredOptions;
        return visible.length > 0 && visible.every(opt => this.selected.includes(opt.value));
    },
    get isIndeterminate() {
        const visible = this.filteredOptions;
        const count = visible.filter(opt => this.selected.includes(opt.value)).length;
        return count > 0 && count < visible.length;
    },
    toggleSelectAll() {
        const visibleValues = this.filteredOptions.map(o => o.value);
        if (this.allSelected) {
            this.selected = this.selected.filter(val => !visibleValues.includes(val));
        } else {
            this.selected = Array.from(new Set([...this.selected, ...visibleValues]));
        }
    },
    toggleOption(val) {
        if (this.selected.includes(val)) {
            this.selected = this.selected.filter(v => v !== val);
        } else {
            this.selected = [...this.selected, val];
        }
    }
};

const results = [];
results.push({ step: 'init', allSelected: state.allSelected, isIndeterminate: state.isIndeterminate, selected: state.selected });

state.toggleSelectAll();
results.push({ step: 'select_all', allSelected: state.allSelected, isIndeterminate: state.isIndeterminate, selected: state.selected });

state.toggleSelectAll();
results.push({ step: 'uncheck_all', allSelected: state.allSelected, isIndeterminate: state.isIndeterminate, selected: state.selected });

state.toggleOption('serial');
results.push({ step: 'check_serial', allSelected: state.allSelected, isIndeterminate: state.isIndeterminate, selected: state.selected });

state.toggleOption('batch');
results.push({ step: 'check_batch', allSelected: state.allSelected, isIndeterminate: state.isIndeterminate, selected: state.selected });

state.toggleOption('serial');
results.push({ step: 'uncheck_serial', allSelected: state.allSelected, isIndeterminate: state.isIndeterminate, selected: state.selected });

state.toggleOption('batch');
results.push({ step: 'uncheck_both', allSelected: state.allSelected, isIndeterminate: state.isIndeterminate, selected: state.selected });

console.log(JSON.stringify(results));
JS;

        file_put_contents($tempJs, $jsCode);
        $output = shell_exec('node ' . escapeshellarg($tempJs));
        @unlink($tempJs);

        $this->assertNotEmpty($output, 'Node.js failed to execute state machine script');

        $steps = json_decode(trim($output), true);
        $this->assertIsArray($steps);

        // Step 1: Init (unchecked)
        $this->assertFalse($steps[0]['allSelected']);
        $this->assertFalse($steps[0]['isIndeterminate']);
        $this->assertEmpty($steps[0]['selected']);

        // Step 2: Select All -> both options become checked
        $this->assertTrue($steps[1]['allSelected']);
        $this->assertFalse($steps[1]['isIndeterminate']);
        $this->assertEquals(['serial', 'batch'], $steps[1]['selected']);

        // Step 3: Uncheck Select All -> both options become unchecked
        $this->assertFalse($steps[2]['allSelected']);
        $this->assertFalse($steps[2]['isIndeterminate']);
        $this->assertEmpty($steps[2]['selected']);

        // Step 4: Check Serial-wise individually -> Select All is unchecked, indeterminate is true
        $this->assertFalse($steps[3]['allSelected']);
        $this->assertTrue($steps[3]['isIndeterminate']);
        $this->assertEquals(['serial'], $steps[3]['selected']);

        // Step 5: Check Batch-wise individually -> Select All becomes checked
        $this->assertTrue($steps[4]['allSelected']);
        $this->assertFalse($steps[4]['isIndeterminate']);
        $this->assertEquals(['serial', 'batch'], $steps[4]['selected']);

        // Step 6: Uncheck Serial-wise -> Select All becomes unchecked, Batch-wise stays checked
        $this->assertFalse($steps[5]['allSelected']);
        $this->assertTrue($steps[5]['isIndeterminate']);
        $this->assertEquals(['batch'], $steps[5]['selected']);

        // Step 7: Uncheck Batch-wise -> Select All becomes unchecked, neither option selected
        $this->assertFalse($steps[6]['allSelected']);
        $this->assertFalse($steps[6]['isIndeterminate']);
        $this->assertEmpty($steps[6]['selected']);
    }
}
