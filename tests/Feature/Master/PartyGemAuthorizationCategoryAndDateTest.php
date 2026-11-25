<?php

namespace Tests\Feature\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\PartyGemAuthorization;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyGemAuthorizationCategoryAndDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_party_form_renders_category_and_date_of_issue_fields(): void
    {
        $user = $this->partyUser();

        $response = $this->actingAs($user)->get(route('masters.customers.create'));

        $response->assertOk();
        $response->assertSee('Category');
        $response->assertSee('placeholder="Enter category"', false);
        $response->assertSee('Date of Issue');
        $response->assertSee('type="date"', false);
    }

    public function test_can_create_party_with_single_gem_oem_category_and_date_of_issue(): void
    {
        $user = $this->partyUser();
        $brand = $this->brand($user->company_id, 'Cisco');

        $response = $this->actingAs($user)->post(route('masters.customers.store'), [
            'name' => 'Cisco Partner Ltd',
            'party_type' => 'sundry_debtors',
            'gem_authorizations' => [
                [
                    'brand_id' => $brand->id,
                    'gem_registered' => '1',
                    'gem_authorized' => '1',
                    'authorization_number' => 'AUTH-CISCO-01',
                    'category' => 'Network Switches & Routers',
                    'date_of_issue' => '2026-05-15',
                ],
            ],
        ]);

        $response->assertRedirect(route('masters.customers.index'));

        $party = Customer::query()->latest('id')->firstOrFail();
        $this->assertCount(1, $party->gemAuthorizations);

        $auth = $party->gemAuthorizations->first();
        $this->assertSame($brand->id, $auth->brand_id);
        $this->assertTrue($auth->gem_registered);
        $this->assertTrue($auth->gem_authorized);
        $this->assertSame('AUTH-CISCO-01', $auth->authorization_number);
        $this->assertSame('Network Switches & Routers', $auth->category);
        $this->assertSame('2026-05-15', $auth->date_of_issue?->format('Y-m-d'));
    }

    public function test_can_create_party_with_multiple_gem_oems_having_different_categories_and_dates(): void
    {
        $user = $this->partyUser();
        $sony = $this->brand($user->company_id, 'Sony');
        $dell = $this->brand($user->company_id, 'Dell');

        $response = $this->actingAs($user)->post(route('masters.customers.store'), [
            'name' => 'Multi Vendor Trader',
            'party_type' => 'sundry_debtors',
            'gem_authorizations' => [
                [
                    'brand_id' => $sony->id,
                    'gem_registered' => '1',
                    'gem_authorized' => '1',
                    'authorization_number' => 'AUTH-SONY-77',
                    'category' => 'Audio & Visual Displays',
                    'date_of_issue' => '2026-01-10',
                ],
                [
                    'brand_id' => $dell->id,
                    'gem_registered' => '1',
                    'gem_authorized' => '1',
                    'authorization_number' => 'AUTH-DELL-88',
                    'category' => 'Laptops & Workstations',
                    'date_of_issue' => '2026-04-22',
                ],
            ],
        ]);

        $response->assertRedirect(route('masters.customers.index'));

        $party = Customer::query()->latest('id')->firstOrFail();
        $this->assertCount(2, $party->gemAuthorizations);

        $sonyAuth = $party->gemAuthorizations->firstWhere('brand_id', $sony->id);
        $this->assertNotNull($sonyAuth);
        $this->assertSame('AUTH-SONY-77', $sonyAuth->authorization_number);
        $this->assertSame('Audio & Visual Displays', $sonyAuth->category);
        $this->assertSame('2026-01-10', $sonyAuth->date_of_issue?->format('Y-m-d'));

        $dellAuth = $party->gemAuthorizations->firstWhere('brand_id', $dell->id);
        $this->assertNotNull($dellAuth);
        $this->assertSame('AUTH-DELL-88', $dellAuth->authorization_number);
        $this->assertSame('Laptops & Workstations', $dellAuth->category);
        $this->assertSame('2026-04-22', $dellAuth->date_of_issue?->format('Y-m-d'));
    }

    public function test_can_edit_and_update_or_clear_gem_category_and_date_of_issue(): void
    {
        $user = $this->partyUser();
        $brand = $this->brand($user->company_id, 'HP');

        $party = Customer::create([
            'company_id' => $user->company_id,
            'code' => 'PTY-HP-1',
            'name' => 'HP Authorized Dealer',
            'party_type' => 'sundry_debtors',
            'is_active' => true,
        ]);

        $auth = PartyGemAuthorization::create([
            'customer_id' => $party->id,
            'brand_id' => $brand->id,
            'gem_registered' => true,
            'gem_authorized' => true,
            'authorization_number' => 'HP-AUTH-001',
            'category' => 'Printers & Plotters',
            'date_of_issue' => '2026-02-14',
        ]);

        // Test Edit View contains the data
        $editResponse = $this->actingAs($user)->get(route('masters.customers.edit', $party));
        $editResponse->assertOk();
        $this->assertStringContainsString('Printers & Plotters', $editResponse->getContent());
        $this->assertStringContainsString('2026-02-14', $editResponse->getContent());

        // Test Update with modified values
        $updateResponse = $this->actingAs($user)->put(route('masters.customers.update', $party), [
            'name' => 'HP Authorized Dealer Updated',
            'party_type' => 'sundry_debtors',
            'gem_authorizations' => [
                [
                    'id' => $auth->id,
                    'brand_id' => $brand->id,
                    'gem_registered' => '1',
                    'gem_authorized' => '1',
                    'authorization_number' => 'HP-AUTH-001',
                    'category' => 'Commercial Laptops',
                    'date_of_issue' => '2026-03-20',
                ],
            ],
        ]);

        $updateResponse->assertRedirect(route('masters.customers.index'));

        $auth->refresh();
        $this->assertSame('Commercial Laptops', $auth->category);
        $this->assertSame('2026-03-20', $auth->date_of_issue?->format('Y-m-d'));

        // Test clearing both optional values
        $clearResponse = $this->actingAs($user)->put(route('masters.customers.update', $party), [
            'name' => 'HP Authorized Dealer Updated',
            'party_type' => 'sundry_debtors',
            'gem_authorizations' => [
                [
                    'id' => $auth->id,
                    'brand_id' => $brand->id,
                    'gem_registered' => '1',
                    'gem_authorized' => '1',
                    'authorization_number' => 'HP-AUTH-001',
                    'category' => '',
                    'date_of_issue' => '',
                ],
            ],
        ]);

        $clearResponse->assertRedirect(route('masters.customers.index'));

        $auth->refresh();
        $this->assertNull($auth->category);
        $this->assertNull($auth->date_of_issue);
    }

    public function test_removing_oem_record_removes_only_that_record(): void
    {
        $user = $this->partyUser();
        $sony = $this->brand($user->company_id, 'Sony');
        $dell = $this->brand($user->company_id, 'Dell');

        $party = Customer::create([
            'company_id' => $user->company_id,
            'code' => 'PTY-DUAL-1',
            'name' => 'Dual OEM Entity',
            'party_type' => 'sundry_debtors',
            'is_active' => true,
        ]);

        $sonyAuth = PartyGemAuthorization::create([
            'customer_id' => $party->id,
            'brand_id' => $sony->id,
            'gem_registered' => true,
            'gem_authorized' => true,
            'authorization_number' => 'SONY-KEEP',
            'category' => 'Keep Category',
            'date_of_issue' => '2026-01-01',
        ]);

        $dellAuth = PartyGemAuthorization::create([
            'customer_id' => $party->id,
            'brand_id' => $dell->id,
            'gem_registered' => true,
            'gem_authorized' => true,
            'authorization_number' => 'DELL-DELETE',
            'category' => 'Delete Category',
            'date_of_issue' => '2026-02-02',
        ]);

        // Submit update keeping only Sony
        $response = $this->actingAs($user)->put(route('masters.customers.update', $party), [
            'name' => 'Dual OEM Entity',
            'party_type' => 'sundry_debtors',
            'gem_authorizations' => [
                [
                    'id' => $sonyAuth->id,
                    'brand_id' => $sony->id,
                    'gem_registered' => '1',
                    'gem_authorized' => '1',
                    'authorization_number' => 'SONY-KEEP',
                    'category' => 'Keep Category',
                    'date_of_issue' => '2026-01-01',
                ],
            ],
        ]);

        $response->assertRedirect(route('masters.customers.index'));

        $this->assertDatabaseHas('party_gem_authorizations', [
            'id' => $sonyAuth->id,
            'category' => 'Keep Category',
        ]);

        $this->assertDatabaseMissing('party_gem_authorizations', [
            'id' => $dellAuth->id,
        ]);
    }

    public function test_party_show_view_displays_gem_category_and_formatted_date_of_issue(): void
    {
        $user = $this->partyUser();
        $brand = $this->brand($user->company_id, 'Lenovo');

        $party = Customer::create([
            'company_id' => $user->company_id,
            'code' => 'PTY-LEN-1',
            'name' => 'Lenovo Showcase Party',
            'party_type' => 'sundry_debtors',
            'is_active' => true,
        ]);

        PartyGemAuthorization::create([
            'customer_id' => $party->id,
            'brand_id' => $brand->id,
            'gem_registered' => true,
            'gem_authorized' => true,
            'authorization_number' => 'LEN-7788',
            'category' => 'Workstation Servers',
            'date_of_issue' => '2026-08-19',
        ]);

        $response = $this->actingAs($user)->get(route('masters.customers.show', $party));

        $response->assertOk();
        $response->assertSee('Workstation Servers');
        $response->assertSee('19-08-2026');
    }

    private function partyUser(): User
    {
        $company = Company::create([
            'name' => 'GEM Test Co',
            'code' => 'GTC',
            'is_active' => true,
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        return $user;
    }

    private function brand(int $companyId, string $name): Brand
    {
        $brand = new Brand;
        $brand->forceFill([
            'company_id' => $companyId,
            'name' => $name,
            'is_active' => true,
        ])->save();

        return $brand;
    }
}
