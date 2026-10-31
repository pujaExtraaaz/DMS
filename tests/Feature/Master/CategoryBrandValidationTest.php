<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryBrandValidationTest extends TestCase
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
            'name' => 'Samsung',
            'is_active' => true,
        ]);
    }

    public function test_create_category_form_shows_required_brand_asterisk(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.categories.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Brand <span class="text-red-500">*</span>', $content);
        $this->assertStringContainsString('id="brand_id"', $content);
        $this->assertStringContainsString('required', $content);
        $this->assertStringContainsString('submitCategoryForm', $content);
    }

    public function test_category_creation_fails_without_brand(): void
    {
        $response = $this->actingAs($this->user)->from(route('masters.categories.create'))->post(route('masters.categories.store'), [
            'name' => 'Electronics',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors([
            'brand_id' => 'Please select a brand before creating a category.',
        ]);
        $this->assertDatabaseMissing('categories', [
            'name' => 'Electronics',
        ]);
    }

    public function test_category_creation_fails_with_empty_brand(): void
    {
        $response = $this->actingAs($this->user)->from(route('masters.categories.create'))->post(route('masters.categories.store'), [
            'brand_id' => '',
            'name' => 'Electronics',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors([
            'brand_id' => 'Please select a brand before creating a category.',
        ]);
        $this->assertDatabaseMissing('categories', [
            'name' => 'Electronics',
        ]);
    }

    public function test_category_creation_fails_with_invalid_brand_id(): void
    {
        $response = $this->actingAs($this->user)->from(route('masters.categories.create'))->post(route('masters.categories.store'), [
            'brand_id' => 999999,
            'name' => 'Electronics',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors([
            'brand_id' => 'Please select a brand before creating a category.',
        ]);
        $this->assertDatabaseMissing('categories', [
            'name' => 'Electronics',
        ]);
    }

    public function test_category_is_created_successfully_with_valid_brand(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.categories.store'), [
            'brand_id' => $this->brand->id,
            'name' => 'Electronics',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('masters.categories.index'));
        $this->assertDatabaseHas('categories', [
            'brand_id' => $this->brand->id,
            'name' => 'Electronics',
        ]);
    }

    public function test_category_update_fails_without_brand(): void
    {
        $category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Audio',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->from(route('masters.categories.edit', $category))->put(route('masters.categories.update', $category), [
            'brand_id' => '',
            'name' => 'Audio Updated',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors([
            'brand_id' => 'Please select a brand before creating a category.',
        ]);

        $category->refresh();
        $this->assertSame($this->brand->id, $category->brand_id);
        $this->assertSame('Audio', $category->name);
    }

    public function test_quick_store_fails_without_brand(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('masters.categories.quick-store'), [
            'name' => 'Quick Category',
            'is_active' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Please select a brand before creating a category.',
        ]);
        $this->assertDatabaseMissing('categories', [
            'name' => 'Quick Category',
        ]);
    }

    public function test_quick_store_fails_with_invalid_brand(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('masters.categories.quick-store'), [
            'brand_id' => 888888,
            'name' => 'Quick Category Invalid',
            'is_active' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Please select a brand before creating a category.',
        ]);
        $this->assertDatabaseMissing('categories', [
            'name' => 'Quick Category Invalid',
        ]);
    }

    public function test_quick_store_succeeds_with_valid_brand(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('masters.categories.quick-store'), [
            'brand_id' => $this->brand->id,
            'name' => 'Quick Category Valid',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'item' => [
                'name' => 'Quick Category Valid',
                'brand_id' => (string) $this->brand->id,
            ],
        ]);
        $this->assertDatabaseHas('categories', [
            'brand_id' => $this->brand->id,
            'name' => 'Quick Category Valid',
        ]);
    }
}

