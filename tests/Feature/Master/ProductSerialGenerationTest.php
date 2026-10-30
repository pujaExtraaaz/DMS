<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use App\Support\CodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductSerialGenerationTest extends TestCase
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
            'name' => 'Alpha Corp',
            'code' => 'ALP-01',
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

        $this->brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Sony Electronics',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Televisions',
            'is_active' => true,
        ]);
    }

    protected function createProduct(array $overrides = []): Product
    {
        static $seq = 1;
        $num = $seq++;

        return Product::create(array_merge([
            'company_id' => $this->company->id,
            'name' => "Product {$num}",
            'sku' => "SKU-TEST-{$num}",
            'serial_no' => sprintf('PRD-GLOBAL-%05d', $num),
            'base_uom_id' => $this->uom->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'is_active' => true,
        ], $overrides));
    }

    public function test_form_renders_serial_number_input(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.products.create'));

        $response->assertOk();
        $response->assertSee('Serial Number');
        $response->assertSee('name="serial_no"', false);
    }

    public function test_new_product_receives_unique_auto_generated_serial_number(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Sony Bravia 55',
            'sku' => 'SNY-BRV-55',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'SNY-BRV-55')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->serial_no);
        $this->assertMatchesRegularExpression('/^PRD-GLOBAL-\d{5}$/', $product->serial_no);
    }

    public function test_consecutive_products_receive_incrementing_unique_serial_numbers(): void
    {
        $response1 = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Product Alpha',
            'sku' => 'SKU-ALPHA-1',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);
        $response1->assertRedirect(route('masters.products.index'));

        $response2 = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Product Beta',
            'sku' => 'SKU-BETA-2',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);
        $response2->assertRedirect(route('masters.products.index'));

        $p1 = Product::where('sku', 'SKU-ALPHA-1')->first();
        $p2 = Product::where('sku', 'SKU-BETA-2')->first();

        $this->assertNotEquals($p1->serial_no, $p2->serial_no);
    }

    public function test_serial_generator_respects_soft_deleted_records_and_avoids_collision(): void
    {
        // Create an existing product with PRD-GLOBAL-00017
        $existing = $this->createProduct([
            'serial_no' => 'PRD-GLOBAL-00017',
            'sku' => 'SKU-DELETED-17',
        ]);

        // Soft delete it (simulating archived product whose serial is still in DB)
        $existing->delete();

        // The record is soft-deleted, but in database
        $this->assertSoftDeleted('products', ['id' => $existing->id]);
        $this->assertDatabaseHas('products', ['serial_no' => 'PRD-GLOBAL-00017']);

        // Next auto-generated serial number must NOT be PRD-GLOBAL-00017, it must be PRD-GLOBAL-00018
        $nextSerial = CodeGenerator::forProductSerial();
        $this->assertEquals('PRD-GLOBAL-00018', $nextSerial);

        // Creating a new product must succeed with PRD-GLOBAL-00018 without collision
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Product After Deleted 17',
            'sku' => 'SKU-AFTER-17',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));
        $created = Product::where('sku', 'SKU-AFTER-17')->first();
        $this->assertEquals('PRD-GLOBAL-00018', $created->serial_no);
    }

    public function test_manual_serial_number_entry_validation_fails_on_duplicate(): void
    {
        $this->createProduct([
            'serial_no' => 'PRD-GLOBAL-00017',
            'sku' => 'SKU-EXISTING-17',
        ]);

        // Try to manually enter the duplicate serial number
        $response = $this->actingAs($this->user)->from(route('masters.products.create'))->post(route('masters.products.store'), [
            'name' => 'Duplicate Serial Attempt',
            'sku' => 'SKU-ATTEMPT-NEW',
            'serial_no' => 'PRD-GLOBAL-00017',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors(['serial_no' => 'The product serial number has already been taken.']);
        $this->assertDatabaseMissing('products', ['sku' => 'SKU-ATTEMPT-NEW']);
    }

    public function test_manual_serial_number_entry_validation_fails_even_if_existing_is_soft_deleted(): void
    {
        $p = $this->createProduct([
            'serial_no' => 'PRD-GLOBAL-00017',
            'sku' => 'SKU-DELETED-17B',
        ]);
        $p->delete();

        // Submitting manually PRD-GLOBAL-00017 must fail validation gracefully
        $response = $this->actingAs($this->user)->from(route('masters.products.create'))->post(route('masters.products.store'), [
            'name' => 'Duplicate Soft Deleted Serial Attempt',
            'sku' => 'SKU-ATTEMPT-NEW-2',
            'serial_no' => 'PRD-GLOBAL-00017',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors(['serial_no' => 'The product serial number has already been taken.']);
        $this->assertDatabaseMissing('products', ['sku' => 'SKU-ATTEMPT-NEW-2']);
    }

    public function test_valid_unique_manual_serial_number_is_saved_successfully(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Manual Serial Product',
            'sku' => 'SKU-MANUAL-1',
            'serial_no' => 'CUSTOM-SERIAL-999',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $product = Product::where('sku', 'SKU-MANUAL-1')->first();
        $this->assertNotNull($product);
        $this->assertEquals('CUSTOM-SERIAL-999', $product->serial_no);
    }

    public function test_editing_product_without_modifying_serial_preserves_serial_number(): void
    {
        $product = $this->createProduct([
            'name' => 'Original Name',
            'sku' => 'SKU-ORIGINAL',
            'serial_no' => 'PRD-GLOBAL-00005',
        ]);

        $response = $this->actingAs($this->user)->put(route('masters.products.update', $product), [
            'name' => 'Updated Name',
            'sku' => 'SKU-ORIGINAL',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $product->refresh();
        $this->assertEquals('Updated Name', $product->name);
        $this->assertEquals('PRD-GLOBAL-00005', $product->serial_no);
    }

    public function test_concurrent_collision_is_retried_and_succeeds_with_next_serial(): void
    {
        $attempts = 0;
        Product::creating(function ($product) use (&$attempts) {
            $attempts++;
            if ($attempts === 1) {
                DB::table('products')->insert([
                    'company_id' => $this->company->id,
                    'name' => 'Concurrent Inserted Product',
                    'sku' => 'SKU-CONCURRENT-RACE',
                    'serial_no' => $product->serial_no,
                    'base_uom_id' => $this->uom->id,
                    'tax_rate' => 18,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $response = $this->actingAs($this->user)->post(route('masters.products.store'), [
            'name' => 'Race Condition Survivor Product',
            'sku' => 'SKU-SURVIVOR-1',
            'base_uom_id' => $this->uom->id,
            'alternate_uom_not_applicable' => 1,
            'dimensions_not_applicable' => 1,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'tax_rate' => 18,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.products.index'));

        $survivor = Product::where('sku', 'SKU-SURVIVOR-1')->first();
        $this->assertNotNull($survivor);
        $this->assertNotEquals(
            Product::where('sku', 'SKU-CONCURRENT-RACE')->value('serial_no'),
            $survivor->serial_no
        );
    }
}

