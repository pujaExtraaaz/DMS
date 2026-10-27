<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyBankAccount;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyBankAccountTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;
    protected Warehouse $warehouse;
    protected CustomerType $customerType;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);

        $this->company = Company::create([
            'name' => 'Apex Enterprises',
            'code' => 'APEX-01',
            'is_active' => true,
        ]);

        $this->adminUser->update(['company_id' => $this->company->id]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Central Warehouse',
            'code' => 'CW-01',
            'is_active' => true,
        ]);

        $this->customerType = CustomerType::create([
            'company_id' => $this->company->id,
            'name' => 'Retailer',
            'code' => 'RET-01',
            'is_active' => true,
        ]);

        $this->area = Area::create([
            'company_id' => $this->company->id,
            'name' => 'Downtown',
            'code' => 'DT-01',
            'is_active' => true,
        ]);
    }

    public function test_can_save_bank_account_with_empty_account_number(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'code' => 'CUST-001',
            'name' => 'Sony India Private Limited',
            'party_type' => 'sundry_creditors',
            'gstin' => '27AABCS1429B1Z1',
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Sony India Private Limited',
            'party_type' => 'sundry_creditors',
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'gstin' => '27AABCS1429B1Z1',
            'is_active' => '1',
            'bank_accounts' => [
                [
                    'account_holder_name' => 'Sony India Private Limited',
                    'bank_name' => 'Hdfc Bank',
                    'branch_name' => 'Park Street',
                    'account_number' => '',
                    'account_type' => 'current',
                    'ifsc_code' => 'HDFC0001234',
                    'is_primary' => '1',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('masters.customers.update', $customer), $payload);

        $response->assertRedirect(route('masters.customers.index'));

        $this->assertDatabaseHas('party_bank_accounts', [
            'customer_id' => $customer->id,
            'account_holder_name' => 'Sony India Private Limited',
            'bank_name' => 'Hdfc Bank',
            'branch_name' => 'Park Street',
            'account_number' => null,
            'ifsc_code' => 'HDFC0001234',
            'account_type' => 'current',
            'is_primary' => 1,
        ]);
    }

    public function test_empty_bank_account_rows_are_not_persisted(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'code' => 'CUST-002',
            'name' => 'Test Customer',
            'party_type' => 'sundry_debtors',
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Test Customer',
            'party_type' => 'sundry_debtors',
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'is_active' => '1',
            'bank_accounts' => [
                [
                    'account_holder_name' => '',
                    'bank_name' => '',
                    'branch_name' => '',
                    'account_number' => '',
                    'account_type' => 'current',
                    'ifsc_code' => '',
                    'is_primary' => '0',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('masters.customers.update', $customer), $payload);

        $response->assertRedirect(route('masters.customers.index'));

        $this->assertDatabaseMissing('party_bank_accounts', [
            'customer_id' => $customer->id,
        ]);
    }
}
