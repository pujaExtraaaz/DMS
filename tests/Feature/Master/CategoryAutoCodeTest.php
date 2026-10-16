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

    public function test_create_category_form_has_readonly_auto_gen_code_field(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.categories.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('title="auto gen code"', $content);
        $this->assertStringContainsString('Auto-generated on save', $content);
        $this->assertStringContainsString('readonly', $content);
        $this->assertStringContainsString('name="code"', $content);
    }

    public function test_category_code_is_automatically_generated_on_save_without_client_code(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.categories.store'), [
            'brand_id' => $this->brand->id,
            'name' => 'Smartphones',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('masters.categories.index'));
        $response->assertSessionHas('status', 'Category created successfully.');

        $category = Category::where('name', 'Smartphones')->first();
        $this->assertNotNull($category);
        $this->assertSame("CAT-{$this->company->id}-00001", $category->code);
        $this->assertSame($this->brand->id, $category->brand_id);
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
        $this->assertNotSame('HACKED-CODE', $category->code);
        $this->assertSame("CAT-{$this->company->id}-00001", $category->code);
    }

    public function test_category_code_cannot_be_changed_on_update(): void
    {
        $category = Category::create([
            'brand_id' => $this->brand->id,
            'name' => 'Accessories',
            'is_active' => true,
        ]);
        $originalCode = $category->code;

        $response = $this->actingAs($this->user)->put(route('masters.categories.update', $category), [
            'brand_id' => $this->brand->id,
            'name' => 'Accessories Updated',
            'code' => 'CHANGED-CODE',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('masters.categories.index'));

        $category->refresh();
        $this->assertSame('Accessories Updated', $category->name);
        $this->assertSame($originalCode, $category->code);
    }
}
