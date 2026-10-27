<?php

namespace Tests\Feature\Organization;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SisterConcernCompanyModalTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected BusinessGroup $group;
    protected Company $existingCompany;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $roleRegular = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->group = BusinessGroup::firstOrCreate(
            ['code' => 'NEXUS-CORP'],
            [
                'name' => 'Nexus Corporation',
                'description' => 'Parent conglomerate',
                'is_active' => true,
            ]
        );

        $this->existingCompany = Company::create([
            'business_group_id' => $this->group->id,
            'name' => 'Nexus Technologies Pvt Ltd',
            'code' => 'NEX-01',
            'legal_name' => 'Nexus Technologies Private Limited',
            'company_type' => 'Private Limited',
            'gstin' => '27AAACN0123M1Z5',
            'pan' => 'AAACN0123M',
            'relationship_type' => 'Subsidiary',
            'shareholding_percentage' => 75.50,
            'compliance_status' => 'Compliant',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->existingCompany->id,
            'name' => 'Admin Controller',
            'email' => 'admin.modal@example.com',
        ]);
        $this->adminUser->assignRole($roleAdmin);

        $this->regularUser = User::factory()->create([
            'company_id' => $this->existingCompany->id,
            'name' => 'Plain Staff',
            'email' => 'staff.modal@example.com',
        ]);
        $this->regularUser->assignRole($roleRegular);
    }

    public function test_sister_concerns_page_renders_with_add_modal_and_masters(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.companies'));

        $response->assertStatus(200);
        $response->assertViewIs('organization.sister-concerns.companies');
        $response->assertSee('Sister Concern Management');
        $response->assertSee('Add Sister Concern');
        $response->assertSee('Tax & Regulatory', false);
        $response->assertSee('Address & Contact', false);
        $response->assertSee('Business & Financial', false);
        $response->assertSee('Relationship & Status', false);
        $response->assertSee('Company Type');
        $response->assertSee('GSTIN');
        $response->assertSee('PAN');
    }

    public function test_add_company_modal_has_sidebar_safe_layout_and_responsive_dimensions(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.companies'));

        $response->assertStatus(200);

        // Modal teleported to body and centered in full viewport above sidebar
        $response->assertSee('x-teleport="body"', false);
        $response->assertSee('fixed inset-0 z-[100]', false);
        $response->assertSee('min-h-screen items-center justify-center', false);
        $response->assertDontSee('left-0 lg:left-64', false);

        // Modal dimension and scrolling rules
        $response->assertSee('max-h-[88vh]', false);
        $response->assertSee('sticky top-0 z-10', false);
        $response->assertSee('sticky bottom-0 z-10', false);

        // Form controls and section fields
        $response->assertSee('x-model="form.name"', false);
        $response->assertSee('x-model="form.code"', false);
        $response->assertSee('x-model="form.company_type"', false);
        $response->assertSee('x-model="form.legal_name"', false);
        $response->assertSee('x-model="form.incorporation_no"', false);
        $response->assertSee('x-model="form.incorporation_date"', false);
        $response->assertSee('x-model="form.cin"', false);
        $response->assertSee('x-model="form.logo_emoji"', false);
        $response->assertSee('x-model="form.gstin"', false);
        $response->assertSee('x-model="form.pan"', false);
        $response->assertSee('x-model="form.tan"', false);
        $response->assertSee('x-model="form.iec_code"', false);
        $response->assertSee('x-model="form.msme_registration_no"', false);
        $response->assertSee('x-model="form.address"', false);
        $response->assertSee('x-model="form.city"', false);
        $response->assertSee('x-model="form.state"', false);
        $response->assertSee('x-model="form.country"', false);
        $response->assertSee('x-model="form.pincode"', false);
        $response->assertSee('x-model="form.phone"', false);
        $response->assertSee('x-model="form.email"', false);
        $response->assertSee('x-model="form.website"', false);
        $response->assertSee('x-model="form.nature_of_business"', false);
        $response->assertSee('x-model="form.authorized_capital"', false);
        $response->assertSee('x-model="form.paid_up_capital"', false);
        $response->assertSee('x-model="form.annual_turnover"', false);
        $response->assertSee('x-model="form.financial_year"', false);
        $response->assertSee('x-model="form.accounting_software"', false);
        $response->assertSee('x-model="form.branches_count_manual"', false);
        $response->assertSee('x-model="form.employees_count_manual"', false);
        $response->assertSee('x-model="form.relationship_type"', false);
        $response->assertSee('x-model="form.shareholding_percentage"', false);
        $response->assertSee('x-model="form.is_active"', false);
        $response->assertSee('x-model="form.compliance_status"', false);

        // Logo emoji container wraps properly
        $response->assertSee('flex items-center gap-1 flex-wrap py-1', false);
    }

    public function test_view_and_delete_company_modals_use_sidebar_safe_boundary(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.companies'));

        $response->assertStatus(200);

        // All 3 modals (Add/Edit, View, Delete) teleport to body with full viewport stacking
        $content = $response->getContent();
        $needle = 'x-teleport="body"';
        $occurrences = substr_count($content, $needle);
        $this->assertGreaterThanOrEqual(3, $occurrences, 'Add/Edit, View, and Delete modals should all teleport to body.');
        $response->assertDontSee('left-0 lg:left-64', false);
    }

    public function test_can_create_sister_concern_with_all_five_sections(): void
    {
        $payload = [
            // 1. Company Information
            'name' => 'Apex Healthcare Solutions Ltd',
            'code' => 'APX-99',
            'legal_name' => 'Apex Healthcare Solutions Private Limited',
            'business_group_id' => $this->group->id,
            'company_type' => 'Private Limited',
            'incorporation_no' => 'INC-987654321',
            'incorporation_date' => '2021-06-15',
            'cin_number' => 'U74999MH2021PTC123456',
            'logo_emoji' => '🏥',

            // 2. Tax & Regulatory
            'gstin' => '27AAACA1234B1Z5',
            'pan' => 'AAACA1234B',
            'tan' => 'PNEB12345C',
            'iec_code' => 'IEC1234567',
            'msme_reg_no' => 'UDYAM-MH-01-0012345',

            // 3. Address & Contact
            'address' => 'Plot 45, MIDC Infotech Park, Hinjewadi Phase 1',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'country' => 'India',
            'pincode' => '411057',
            'phone' => '+91 20 6789 0000',
            'email' => 'info@apexhealthcare.example.com',
            'website' => 'https://apexhealthcare.example.com',

            // 4. Business & Financial
            'nature_of_business' => 'Medical Equipment Distribution & Healthcare IT',
            'authorized_capital' => 5000000.00,
            'paid_up_capital' => 2500000.00,
            'annual_turnover' => 12500000.00,
            'financial_year' => '2025-2026',
            'accounting_software' => 'Tally Prime',
            'branches_count_manual' => 4,
            'employees_count_manual' => 48,

            // 5. Relationship & Status
            'relationship_type' => 'Sister Concern',
            'shareholding_percentage' => 51.00,
            'is_active' => 1,
            'compliance_status' => 'Compliant',
        ];

        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('organization.companies.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'Apex Healthcare Solutions Ltd',
            'code' => 'APX-99',
            'company_type' => 'Private Limited',
            'gstin' => '27AAACA1234B1Z5',
            'pan' => 'AAACA1234B',
            'cin' => 'U74999MH2021PTC123456',
            'logo_emoji' => '🏥',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'country' => 'India',
            'iec_code' => 'IEC1234567',
            'msme_registration_no' => 'UDYAM-MH-01-0012345',
            'accounting_software' => 'Tally Prime',
            'branches_count_manual' => 4,
            'employees_count_manual' => 48,
            'relationship_type' => 'Sister Concern',
            'compliance_status' => 'Compliant',
        ]);
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('organization.companies.store'), [
                // Empty payload missing name, company_type, gstin, pan
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'company_type', 'gstin', 'pan']);
    }

    public function test_validation_fails_for_invalid_gstin_and_pan_formats(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('organization.companies.store'), [
                'name' => 'Bad Statutory Test Co',
                'company_type' => 'Private Limited',
                'gstin' => 'INVALID_GSTIN_123',
                'pan' => 'INVALIDPAN',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['gstin', 'pan']);
    }

    public function test_validation_fails_for_out_of_range_shareholding_and_negative_counts(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('organization.companies.store'), [
                'name' => 'Range Test Co',
                'company_type' => 'Private Limited',
                'gstin' => '27AAACA1234B1Z5',
                'pan' => 'AAACA1234B',
                'shareholding_percentage' => 150.00, // Invalid: exceeds 100
                'branches_count_manual' => -3, // Invalid: negative
                'employees_count_manual' => -10, // Invalid: negative
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'shareholding_percentage',
            'branches_count_manual',
            'employees_count_manual',
        ]);
    }

    public function test_can_update_existing_sister_concern_via_json_endpoint(): void
    {
        $updatePayload = [
            'name' => 'Nexus Global Technologies Ltd',
            'code' => $this->existingCompany->code,
            'company_type' => 'Public Limited',
            'gstin' => '27AAACN0123M1Z5',
            'pan' => 'AAACN0123M',
            'relationship_type' => 'Holding',
            'shareholding_percentage' => 100.00,
            'compliance_status' => 'Compliant',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'country' => 'India',
            'branches_count_manual' => 12,
            'employees_count_manual' => 150,
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->put(route('organization.companies.update', $this->existingCompany), $updatePayload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('companies', [
            'id' => $this->existingCompany->id,
            'name' => 'Nexus Global Technologies Ltd',
            'company_type' => 'Public Limited',
            'relationship_type' => 'Holding',
            'shareholding_percentage' => 100.00,
            'city' => 'Mumbai',
            'branches_count_manual' => 12,
            'employees_count_manual' => 150,
        ]);
    }

    public function test_show_endpoint_returns_json_with_all_five_sections_data(): void
    {
        $this->existingCompany->update([
            'incorporation_no' => 'INC-112233',
            'cin_number' => 'U12345MH2020PTC000000',
            'logo_emoji' => '⚡',
            'city' => 'Navi Mumbai',
            'iec_code' => 'IEC998877',
            'msme_reg_no' => 'UDYAM-MH-02-1234567',
            'nature_of_business' => 'Cloud & Infrastructure Services',
            'authorized_capital' => 10000000,
            'paid_up_capital' => 5000000,
            'annual_turnover' => 20000000,
            'financial_year' => '2025-2026',
            'accounting_software' => 'SAP',
            'branches_count_manual' => 6,
            'employees_count_manual' => 85,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->get(route('organization.companies.show', $this->existingCompany));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'company' => [
                'id' => $this->existingCompany->id,
                'name' => 'Nexus Technologies Pvt Ltd',
                'company_type' => 'Private Limited',
                'cin_number' => 'U12345MH2020PTC000000',
                'logo_emoji' => '⚡',
                'city' => 'Navi Mumbai',
                'iec_code' => 'IEC998877',
                'nature_of_business' => 'Cloud & Infrastructure Services',
                'accounting_software' => 'SAP',
                'branches_count_manual' => 6,
                'employees_count_manual' => 85,
                'compliance_status' => 'Compliant',
            ],
        ]);
    }

    public function test_unauthorized_user_cannot_create_or_update_sister_concern(): void
    {
        $payload = [
            'name' => 'Unauthorized Attempt Co',
            'company_type' => 'Private Limited',
            'gstin' => '27AAACA1234B1Z5',
            'pan' => 'AAACA1234B',
        ];

        $response = $this->actingAs($this->regularUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('organization.companies.store'), $payload);

        $response->assertStatus(403);
    }
}

