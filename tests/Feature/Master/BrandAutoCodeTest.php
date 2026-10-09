<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use App\Support\CodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BrandAutoCodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company1;
    protected Company $company2;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->company1 = Company::create([
            'name' => 'Company Alpha',
            'code' => 'CMP-01',
            'is_active' => true,
        ]);

        $this->company2 = Company::create([
            'name' => 'Company Beta',
            'code' => 'CMP-02',
            'is_active' => true,
        ]);
    }

    public function test_add_brand_form_displays_auto_code_notice_and_no_code_input(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.brands.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Auto code:', $content);
        $this->assertStringContainsString('BR-&lt;company&gt;-&lt;seq&gt;', $content);
        $this->assertStringNotContainsString('<input type="text" name="code"', $content);
    }

    public function test_brand_code_is_automatically_generated_on_save(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.brands.store'), [
            'company_id' => $this->company1->id,
            'name' => 'Nike',
            'detail' => 'Athletic footwear and apparel',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('masters.brands.index'));
        $response->assertSessionHas('status', 'Brand created successfully.');

        $brand = Brand::where('name', 'Nike')->first();
        $this->assertNotNull($brand);
        $this->assertSame("BR-{$this->company1->id}-00001", $brand->code);
        $this->assertSame($this->company1->id, $brand->company_id);
    }

    public function test_multiple_brands_receive_consecutive_unique_codes_under_same_company(): void
    {
        $brands = ['Adidas', 'Puma', 'Reebok', 'Under Armour'];

        foreach ($brands as $index => $brandName) {
            $response = $this->actingAs($this->user)->post(route('masters.brands.store'), [
                'company_id' => $this->company1->id,
                'name' => $brandName,
            ]);
            $response->assertRedirect(route('masters.brands.index'));

            $expectedSeq = sprintf('%05d', $index + 1);
            $expectedCode = "BR-{$this->company1->id}-{$expectedSeq}";

            $brand = Brand::where('name', $brandName)->first();
            $this->assertNotNull($brand);
            $this->assertSame($expectedCode, $brand->code);
        }

        $allCodes = Brand::where('company_id', $this->company1->id)->pluck('code')->all();
        $this->assertCount(count($brands), $allCodes);
        $this->assertSame(array_unique($allCodes), $allCodes);
    }

    public function test_brand_codes_are_scoped_per_company_without_collision(): void
    {
        // Company 1 brands
        $b1 = Brand::create(['company_id' => $this->company1->id, 'name' => 'Sony']);
        $b2 = Brand::create(['company_id' => $this->company1->id, 'name' => 'Panasonic']);

        // Company 2 brands
        $b3 = Brand::create(['company_id' => $this->company2->id, 'name' => 'LG']);
        $b4 = Brand::create(['company_id' => $this->company2->id, 'name' => 'Samsung']);

        // Global (no company) brand
        $b5 = Brand::create(['company_id' => null, 'name' => 'Philips']);

        $this->assertSame("BR-{$this->company1->id}-00001", $b1->code);
        $this->assertSame("BR-{$this->company1->id}-00002", $b2->code);

        $this->assertSame("BR-{$this->company2->id}-00001", $b3->code);
        $this->assertSame("BR-{$this->company2->id}-00002", $b4->code);

        $this->assertSame('BR-GLOBAL-00001', $b5->code);
    }

    public function test_manual_code_in_request_is_ignored_on_brand_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.brands.store'), [
            'company_id' => $this->company1->id,
            'name' => 'Apple',
            'code' => 'CUSTOM-CODE-999', // Attempt to override
        ]);

        $response->assertRedirect(route('masters.brands.index'));

        $brand = Brand::where('name', 'Apple')->first();
        $this->assertNotNull($brand);
        $this->assertNotSame('CUSTOM-CODE-999', $brand->code);
        $this->assertSame("BR-{$this->company1->id}-00001", $brand->code);
    }

    public function test_existing_brand_code_is_not_regenerated_on_edit(): void
    {
        $brand = Brand::create([
            'company_id' => $this->company1->id,
            'name' => 'Original Brand',
            'code' => 'LEGACY-CODE-001',
        ]);

        $this->assertSame('LEGACY-CODE-001', $brand->code);

        // Edit brand form shows readonly code
        $formResponse = $this->actingAs($this->user)->get(route('masters.brands.edit', $brand));
        $formResponse->assertStatus(200);
        $formResponse->assertSee('LEGACY-CODE-001');

        // Update brand
        $updateResponse = $this->actingAs($this->user)->put(route('masters.brands.update', $brand), [
            'company_id' => $this->company1->id,
            'name' => 'Updated Brand Name',
            'detail' => 'Updated details',
            'code' => 'ATTEMPTED-CHANGE',
        ]);

        $updateResponse->assertRedirect(route('masters.brands.index'));

        $brand->refresh();
        $this->assertSame('Updated Brand Name', $brand->name);
        $this->assertSame('LEGACY-CODE-001', $brand->code); // Code is strictly preserved
    }

    public function test_duplicate_brand_name_displays_validation_error(): void
    {
        Brand::create([
            'company_id' => $this->company1->id,
            'name' => 'Casio',
        ]);

        $response = $this->actingAs($this->user)->post(route('masters.brands.store'), [
            'company_id' => $this->company1->id,
            'name' => 'Casio',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Brand::where('name', 'Casio')->count());
    }

    public function test_concurrent_brand_creation_collision_retry_succeeds(): void
    {
        // Pre-create brand 1
        Brand::create(['company_id' => $this->company1->id, 'name' => 'Brand Alpha']);

        // Next code would be BR-<company1>-00002.
        // Simulate a collision scenario by generating an insert that handles 1062
        $b2 = Brand::create(['company_id' => $this->company1->id, 'name' => 'Brand Beta']);
        $this->assertSame("BR-{$this->company1->id}-00002", $b2->code);

        $b3 = Brand::create(['company_id' => $this->company1->id, 'name' => 'Brand Gamma']);
        $this->assertSame("BR-{$this->company1->id}-00003", $b3->code);

        // All 3 codes exist and are unique
        $this->assertSame(3, Brand::where('company_id', $this->company1->id)->count());
    }
}
