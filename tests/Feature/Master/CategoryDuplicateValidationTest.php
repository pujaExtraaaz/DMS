<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryDuplicateValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Brand $brandA;
    protected Brand $brandB;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->company = Company::create([
            'name' => 'Alpha Corp',
            'code' => 'ALP-01',
            'is_active' => true,
        ]);

        $this->brandA = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Brand Alpha',
            'is_active' => true,
        ]);

        $this->brandB = Brand::create([
            'company_id' => $this->company->id,
            'name' => 'Brand Beta',
            'is_active' => true,
        ]);
    }

    public function test_can_create_category_with_unique_name_under_brand(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.categories.store'), [
            'brand_id' => $this->brandA->id,
            'name' => 'Smartphones',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.categories.index'));
        $this->assertDatabaseHas('categories', [
            'brand_id' => $this->brandA->id,
            'name' => 'Smartphones',
        ]);
    }

    public function test_cannot_create_duplicate_category_under_same_brand(): void
    {
        Category::create([
            'brand_id' => $this->brandA->id,
            'name' => 'Laptops',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('masters.categories.create'))
            ->post(route('masters.categories.store'), [
                'brand_id' => $this->brandA->id,
                'name' => 'Laptops',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('masters.categories.create'));
        $response->assertSessionHasErrors([
            'name' => 'This category already exists for the selected brand.',
        ]);
    }

    public function test_case_variations_and_whitespace_are_treated_as_duplicates(): void
    {
        Category::create([
            'brand_id' => $this->brandA->id,
            'name' => 'Adapter',
            'is_active' => true,
        ]);

        // Attempt lowercase with extra leading/trailing whitespace
        $response = $this->actingAs($this->user)
            ->from(route('masters.categories.create'))
            ->post(route('masters.categories.store'), [
                'brand_id' => $this->brandA->id,
                'name' => '   adapter   ',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('masters.categories.create'));
        $response->assertSessionHasErrors([
            'name' => 'This category already exists for the selected brand.',
        ]);

        // Attempt ALL-CAPS
        $responseCaps = $this->actingAs($this->user)
            ->from(route('masters.categories.create'))
            ->post(route('masters.categories.store'), [
                'brand_id' => $this->brandA->id,
                'name' => 'ADAPTER',
                'is_active' => 1,
            ]);

        $responseCaps->assertSessionHasErrors([
            'name' => 'This category already exists for the selected brand.',
        ]);
    }

    public function test_same_category_name_allowed_under_different_brand(): void
    {
        Category::create([
            'brand_id' => $this->brandA->id,
            'name' => 'Accessories',
            'is_active' => true,
        ]);

        // Creating 'Accessories' under Brand Beta must succeed
        $response = $this->actingAs($this->user)->post(route('masters.categories.store'), [
            'brand_id' => $this->brandB->id,
            'name' => 'Accessories',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.categories.index'));
        $this->assertDatabaseHas('categories', [
            'brand_id' => $this->brandB->id,
            'name' => 'Accessories',
        ]);
    }

    public function test_editing_category_without_changing_name_succeeds(): void
    {
        $category = Category::create([
            'brand_id' => $this->brandA->id,
            'name' => 'Monitors',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->put(route('masters.categories.update', $category), [
            'brand_id' => $this->brandA->id,
            'name' => 'Monitors',
            'is_active' => 0,
        ]);

        $response->assertRedirect(route('masters.categories.index'));
        $category->refresh();
        $this->assertFalse($category->is_active);
    }

    public function test_editing_category_to_match_another_under_same_brand_is_rejected(): void
    {
        Category::create([
            'brand_id' => $this->brandA->id,
            'name' => 'Existing Cat',
            'is_active' => true,
        ]);

        $categoryToEdit = Category::create([
            'brand_id' => $this->brandA->id,
            'name' => 'Other Cat',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('masters.categories.edit', $categoryToEdit))
            ->put(route('masters.categories.update', $categoryToEdit), [
                'brand_id' => $this->brandA->id,
                'name' => ' existing cat ',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('masters.categories.edit', $categoryToEdit));
        $response->assertSessionHasErrors([
            'name' => 'This category already exists for the selected brand.',
        ]);

        $categoryToEdit->refresh();
        $this->assertEquals('Other Cat', $categoryToEdit->name);
    }

    public function test_editing_category_to_match_category_under_different_brand_succeeds(): void
    {
        Category::create([
            'brand_id' => $this->brandB->id,
            'name' => 'Headphones',
            'is_active' => true,
        ]);

        $categoryOnBrandA = Category::create([
            'brand_id' => $this->brandA->id,
            'name' => 'Old Name',
            'is_active' => true,
        ]);

        // Renaming category on Brand A to 'Headphones' is allowed because 'Headphones' is on Brand B
        $response = $this->actingAs($this->user)->put(route('masters.categories.update', $categoryOnBrandA), [
            'brand_id' => $this->brandA->id,
            'name' => 'Headphones',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('masters.categories.index'));
        $categoryOnBrandA->refresh();
        $this->assertEquals('Headphones', $categoryOnBrandA->name);
    }

    public function test_validation_failure_preserves_entered_inputs(): void
    {
        Category::create([
            'brand_id' => $this->brandA->id,
            'name' => 'Displays',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('masters.categories.create'))
            ->post(route('masters.categories.store'), [
                'brand_id' => $this->brandA->id,
                'name' => 'Displays',
                'is_active' => 1,
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertEquals($this->brandA->id, old('brand_id'));
        $this->assertEquals('Displays', old('name'));
    }

    public function test_database_constraint_catches_concurrent_duplicates_gracefully(): void
    {
        Category::create([
            'brand_id' => $this->brandA->id,
            'name' => 'Tablets',
            'is_active' => true,
        ]);

        // Simulate a race condition where a duplicate record was inserted just after validation passed
        $simulated = false;
        Category::creating(function ($cat) use (&$simulated) {
            if (! $simulated && $cat->name === 'Simulated Race Category') {
                $simulated = true;
                // Insert directly to trigger unique constraint on the subsequent insert
                \Illuminate\Support\Facades\DB::table('categories')->insert([
                    'brand_id' => $cat->brand_id,
                    'name' => $cat->name,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $response = $this->actingAs($this->user)
            ->from(route('masters.categories.create'))
            ->post(route('masters.categories.store'), [
                'brand_id' => $this->brandA->id,
                'name' => 'Simulated Race Category',
                'is_active' => 1,
            ]);

        // Must redirect back with validation error, NOT 500 error
        $response->assertRedirect(route('masters.categories.create'));
        $response->assertSessionHasErrors([
            'name' => 'This category already exists for the selected brand.',
        ]);
    }
}

