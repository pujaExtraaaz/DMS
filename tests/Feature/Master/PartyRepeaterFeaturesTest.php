<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\PartyBankAccount;
use App\Domains\Master\Models\PartyCreditCheque;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyRepeaterFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;
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

    public function test_create_party_form_contains_add_address_and_bank_account_and_no_cheques(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('masters.customers.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Verify address and bank account buttons exist
        $this->assertStringContainsString('+ Add Address', $content);
        $this->assertStringContainsString('+ Add Bank Account', $content);

        // 2. Verify cheque section and button have been removed from the form
        $this->assertStringNotContainsString('+ Add Cheque', $content);
        $this->assertStringNotContainsString('Security / Credit Cheques (PDC)', $content);
        $this->assertStringNotContainsString('addCreditCheque()', $content);
        $this->assertStringNotContainsString('removeCreditCheque(', $content);
        $this->assertStringNotContainsString('name="credit_cheques', $content);

        // 3. Verify form encompasses address and bank account sections
        $formPos = strpos($content, '<form');
        $closeFormPos = strrpos($content, '</form>');
        $addAddressPos = strpos($content, '+ Add Address');
        $addBankPos = strpos($content, '+ Add Bank Account');

        $this->assertNotFalse($formPos);
        $this->assertNotFalse($closeFormPos);
        $this->assertTrue($addAddressPos > $formPos && $addAddressPos < $closeFormPos, 'Add Address button must be inside form');
        $this->assertTrue($addBankPos > $formPos && $addBankPos < $closeFormPos, 'Add Bank Account button must be inside form');

        // 4. Verify Alpine data and methods for address & bank accounts
        $this->assertStringContainsString('addAddress()', $content);
        $this->assertStringContainsString('addBankAccount()', $content);
        $this->assertStringContainsString('removeAddress(', $content);
        $this->assertStringContainsString('removeBankAccount(', $content);
        $this->assertStringContainsString('setDefaultBankAccount(', $content);
        $this->assertStringContainsString('default_bank_account_radio', $content);
    }

    public function test_can_save_party_with_multiple_addresses_bank_accounts_and_cheques(): void
    {
        $payload = [
            'name' => 'Dynamic Enterprise Corp',
            'party_type' => 'sundry_debtors',
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'phone' => '9876543210',
            'email' => 'dynamic@corp.example',
            'is_active' => '1',
            // Multiple Addresses
            'addresses' => [
                [
                    'label' => 'Registered Office',
                    'type' => 'both',
                    'contact_person' => 'Rahul Sharma',
                    'contact_phone' => '9876543210',
                    'address_line_1' => 'Plot 101, MIDC Industrial Area',
                    'address_line_2' => 'Near Power Station',
                    'state' => 'Maharashtra',
                    'city' => 'Mumbai',
                    'pincode' => '400001',
                    'is_default_billing' => '1',
                    'is_default_delivery' => '0',
                ],
                [
                    'label' => 'Bhiwandi Warehouse',
                    'type' => 'delivery',
                    'contact_person' => 'Anil Patil',
                    'contact_phone' => '9876543211',
                    'address_line_1' => 'Gala 4, Logistics Park',
                    'address_line_2' => 'Mankoli Naka',
                    'state' => 'Maharashtra',
                    'city' => 'Thane',
                    'pincode' => '421302',
                    'is_default_billing' => '0',
                    'is_default_delivery' => '1',
                ],
            ],
            // Multiple Bank Accounts
            'bank_accounts' => [
                [
                    'account_holder_name' => 'Dynamic Enterprise Corp',
                    'bank_name' => 'HDFC Bank',
                    'branch_name' => 'Nariman Point',
                    'account_number' => '50200012345678',
                    'account_type' => 'current',
                    'ifsc_code' => 'HDFC0000001',
                    'is_primary' => '1',
                ],
                [
                    'account_holder_name' => 'Dynamic Enterprise Corp',
                    'bank_name' => 'State Bank of India',
                    'branch_name' => 'Fort Branch',
                    'account_number' => '30999887766',
                    'account_type' => 'current',
                    'ifsc_code' => 'SBIN0000300',
                    'is_primary' => '0',
                ],
            ],
            // Multiple Cheques
            'credit_cheques' => [
                [
                    'cheque_number' => '000101',
                    'bank_name' => 'HDFC Bank',
                    'branch_name' => 'Nariman Point',
                    'cheque_date' => '2026-11-15',
                    'amount' => '50000.00',
                    'cheque_type' => 'pdc',
                    'status' => 'received',
                    'notes' => 'Advance security for November order',
                ],
                [
                    'cheque_number' => '000102',
                    'bank_name' => 'State Bank of India',
                    'branch_name' => 'Fort',
                    'cheque_date' => '2026-12-15',
                    'amount' => '75000.00',
                    'cheque_type' => 'security',
                    'status' => 'pending',
                    'notes' => 'Credit limit blanket security cheque',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('masters.customers.store'), $payload);

        $response->assertRedirect(route('masters.customers.index'));
        $response->assertSessionHas('status');

        $customer = Customer::where('name', 'Dynamic Enterprise Corp')->first();
        $this->assertNotNull($customer);

        // 1. Check addresses persisted
        $this->assertEquals(2, $customer->addresses()->count());
        $addr1 = $customer->addresses()->where('label', 'Registered Office')->first();
        $this->assertNotNull($addr1);
        $this->assertTrue((bool) $addr1->is_default_billing);
        $this->assertFalse((bool) $addr1->is_default_delivery);
        $this->assertEquals('Plot 101, MIDC Industrial Area', $addr1->address_line_1);
        $this->assertEquals('Mumbai', $addr1->city);

        $addr2 = $customer->addresses()->where('label', 'Bhiwandi Warehouse')->first();
        $this->assertNotNull($addr2);
        $this->assertFalse((bool) $addr2->is_default_billing);
        $this->assertTrue((bool) $addr2->is_default_delivery);
        $this->assertEquals('Thane', $addr2->city);

        // 2. Check bank accounts persisted
        $this->assertEquals(2, $customer->bankAccounts()->count());
        $bank1 = $customer->bankAccounts()->where('account_number', '50200012345678')->first();
        $this->assertNotNull($bank1);
        $this->assertEquals('Hdfc Bank', $bank1->bank_name);
        $this->assertTrue((bool) $bank1->is_primary);

        $bank2 = $customer->bankAccounts()->where('account_number', '30999887766')->first();
        $this->assertNotNull($bank2);
        $this->assertEquals('State Bank Of India', $bank2->bank_name);
        $this->assertFalse((bool) $bank2->is_primary);

        // 3. Check cheques persisted
        $this->assertEquals(2, $customer->creditCheques()->count());
        $chq1 = $customer->creditCheques()->where('cheque_number', '000101')->first();
        $this->assertNotNull($chq1);
        $this->assertEquals('50000.00', (string) $chq1->amount);
        $this->assertEquals('pdc', $chq1->cheque_type);
        $this->assertEquals('received', $chq1->status);
        $this->assertEquals('Advance security for November order', $chq1->notes);

        $chq2 = $customer->creditCheques()->where('cheque_number', '000102')->first();
        $this->assertNotNull($chq2);
        $this->assertEquals('75000.00', (string) $chq2->amount);
        $this->assertEquals('security', $chq2->cheque_type);
        $this->assertEquals('pending', $chq2->status);
    }

    public function test_edit_party_loads_saved_entries_and_supports_updating_and_removing(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'code' => 'CUST-REPEAT',
            'name' => 'Existing Multi-Card Corp',
            'party_type' => 'sundry_debtors',
            'is_active' => true,
        ]);

        $addr1 = $customer->addresses()->create([
            'label' => 'HQ',
            'type' => 'both',
            'address_line_1' => 'Old Street 1',
            'state' => 'Maharashtra',
            'city' => 'Pune',
            'pincode' => '411001',
            'is_default_billing' => true,
            'is_default_delivery' => true,
            'is_active' => true,
        ]);

        $addr2 = $customer->addresses()->create([
            'label' => 'Factory',
            'type' => 'delivery',
            'address_line_1' => 'Old Factory Road',
            'state' => 'Maharashtra',
            'city' => 'Nagpur',
            'pincode' => '440001',
            'is_default_billing' => false,
            'is_default_delivery' => false,
            'is_active' => true,
        ]);

        $bank1 = $customer->bankAccounts()->create([
            'bank_name' => 'ICICI Bank',
            'account_number' => '111122223333',
            'is_primary' => true,
        ]);

        $bank2 = $customer->bankAccounts()->create([
            'bank_name' => 'Axis Bank',
            'account_number' => '444455556666',
            'is_primary' => false,
        ]);

        $chq1 = $customer->creditCheques()->create([
            'cheque_number' => 'CHQ-9901',
            'bank_name' => 'ICICI Bank',
            'amount' => 25000,
            'status' => 'pending',
            'notes' => 'Original cheque 1',
        ]);

        $chq2 = $customer->creditCheques()->create([
            'cheque_number' => 'CHQ-9902',
            'bank_name' => 'Axis Bank',
            'amount' => 35000,
            'status' => 'pending',
            'notes' => 'Original cheque 2',
        ]);

        // 1. Verify Edit view renders persisted address & bank account entries (cheques no longer on form)
        $response = $this->actingAs($this->adminUser)->get(route('masters.customers.edit', $customer));
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('HQ', $content);
        $this->assertStringContainsString('Factory', $content);
        $this->assertStringContainsString('111122223333', $content);
        $this->assertStringContainsString('444455556666', $content);
        $this->assertStringNotContainsString('+ Add Cheque', $content);
        $this->assertStringNotContainsString('Security / Credit Cheques (PDC)', $content);

        // 2. Perform Update without credit_cheques: addresses and bank accounts update; existing cheques remain preserved
        $updatePayload = [
            'name' => 'Updated Multi-Card Corp',
            'party_type' => 'sundry_debtors',
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'is_active' => '1',
            'addresses' => [
                [
                    'id' => $addr1->id,
                    'label' => 'HQ Renovated',
                    'type' => 'both',
                    'address_line_1' => 'New Street 100',
                    'state' => 'Maharashtra',
                    'city' => 'Pune',
                    'pincode' => '411002',
                    'is_default_billing' => '1',
                    'is_default_delivery' => '1',
                ],
                [
                    'id' => null, // newly added address
                    'label' => 'New Hub',
                    'type' => 'delivery',
                    'address_line_1' => 'Hub Road 5',
                    'state' => 'Maharashtra',
                    'city' => 'Nashik',
                    'pincode' => '422001',
                    'is_default_billing' => '0',
                    'is_default_delivery' => '0',
                ],
            ],
            'bank_accounts' => [
                [
                    'id' => $bank1->id,
                    'bank_name' => 'ICICI Bank Main',
                    'account_number' => '111122223333',
                    'is_primary' => '1',
                ],
                [
                    'id' => null, // newly added bank
                    'bank_name' => 'Kotak Mahindra',
                    'account_number' => '777788889999',
                    'is_primary' => '0',
                ],
            ],
        ];

        $updateResponse = $this->actingAs($this->adminUser)->put(route('masters.customers.update', $customer), $updatePayload);
        $updateResponse->assertRedirect(route('masters.customers.index'));

        // Check updated addresses
        $this->assertEquals(2, $customer->addresses()->count());
        $this->assertDatabaseHas('party_addresses', [
            'id' => $addr1->id,
            'label' => 'HQ Renovated',
            'address_line_1' => 'New Street 100',
        ]);
        $this->assertDatabaseMissing('party_addresses', [
            'id' => $addr2->id,
        ]);
        $this->assertDatabaseHas('party_addresses', [
            'customer_id' => $customer->id,
            'label' => 'New Hub',
            'city' => 'Nashik',
        ]);

        // Check updated bank accounts
        $this->assertEquals(2, $customer->bankAccounts()->count());
        $this->assertDatabaseHas('party_bank_accounts', [
            'id' => $bank1->id,
            'account_number' => '111122223333',
        ]);
        $this->assertDatabaseMissing('party_bank_accounts', [
            'id' => $bank2->id,
        ]);
        $this->assertDatabaseHas('party_bank_accounts', [
            'customer_id' => $customer->id,
            'account_number' => '777788889999',
        ]);

        // Check existing cheques are PRESERVED and not deleted when form submits without cheque fields
        $this->assertEquals(2, $customer->creditCheques()->count());
        $this->assertDatabaseHas('party_credit_cheques', [
            'id' => $chq1->id,
            'cheque_number' => 'CHQ-9901',
        ]);
        $this->assertDatabaseHas('party_credit_cheques', [
            'id' => $chq2->id,
            'cheque_number' => 'CHQ-9902',
        ]);
    }

    public function test_validation_fails_when_cheque_details_given_without_cheque_number(): void
    {
        $payload = [
            'name' => 'Cheque Validation Test Party',
            'party_type' => 'sundry_debtors',
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'credit_cheques' => [
                [
                    'cheque_number' => '', // blank cheque number with details provided
                    'bank_name' => 'Axis Bank',
                    'amount' => '50000',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('masters.customers.store'), $payload);

        $response->assertSessionHasErrors(['credit_cheques.0.cheque_number']);
    }

    public function test_validation_fails_when_multiple_default_billing_addresses_submitted(): void
    {
        $payload = [
            'name' => 'Multi Default Billing Test Party',
            'party_type' => 'sundry_debtors',
            'customer_type_id' => $this->customerType->id,
            'area_id' => $this->area->id,
            'addresses' => [
                [
                    'label' => 'Office 1',
                    'address_line_1' => 'Road 1',
                    'is_default_billing' => '1',
                ],
                [
                    'label' => 'Office 2',
                    'address_line_1' => 'Road 2',
                    'is_default_billing' => '1',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('masters.customers.store'), $payload);

        $response->assertSessionHasErrors(['addresses']);
    }
}
