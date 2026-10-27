<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BrandAutoCodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company1;

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
    }

    public function test_add_brand_form_has_no_code_field(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.brands.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringNotContainsString('name="code"', $content);
        $this->assertStringNotContainsString('Auto code:', $content);
    }

    public function test_brand_is_created_without_code(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.brands.store'), [
            'company_id' => $this->company1->id,
            'name' => 'Nike',
            'detail' => 'Athletic footwear and apparel',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('masters.brands.index'));
        $this->assertTrue(strcasecmp((string) session('status'), 'Brand created successfully.') === 0);

        $brand = Brand::where('name', 'Nike')->first();
        $this->assertNotNull($brand);
        $this->assertSame($this->company1->id, $brand->company_id);
        $this->assertFalse(array_key_exists('code', $brand->getAttributes()));
    }

    public function test_manual_code_in_request_is_ignored_on_brand_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.brands.store'), [
            'company_id' => $this->company1->id,
            'name' => 'Apple',
            'code' => 'CUSTOM-CODE-999',
        ]);

        $response->assertRedirect(route('masters.brands.index'));

        $brand = Brand::where('name', 'Apple')->first();
        $this->assertNotNull($brand);
        $this->assertFalse(array_key_exists('code', $brand->getAttributes()));
    }

    public function test_edit_brand_form_has_no_code_field(): void
    {
        $brand = Brand::create([
            'company_id' => $this->company1->id,
            'name' => 'Original Brand',
        ]);

        $formResponse = $this->actingAs($this->user)->get(route('masters.brands.edit', $brand));
        $formResponse->assertStatus(200);
        $this->assertStringNotContainsString('name="code"', $formResponse->getContent());

        $updateResponse = $this->actingAs($this->user)->put(route('masters.brands.update', $brand), [
            'company_id' => $this->company1->id,
            'name' => 'Updated Brand Name',
            'detail' => 'Updated details',
            'code' => 'ATTEMPTED-CHANGE',
        ]);

        $updateResponse->assertRedirect(route('masters.brands.index'));

        $brand->refresh();
        $this->assertSame('Updated Brand Name', $brand->name);
        $this->assertFalse(array_key_exists('code', $brand->getAttributes()));
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
}
