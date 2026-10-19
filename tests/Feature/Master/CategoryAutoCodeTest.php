<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryAutoCodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->company = Company::create([
            'name' => 'Company Alpha',
            'code' => 'CMP-01',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Test Brand',
            'is_active' => true,
        ]);
    }

    public function test_create_category_form_has_no_code_field(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.categories.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringNotContainsString('name="code"', $content);
        $this->assertStringNotContainsString('Auto-generated on save', $content);
        $this->assertStringNotContainsString('title="auto gen code"', $content);
    }

    public function test_category_is_created_without_code(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.categories.store'), [
            'brand_id' => $this->brand->id,
            'name' => 'Smartphones',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('masters.categories.index'));
        $this->assertTrue(strcasecmp((string) session('status'), 'Category created successfully.') === 0);

        $category = Category::where('name', 'Smartphones')->first();
        $this->assertNotNull($category);
        $this->assertSame($this->brand->id, $category->brand_id);
        $this->assertFalse(array_key_exists('code', $category->getAttributes()));
    }

    public function test_client_supplied_code_is_ignored_on_create(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.categories.store'), [
            'brand_id' => $this->brand->id,
            'name' => 'Laptops',
            'code' => 'HACKED-CODE',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('masters.categories.index'));

        $category = Category::where('name', 'Laptops')->first();
        $this->assertNotNull($category);
        $this->assertFalse(array_key_exists('code', $category->getAttributes()));
    }

    public function test_category_update_ignores_code_and_has_no_code_on_edit_form(): void
    {
        $category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Accessories',
            'is_active' => true,
        ]);

        $formResponse = $this->actingAs($this->user)->get(route('masters.categories.edit', $category));
        $formResponse->assertStatus(200);
        $this->assertStringNotContainsString('name="code"', $formResponse->getContent());

        $response = $this->actingAs($this->user)->put(route('masters.categories.update', $category), [
            'brand_id' => $this->brand->id,
            'name' => 'Accessories Updated',
            'code' => 'CHANGED-CODE',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('masters.categories.index'));

        $category->refresh();
        $this->assertSame('Accessories Updated', $category->name);
        $this->assertFalse(array_key_exists('code', $category->getAttributes()));
    }
}
