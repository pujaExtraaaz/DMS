<?php

namespace Tests\Feature\Organization;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyViewAndDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected BusinessGroup $businessGroup;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $roleRegular = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->businessGroup = BusinessGroup::create([
            'name' => 'Apex Holdings Group',
            'code' => 'AHG',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'business_group_id' => $this->businessGroup->id,
            'name' => 'Apex Technologies Pvt Ltd',
            'legal_name' => 'Apex Technologies Private Limited',
            'code' => 'APEX01',
            'gstin' => '27AAAAA0000A1Z5',
            'pan' => 'AAAAA0000A',
            'tan' => 'PNE000000X',
            'cin' => 'U72900MH2020PTC123456',
            'udyam_registration_no' => 'UDYAM-MH-01-0012345',
            'msme_category' => 'micro',
            'msme_registration_no' => 'MSME-0012345',
            'address' => 'Plot 42, Tech Park, Hinjewadi',
            'state' => 'Maharashtra',
            'pincode' => '411057',
            'phone' => '020-12345678',
            'email' => 'contact@apextech.test',
            'website' => 'https://apextech.test',
            'bank_name' => 'HDFC Bank',
            'bank_account_no' => '50200012345678',
            'bank_ifsc' => 'HDFC0001234',
            'upi_id' => 'apextech@hdfcbank',
            'od_limit' => 500000.00,
            'interest_rate' => 8.50,
            'purchase_terms_and_conditions' => "Payment within 30 days.\nInspect goods upon delivery.",
            'selling_terms_and_conditions' => "Standard warranty 1 year.\nNon-refundable after 15 days.",
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($roleAdmin);

        $this->regularUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->regularUser->assignRole($roleRegular);
    }

    public function test_companies_index_displays_view_edit_and_delete_buttons(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.companies.index'));

        $response->assertOk();
        $response->assertSee('Apex Technologies Pvt Ltd');
        $response->assertSee('APEX01');
        $response->assertSee(route('organization.companies.show', $this->company));
        $response->assertSee(route('organization.companies.edit', $this->company));
        $response->assertSee('View');
        $response->assertSee('Edit');
        $response->assertSee('Delete');
        $response->assertSee('Delete Company'); // Confirmation modal title
        $response->assertSee('Are you sure you want to delete this company?');
    }

    public function test_company_show_view_can_be_rendered_with_details(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.companies.show', $this->company));

        $response->assertOk();
        $response->assertSee('Apex Technologies Pvt Ltd');
        $response->assertSee('Apex Technologies Private Limited');
        $response->assertSee('APEX01');
        $response->assertSee('Apex Holdings Group');
        $response->assertSee('Active');
        $response->assertSee('27AAAAA0000A1Z5');
        $response->assertSee('AAAAA0000A');
        $response->assertSee('PNE000000X');
        $response->assertSee('U72900MH2020PTC123456');
        $response->assertSee('UDYAM-MH-01-0012345');
        $response->assertSee('Micro');
        $response->assertSee('020-12345678');
        $response->assertSee('contact@apextech.test');
        $response->assertSee('HDFC Bank');
        $response->assertSee('50200012345678');
        $response->assertSee('HDFC0001234');
        $response->assertSee('apextech@hdfcbank');
        $response->assertSee('Payment within 30 days.');
        $response->assertSee('Standard warranty 1 year.');
        $response->assertSee('Back');
        $response->assertSee(route('organization.companies.index'));
        $response->assertSee(route('organization.companies.edit', $this->company));
    }

    public function test_company_show_displays_dashes_for_empty_optional_fields(): void
    {
        $minimalCompany = Company::create([
            'name' => 'Bare Minimum Ltd',
            'code' => 'MIN001',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.companies.show', $minimalCompany));

        $response->assertOk();
        $response->assertSee('Bare Minimum Ltd');
        $response->assertSee('MIN001');
        $response->assertSee('Inactive');
        $response->assertSee('—');
    }

    public function test_cannot_delete_company_with_linked_branches(): void
    {
        $standaloneCompany = Company::create([
            'name' => 'Branch Owner Ltd',
            'code' => 'BOL01',
            'is_active' => true,
        ]);

        Branch::create([
            'company_id' => $standaloneCompany->id,
            'name' => 'Head Office Branch',
            'code' => 'HOB01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.companies.destroy', $standaloneCompany));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Cannot delete company because branches are linked to it.',
        ]);

        $this->assertDatabaseHas('companies', ['id' => $standaloneCompany->id]);
    }

    public function test_cannot_delete_company_with_linked_warehouses(): void
    {
        // Create a branch on this->company
        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'HQ Branch',
            'code' => 'HQB01',
            'is_active' => true,
        ]);

        $standaloneCompany = Company::create([
            'name' => 'Warehouse Owner Ltd',
            'code' => 'WOL01',
            'is_active' => true,
        ]);

        Warehouse::create([
            'company_id' => $standaloneCompany->id,
            'branch_id' => $branch->id,
            'name' => 'Central Depot',
            'code' => 'DEP01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.companies.destroy', $standaloneCompany));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Cannot delete company because warehouses are linked to it.',
        ]);

        $this->assertDatabaseHas('companies', ['id' => $standaloneCompany->id]);
    }

    public function test_cannot_delete_company_with_linked_financial_years(): void
    {
        $standaloneCompany = Company::create([
            'name' => 'FY Owner Ltd',
            'code' => 'FYO01',
            'is_active' => true,
        ]);

        FinancialYear::create([
            'company_id' => $standaloneCompany->id,
            'name' => 'FY 2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.companies.destroy', $standaloneCompany));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Cannot delete company because financial years are linked to it.',
        ]);

        $this->assertDatabaseHas('companies', ['id' => $standaloneCompany->id]);
    }

    public function test_cannot_delete_company_with_associated_users(): void
    {
        // $this->company has $this->adminUser and $this->regularUser linked via company_id
        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.companies.destroy', $this->company));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Cannot delete company because active users are associated with it.',
        ]);

        $this->assertDatabaseHas('companies', ['id' => $this->company->id]);
    }

    public function test_can_delete_company_without_linked_records(): void
    {
        $freshCompany = Company::create([
            'name' => 'Ephemeral Enterprise',
            'code' => 'EPH01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('organization.companies.destroy', $freshCompany));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => "Company 'Ephemeral Enterprise' deleted successfully.",
        ]);

        $this->assertDatabaseMissing('companies', ['id' => $freshCompany->id]);
    }

    public function test_unauthorized_user_cannot_delete_company(): void
    {
        $freshCompany = Company::create([
            'name' => 'Target Enterprise',
            'code' => 'TGT01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->deleteJson(route('organization.companies.destroy', $freshCompany));

        $response->assertStatus(403);
        $this->assertDatabaseHas('companies', ['id' => $freshCompany->id]);
    }
}
