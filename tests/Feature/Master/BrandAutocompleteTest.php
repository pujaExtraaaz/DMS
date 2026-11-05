<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BrandAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $tenantUser;
    protected Company $companyA;
    protected Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $roleStaff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        foreach (['masters.view', 'masters.create', 'masters.edit', 'masters.delete', 'masters.manage'] as $perm) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->companyA = Company::create([
            'name' => 'Company Alpha',
            'code' => 'ALPHA01',
            'is_active' => true,
        ]);

        $this->companyB = Company::create([
            'name' => 'Company Beta',
            'code' => 'BETA01',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->companyA->id,
        ]);
        $this->adminUser->assignRole($roleAdmin);

        $this->tenantUser = User::factory()->create([
            'company_id' => $this->companyB->id,
        ]);
        $this->tenantUser->assignRole($roleStaff);
        $this->tenantUser->givePermissionTo('masters.view', 'masters.create', 'masters.edit');
    }

    public function test_search_requires_at_least_one_character(): void
    {
        Brand::create(['company_id' => $this->companyA->id, 'name' => 'Sony']);

        $response = $this->actingAs($this->adminUser)->getJson(route('masters.brands.search', ['q' => '']));
        $response->assertOk();
        $response->assertExactJson([]);

        $responseWhitespace = $this->actingAs($this->adminUser)->getJson(route('masters.brands.search', ['q' => '   ']));
        $responseWhitespace->assertOk();
        $responseWhitespace->assertExactJson([]);
    }

    public function test_search_returns_case_insensitive_matching_brands_with_expected_behavior(): void
    {
        Brand::create(['company_id' => $this->companyA->id, 'name' => 'Sony']);
        Brand::create(['company_id' => $this->companyA->id, 'name' => 'Samsung']);
        Brand::create(['company_id' => $this->companyA->id, 'name' => 'Shure']);
        Brand::create(['company_id' => $this->companyA->id, 'name' => 'Sennheiser']);
        Brand::create(['company_id' => $this->companyA->id, 'name' => 'LG']);

        // 1. When user types 'S' (lowercase or uppercase)
        $resS = $this->actingAs($this->adminUser)->getJson(route('masters.brands.search', [
            'q' => 's',
            'company_id' => $this->companyA->id,
        ]));
        $resS->assertOk();
        $namesS = collect($resS->json())->pluck('name')->all();
        $this->assertContains('Samsung', $namesS);
        $this->assertContains('Sennheiser', $namesS);
        $this->assertContains('Shure', $namesS);
        $this->assertContains('Sony', $namesS);
        $this->assertNotContains('LG', $namesS);

        // 2. When user types 'SON' -> returns Sony
        $resSon = $this->actingAs($this->adminUser)->getJson(route('masters.brands.search', [
            'q' => 'SON',
            'company_id' => $this->companyA->id,
        ]));
        $resSon->assertOk();
        $namesSon = collect($resSon->json())->pluck('name')->all();
        $this->assertEquals(['Sony'], $namesSon);

        // 3. When user types 'SAM' -> returns Samsung
        $resSam = $this->actingAs($this->adminUser)->getJson(route('masters.brands.search', [
            'q' => 'sam',
            'company_id' => $this->companyA->id,
        ]));
        $resSam->assertOk();
        $namesSam = collect($resSam->json())->pluck('name')->all();
        $this->assertEquals(['Samsung'], $namesSam);

        // 4. When no brands match -> returns empty array
        $resNone = $this->actingAs($this->adminUser)->getJson(route('masters.brands.search', [
            'q' => 'NonExistentBrandXYZ',
            'company_id' => $this->companyA->id,
        ]));
        $resNone->assertOk();
        $resNone->assertExactJson([]);
    }

    public function test_search_results_are_ordered_alphabetically_and_limited_to_ten(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $letter = chr(64 + $i);
            Brand::create([
                'company_id' => $this->companyA->id,
                'name' => "Brand {$letter}",
            ]);
        }

        $res = $this->actingAs($this->adminUser)->getJson(route('masters.brands.search', [
            'q' => 'Brand',
            'company_id' => $this->companyA->id,
        ]));

        $res->assertOk();
        $data = $res->json();
        $this->assertCount(10, $data);

        $names = collect($data)->pluck('name')->all();
        $sortedNames = $names;
        sort($sortedNames, SORT_NATURAL | SORT_FLAG_CASE);
        $this->assertEquals($sortedNames, $names);
    }

    public function test_search_enforces_company_and_tenant_isolation(): void
    {
        Brand::create(['company_id' => $this->companyA->id, 'name' => 'Alpha Audio']);
        Brand::create(['company_id' => $this->companyB->id, 'name' => 'Beta Vision']);

        // Admin querying for company A gets Alpha Audio, but not Beta Vision
        $resAdminA = $this->actingAs($this->adminUser)->getJson(route('masters.brands.search', [
            'q' => 'Audio',
            'company_id' => $this->companyA->id,
        ]));
        $resAdminA->assertOk();
        $this->assertCount(1, $resAdminA->json());
        $this->assertEquals('Alpha Audio', $resAdminA->json()[0]['name']);

        // Tenant user restricted to company B cannot see company A brand even if passing company_id of A
        $resTenant = $this->actingAs($this->tenantUser)->getJson(route('masters.brands.search', [
            'q' => 'Audio',
            'company_id' => $this->companyA->id,
        ]));
        $resTenant->assertOk();
        $this->assertEmpty($resTenant->json());

        // Tenant user can see company B brand
        $resTenantB = $this->actingAs($this->tenantUser)->getJson(route('masters.brands.search', [
            'q' => 'Beta',
        ]));
        $resTenantB->assertOk();
        $this->assertCount(1, $resTenantB->json());
        $this->assertEquals('Beta Vision', $resTenantB->json()[0]['name']);
    }

    public function test_form_view_renders_autocomplete_interface(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('masters.brands.create'));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('brandAutocomplete', $content);
        $this->assertStringContainsString(route('masters.brands.search'), $content);
        $this->assertStringContainsString('No matching brands found', $content);
        $this->assertStringContainsString('Searching brands...', $content);
        $this->assertStringContainsString('already exists', $content);
        $this->assertStringContainsString('Edit Existing Brand', $content);
    }

    public function test_duplicate_brand_creation_is_prevented_case_insensitively(): void
    {
        Brand::create([
            'company_id' => $this->companyA->id,
            'name' => 'Sony',
        ]);

        // Attempting to create 'sony' (lowercase) with extra whitespace
        $response = $this->actingAs($this->adminUser)->post(route('masters.brands.store'), [
            'company_id' => $this->companyA->id,
            'name' => '  sony  ',
            'detail' => 'Duplicate attempt',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertEquals(1, Brand::whereRaw('LOWER(name) = ?', ['sony'])->count());
    }

    public function test_editing_existing_brand_allows_same_name_but_blocks_duplicate_of_other_brand(): void
    {
        $brand1 = Brand::create(['company_id' => $this->companyA->id, 'name' => 'Sony']);
        $brand2 = Brand::create(['company_id' => $this->companyA->id, 'name' => 'Samsung']);

        // 1. Preserving same name on update works smoothly
        $resSame = $this->actingAs($this->adminUser)->put(route('masters.brands.update', $brand1), [
            'company_id' => $this->companyA->id,
            'name' => 'Sony',
            'detail' => 'Updated electronics',
            'is_active' => '1',
        ]);
        $resSame->assertRedirect(route('masters.brands.index'));
        $brand1->refresh();
        $this->assertEquals('Updated Electronics', $brand1->detail);

        // 2. Renaming brand1 to existing brand2 ('Samsung') is blocked by validation
        $resDupe = $this->actingAs($this->adminUser)->put(route('masters.brands.update', $brand1), [
            'company_id' => $this->companyA->id,
            'name' => 'samsung',
        ]);
        $resDupe->assertSessionHasErrors('name');
    }
}
