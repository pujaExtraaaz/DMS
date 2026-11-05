<?php

namespace Tests\Feature\Organization;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyBankAdditionalFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected BusinessGroup $businessGroup;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.edit', 'guard_name' => 'web']);

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

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
            'company_type' => 'Private Limited Company',
            'gstin' => '27AAAAA0000A1Z5',
            'pan' => 'AAAAA0000A',
            'is_active' => true,
            'bank_name' => 'HDFC Bank',
            'bank_account_no' => '50200012345678',
            'bank_ifsc' => 'HDFC0001234',
            'upi_id' => 'apex@hdfcbank',
            'swift_code' => 'HDFCINBBXXX',
            'bank_branch' => 'Bandra Kurla Complex',
            'bsr_code' => '0001234',
            'client_code' => '00987CORP',
            'additional_details' => [
                'bank_accounts' => [
                    [
                        'bank_name' => 'ICICI Bank',
                        'bank_account_no' => '001122334455',
                        'bank_ifsc' => 'ICIC0000011',
                        'upi_id' => null,
                        'swift_code' => 'ICICINBB',
                        'branch' => 'Nariman Point Branch',
                        'bank_branch' => 'Nariman Point Branch',
                        'bsr_code' => '0054321',
                        'client_code' => '0045ICICI',
                    ],
                ],
            ],
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->adminUser->assignRole($roleAdmin);
    }

    public function test_company_create_and_edit_forms_render_all_four_new_bank_fields(): void
    {
        // Check Company Create Form
        $createResponse = $this->actingAs($this->adminUser)->get(route('organization.companies.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('Banking &amp; Payments', false);
        $createResponse->assertSee('SWIFT Code');
        $createResponse->assertSee('Branch');
        $createResponse->assertSee('BSR Code');
        $createResponse->assertSee('Client Code');

        // Check Company Edit Form
        $editResponse = $this->actingAs($this->adminUser)->get(route('organization.companies.edit', $this->company));
        $editResponse->assertOk();
        $editResponse->assertSee('HDFCINBBXXX');
        $editResponse->assertSee('Bandra Kurla Complex');
        $editResponse->assertSee('0001234');
        $editResponse->assertSee('00987CORP');
        $editResponse->assertSee('ICICINBB');
        $editResponse->assertSee('Nariman Point Branch');

        // Check Company Profile Form
        $profileResponse = $this->actingAs($this->adminUser)->get(route('organization.company-profile'));
        $profileResponse->assertOk();
        $profileResponse->assertSee('SWIFT Code');
        $profileResponse->assertSee('Branch');
        $profileResponse->assertSee('BSR Code');
        $profileResponse->assertSee('Client Code');
    }

    public function test_company_show_view_renders_all_bank_fields_including_leading_zeros(): void
    {
        $showResponse = $this->actingAs($this->adminUser)->get(route('organization.companies.show', $this->company));
        $showResponse->assertOk();

        // Primary account
        $showResponse->assertSee('HDFCINBBXXX');
        $showResponse->assertSee('Bandra Kurla Complex');
        $showResponse->assertSee('0001234'); // Preserves leading zeros
        $showResponse->assertSee('00987CORP');

        // Second account
        $showResponse->assertSee('ICICINBB');
        $showResponse->assertSee('Nariman Point Branch');
        $showResponse->assertSee('0054321');
        $showResponse->assertSee('0045ICICI');
    }

    public function test_can_create_company_with_multiple_bank_accounts_and_additional_fields(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('organization.companies.store'), [
            'name' => 'Beta Global Logistics Pvt Ltd',
            'code' => 'BETA01',
            'company_type' => 'Private Limited Company',
            'gstin' => '27BBBBB1111B1Z2',
            'pan' => 'BBBBB1111B',
            'is_active' => true,
            'bank_accounts' => [
                [
                    'bank_name' => 'State Bank of India',
                    'bank_account_no' => '300011122233',
                    'bank_ifsc' => 'SBIN0001234',
                    'upi_id' => 'beta@sbi',
                    'swift_code' => 'SBININBB',
                    'branch' => 'Fort Mumbai Branch',
                    'bsr_code' => '0007890',
                    'client_code' => '00123SBI',
                ],
                [
                    'bank_name' => 'Axis Bank',
                    'bank_account_no' => '910020030040',
                    'bank_ifsc' => 'UTIB0000004',
                    'upi_id' => null,
                    'swift_code' => 'AXISINBBXXX',
                    'branch' => 'Worli Corporate Office',
                    'bsr_code' => '0004567',
                    'client_code' => 'AXIS-CORP-001',
                ],
            ],
        ]);

        $response->assertRedirect(route('organization.companies.index'));

        $newCompany = Company::where('code', 'BETA01')->first();
        $this->assertNotNull($newCompany);

        // Verify primary bank fields saved directly to columns
        $this->assertSame('State Bank Of India', $newCompany->bank_name);
        $this->assertSame('300011122233', $newCompany->bank_account_no);
        $this->assertSame('SBIN0001234', $newCompany->bank_ifsc);
        $this->assertSame('beta@sbi', $newCompany->upi_id);
        $this->assertSame('SBININBB', $newCompany->swift_code);
        $this->assertSame('Fort Mumbai Branch', $newCompany->bank_branch);
        $this->assertSame('Fort Mumbai Branch', $newCompany->branch);
        $this->assertSame('0007890', $newCompany->bsr_code);
        $this->assertSame('00123SBI', $newCompany->client_code);

        // Verify secondary bank fields saved to additional_details
        $accounts = $newCompany->bank_accounts;
        $this->assertCount(2, $accounts);

        $this->assertSame('Axis Bank', $accounts[1]['bank_name']);
        $this->assertSame('910020030040', $accounts[1]['bank_account_no']);
        $this->assertSame('UTIB0000004', $accounts[1]['bank_ifsc']);
        $this->assertNull($accounts[1]['upi_id']);
        $this->assertSame('AXISINBBXXX', $accounts[1]['swift_code']);
        $this->assertSame('Worli Corporate Office', $accounts[1]['branch']);
        $this->assertSame('Worli Corporate Office', $accounts[1]['bank_branch']);
        $this->assertSame('0004567', $accounts[1]['bsr_code']);
        $this->assertSame('AXIS-CORP-001', $accounts[1]['client_code']);
    }

    public function test_can_update_company_profile_with_additional_bank_fields(): void
    {
        $response = $this->actingAs($this->adminUser)->put(route('organization.company-profile.update'), [
            'name' => 'Apex Technologies Updated',
            'bank_accounts' => [
                [
                    'bank_name' => 'Kotak Mahindra Bank',
                    'bank_account_no' => '654321098765',
                    'bank_ifsc' => 'KKBK0000123',
                    'upi_id' => 'apex@kotak',
                    'swift_code' => 'KKBKINBB',
                    'branch' => 'Khar West',
                    'bsr_code' => '0003322',
                    'client_code' => 'KOTAK-999',
                    'od_limit' => 200000.00,
                    'interest_rate' => 9.50,
                ],
            ],
        ]);

        $response->assertRedirect(route('organization.company-profile'));

        $this->company->refresh();
        $this->assertSame('Kotak Mahindra Bank', $this->company->bank_name);
        $this->assertSame('654321098765', $this->company->bank_account_no);
        $this->assertSame('KKBK0000123', $this->company->bank_ifsc);
        $this->assertSame('apex@kotak', $this->company->upi_id);
        $this->assertSame('KKBKINBB', $this->company->swift_code);
        $this->assertSame('Khar West', $this->company->bank_branch);
        $this->assertSame('Khar West', $this->company->branch);
        $this->assertSame('0003322', $this->company->bsr_code);
        $this->assertSame('KOTAK-999', $this->company->client_code);
    }

    public function test_validates_swift_code_format_when_provided(): void
    {
        // Invalid SWIFT (too short / invalid pattern)
        $response = $this->actingAs($this->adminUser)->post(route('organization.companies.store'), [
            'name' => 'Invalid Swift Co',
            'company_type' => 'Private Limited Company',
            'gstin' => '27CCCCC2222C1Z3',
            'pan' => 'CCCCC2222C',
            'bank_accounts' => [
                [
                    'bank_name' => 'HDFC Bank',
                    'bank_account_no' => '50200012345678',
                    'swift_code' => 'INVALID_SWIFT_123456', // Too long / invalid characters
                ],
            ],
        ]);

        $response->assertSessionHasErrors('bank_accounts.0.swift_code');

        // Valid 8-char SWIFT
        $valid8Response = $this->actingAs($this->adminUser)->post(route('organization.companies.store'), [
            'name' => 'Valid Swift 8 Co',
            'company_type' => 'Private Limited Company',
            'gstin' => '27DDDDD3333D1Z4',
            'pan' => 'DDDDD3333D',
            'bank_accounts' => [
                [
                    'bank_name' => 'HDFC Bank',
                    'bank_account_no' => '50200012345678',
                    'swift_code' => 'HDFCINBB',
                ],
            ],
        ]);
        $valid8Response->assertSessionHasNoErrors();

        // Valid 11-char SWIFT
        $valid11Response = $this->actingAs($this->adminUser)->post(route('organization.companies.store'), [
            'name' => 'Valid Swift 11 Co',
            'company_type' => 'Private Limited Company',
            'gstin' => '27EEEEE4444E1Z5',
            'pan' => 'EEEEE4444E',
            'bank_accounts' => [
                [
                    'bank_name' => 'HDFC Bank',
                    'bank_account_no' => '50200012345678',
                    'swift_code' => 'HDFCINBBXXX',
                ],
            ],
        ]);
        $valid11Response->assertSessionHasNoErrors();
    }

    public function test_empty_optional_fields_are_handled_cleanly(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('organization.companies.store'), [
            'name' => 'Empty Bank Fields Co',
            'company_type' => 'Private Limited Company',
            'gstin' => '27FFFFF5555F1Z6',
            'pan' => 'FFFFF5555F',
            'bank_accounts' => [
                [
                    'bank_name' => 'HDFC Bank',
                    'bank_account_no' => '50200012345678',
                    'bank_ifsc' => 'HDFC0001234',
                    'upi_id' => null,
                    'swift_code' => null,
                    'branch' => null,
                    'bsr_code' => null,
                    'client_code' => null,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $company = Company::where('name', 'Empty Bank Fields Co')->first();
        $this->assertNotNull($company);
        $this->assertNull($company->swift_code);
        $this->assertNull($company->bank_branch);
        $this->assertNull($company->branch);
        $this->assertNull($company->bsr_code);
        $this->assertNull($company->client_code);
    }
}

