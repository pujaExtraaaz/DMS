<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyBankAccount;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IfscLookupAutoFetchTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole($role);

        $this->company = Company::create([
            'name' => 'Apex Enterprises',
            'code' => 'APEX-01',
            'is_active' => true,
        ]);
        $this->superAdmin->update(['company_id' => $this->company->id]);
    }

    public function test_valid_ifsc_returns_bank_and_branch_details(): void
    {
        Http::fake([
            'https://ifsc.razorpay.com/HDFC0000001' => Http::response([
                'BANK' => 'HDFC Bank',
                'IFSC' => 'HDFC0000001',
                'BRANCH' => 'Kanjurmarg',
                'ADDRESS' => 'Ground Floor, Kanjurmarg East',
                'CITY' => 'Mumbai',
                'STATE' => 'Maharashtra',
                'MICR' => '400240002',
                'CONTACT' => '022-12345678',
            ], 200),
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson(route('masters.customers.ifsc-lookup', ['ifsc' => 'HDFC0000001'], false));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status_code' => 200,
            'message' => 'Bank and branch details fetched successfully.',
            'bank' => [
                'ifsc' => 'HDFC0000001',
                'bank_name' => 'HDFC Bank',
                'branch_name' => 'Kanjurmarg',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
            ],
        ]);

        // Verify result is cached
        $this->assertTrue(Cache::has('ifsc_lookup:HDFC0000001'));
    }

    public function test_ifsc_post_request_also_supported(): void
    {
        Http::fake([
            'https://ifsc.razorpay.com/SBIN0001234' => Http::response([
                'BANK' => 'State Bank of India',
                'IFSC' => 'SBIN0001234',
                'BRANCH' => 'Nariman Point',
                'ADDRESS' => 'Nariman Point, Mumbai',
                'CITY' => 'Mumbai',
                'STATE' => 'Maharashtra',
                'MICR' => '400002001',
                'CONTACT' => '022-87654321',
            ], 200),
        ]);

        $response = $this->actingAs($this->superAdmin)->postJson(route('masters.customers.ifsc-lookup', [], false), [
            'ifsc' => 'sbin0001234',
        ]);

        $response->assertOk();
        $response->assertJsonPath('bank.bank_name', 'State Bank of India');
        $response->assertJsonPath('bank.branch_name', 'Nariman Point');
    }

    public function test_invalid_ifsc_format_returns_validation_error(): void
    {
        Http::fake();

        $response = $this->actingAs($this->superAdmin)->getJson(route('masters.customers.ifsc-lookup', ['ifsc' => 'INVALID123'], false));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'status_code' => 422,
            'message' => 'Please enter a valid 11-character IFSC code.',
        ]);

        // Provider should never be called when regex fails
        Http::assertNothingSent();
    }

    public function test_empty_ifsc_returns_422(): void
    {
        $response = $this->actingAs($this->superAdmin)->getJson(route('masters.customers.ifsc-lookup', ['ifsc' => ''], false));

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_non_existent_ifsc_returns_404(): void
    {
        Http::fake([
            'https://ifsc.razorpay.com/HDFC0999999' => Http::response([], 404),
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson(route('masters.customers.ifsc-lookup', ['ifsc' => 'HDFC0999999'], false));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status_code' => 404,
            'message' => 'IFSC code not found. Please verify the code.',
        ]);
    }

    public function test_external_api_failure_returns_graceful_message(): void
    {
        Http::fake([
            'https://ifsc.razorpay.com/ICIC0001234' => Http::response('Server Error', 500),
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson(route('masters.customers.ifsc-lookup', ['ifsc' => 'ICIC0001234'], false));

        $response->assertStatus(502);
        $response->assertJson([
            'success' => false,
            'status_code' => 502,
            'message' => 'Unable to fetch bank details. Please enter details manually.',
        ]);
    }

    public function test_external_api_timeout_returns_graceful_message(): void
    {
        Http::fake([
            'https://ifsc.razorpay.com/KKBK0001234' => function () {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson(route('masters.customers.ifsc-lookup', ['ifsc' => 'KKBK0001234'], false));

        $response->assertStatus(504);
        $response->assertJson([
            'success' => false,
            'status_code' => 504,
            'message' => 'Unable to fetch bank details. Please enter details manually.',
        ]);
    }

    public function test_company_manager_role_can_access_ifsc_lookup(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $perm = Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $role->givePermissionTo($perm);

        $companyUser = User::factory()->create(['company_id' => $this->company->id]);
        $companyUser->assignRole($role);

        Http::fake([
            'https://ifsc.razorpay.com/PUNB0001234' => Http::response([
                'BANK' => 'Punjab National Bank',
                'IFSC' => 'PUNB0001234',
                'BRANCH' => 'Connaught Place',
            ], 200),
        ]);

        $response = $this->actingAs($companyUser)->getJson(route('masters.customers.ifsc-lookup', ['ifsc' => 'PUNB0001234'], false));

        $response->assertOk();
        $response->assertJsonPath('bank.bank_name', 'Punjab National Bank');
    }

    public function test_parties_form_blade_contains_ifsc_auto_fetch_bindings_and_methods(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('masters.customers.create'));

        $response->assertOk();
        $response->assertSee('onIfscInput(idx)', false);
        $response->assertSee('lookupIfsc(idx)', false);
        $response->assertSee('Fetching bank details...', false);
        $response->assertSee('Bank and branch details fetched successfully.', false);
        $response->assertSee('Please enter a valid 11-character IFSC code.', false);
        $response->assertSee('Unable to fetch bank details. Please enter details manually.', false);
    }

    public function test_companies_form_blade_contains_ifsc_auto_fetch_bindings_and_controller(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('organization.companies.create'));

        $response->assertOk();
        $response->assertSee('companyBankAccountsController', false);
        $response->assertSee('onIfscInput(index)', false);
        $response->assertSee('lookupIfsc(index)', false);
        $response->assertSee('Fetching bank details...', false);
        $response->assertSee('Bank and branch details fetched successfully.', false);
    }

    public function test_saving_party_with_multiple_bank_accounts_preserves_details(): void
    {
        $cType = CustomerType::create(['name' => 'Vendor', 'code' => 'VND', 'is_active' => true]);
        $area = Area::create(['name' => 'East Zone', 'code' => 'EZ', 'is_active' => true]);

        $payload = [
            'name' => 'Zenith Tech Solutions',
            'party_type' => 'sundry_creditors',
            'customer_type_id' => $cType->id,
            'area_id' => $area->id,
            'is_active' => '1',
            'bank_accounts' => [
                [
                    'account_holder_name' => 'Zenith Primary',
                    'bank_name' => 'HDFC Bank',
                    'branch_name' => 'Kanjurmarg',
                    'account_number' => '50200012345678',
                    'account_type' => 'current',
                    'ifsc_code' => 'HDFC0000001',
                    'is_primary' => '1',
                ],
                [
                    'account_holder_name' => 'Zenith Secondary',
                    'bank_name' => 'State Bank of India',
                    'branch_name' => 'Nariman Point',
                    'account_number' => '30200087654321',
                    'account_type' => 'savings',
                    'ifsc_code' => 'SBIN0001234',
                    'is_primary' => '0',
                ],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('masters.customers.store'), $payload);
        $response->assertRedirect();

        $customer = Customer::where('name', 'Zenith Tech Solutions')->firstOrFail();
        $this->assertCount(2, $customer->bankAccounts);

        $acc1 = $customer->bankAccounts()->where('ifsc_code', 'HDFC0000001')->firstOrFail();
        $this->assertEqualsIgnoringCase('HDFC Bank', $acc1->bank_name);
        $this->assertEqualsIgnoringCase('Kanjurmarg', $acc1->branch_name);
        $this->assertSame('50200012345678', $acc1->account_number);

        $acc2 = $customer->bankAccounts()->where('ifsc_code', 'SBIN0001234')->firstOrFail();
        $this->assertEqualsIgnoringCase('State Bank of India', $acc2->bank_name);
        $this->assertEqualsIgnoringCase('Nariman Point', $acc2->branch_name);
        $this->assertSame('30200087654321', $acc2->account_number);
    }

    public function test_saving_company_with_multiple_bank_accounts_preserves_details(): void
    {
        $payload = [
            'name' => 'Orion Global Industries',
            'legal_name' => 'Orion Global Industries Private Limited',
            'company_type' => 'Private Limited Company',
            'gstin' => '27ABCDE1234F1Z5',
            'pan' => 'ABCDE1234F',
            'is_active' => '1',
            'bank_accounts' => [
                [
                    'bank_name' => 'HDFC Bank',
                    'bank_account_no' => '50200011112222',
                    'bank_ifsc' => 'HDFC0000001',
                    'branch' => 'Kanjurmarg',
                    'upi_id' => 'orion@hdfcbank',
                    'swift_code' => 'HDFCINBB',
                    'bsr_code' => '0240002',
                    'client_code' => 'ORION01',
                ],
                [
                    'bank_name' => 'ICICI Bank',
                    'bank_account_no' => '001105001234',
                    'bank_ifsc' => 'ICIC0000002',
                    'branch' => 'Bandra Kurla Complex',
                    'upi_id' => 'orion@icici',
                    'swift_code' => 'ICICINBB',
                    'bsr_code' => '0000002',
                    'client_code' => 'ORION02',
                ],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('organization.companies.store'), $payload);
        $response->assertRedirect();

        $company = Company::where('name', 'Orion Global Industries')->firstOrFail();
        $accounts = $company->bank_accounts;

        $this->assertCount(2, $accounts);
        $this->assertSame('HDFC0000001', $accounts[0]['bank_ifsc']);
        $this->assertEqualsIgnoringCase('HDFC Bank', $accounts[0]['bank_name']);
        $this->assertEqualsIgnoringCase('Kanjurmarg', $accounts[0]['branch']);

        $this->assertSame('ICIC0000002', $accounts[1]['bank_ifsc']);
        $this->assertEqualsIgnoringCase('ICICI Bank', $accounts[1]['bank_name']);
        $this->assertEqualsIgnoringCase('Bandra Kurla Complex', $accounts[1]['branch']);
        $this->assertSame('Bandra Kurla Complex', $accounts[1]['branch']);
    }
}
