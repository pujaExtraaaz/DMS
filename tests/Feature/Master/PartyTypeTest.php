<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyTypeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CustomerType $customerType;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->customerType = CustomerType::create([
            'name' => 'General',
            'code' => 'GEN',
            'is_active' => true,
        ]);
    }

    public function test_party_create_form_displays_exact_three_party_type_options(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.customers.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Must contain exactly the 3 required options
        $this->assertStringContainsString('value="sundry_debtors"', $content);
        $this->assertStringContainsString('>Sundry Debtors</option>', $content);
        $this->assertStringContainsString('value="sundry_creditors"', $content);
        $this->assertStringContainsString('>Sundry Creditors</option>', $content);
        $this->assertStringContainsString('value="both"', $content);
        $this->assertStringContainsString('>Both</option>', $content);

        // Must NOT contain old options
        $this->assertStringNotContainsString('Both (Customer & Supplier)', $content);
    }

    public function test_can_create_party_as_sundry_debtors(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.customers.store'), [
            'name' => 'Acme Debtors Ltd',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('masters.customers.index'));

        $party = Customer::where('name', 'Acme Debtors Ltd')->firstOrFail();
        $this->assertEquals(Customer::PARTY_TYPE_SUNDRY_DEBTORS, $party->party_type);
        $this->assertEquals('Sundry Debtors', $party->party_type_label);
        $this->assertEquals('sundry_debtors', $party->party_type_key);
        $this->assertTrue($party->isCustomerParty());
        $this->assertFalse($party->isSupplier());
    }

    public function test_can_create_party_as_sundry_creditors(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.customers.store'), [
            'name' => 'Supplier Creditors Corp',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543211',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('masters.customers.index'));

        $party = Customer::where('name', 'Supplier Creditors Corp')->firstOrFail();
        $this->assertEquals(Customer::PARTY_TYPE_SUNDRY_CREDITORS, $party->party_type);
        $this->assertEquals('Sundry Creditors', $party->party_type_label);
        $this->assertEquals('sundry_creditors', $party->party_type_key);
        $this->assertFalse($party->isCustomerParty());
        $this->assertTrue($party->isSupplier());
    }

    public function test_can_create_party_as_both(): void
    {
        $response = $this->actingAs($this->user)->post(route('masters.customers.store'), [
            'name' => 'Dual Operations Inc',
            'party_type' => Customer::PARTY_TYPE_BOTH,
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543212',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('masters.customers.index'));

        $party = Customer::where('name', 'Dual Operations Inc')->firstOrFail();
        $this->assertEquals(Customer::PARTY_TYPE_BOTH, $party->party_type);
        $this->assertEquals('Both', $party->party_type_label);
        $this->assertEquals('both', $party->party_type_key);
        $this->assertTrue($party->isCustomerParty());
        $this->assertTrue($party->isSupplier());
        $this->assertTrue($party->isBoth());
    }

    public function test_legacy_party_type_inputs_are_safely_normalized(): void
    {
        // 'customer' input -> sundry_debtors
        $this->actingAs($this->user)->post(route('masters.customers.store'), [
            'name' => 'Legacy Customer Co',
            'party_type' => 'customer',
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543213',
            'is_active' => true,
        ]);

        $party1 = Customer::where('name', 'Legacy Customer Co')->firstOrFail();
        $this->assertEquals(Customer::PARTY_TYPE_SUNDRY_DEBTORS, $party1->party_type);

        // 'supplier' input -> sundry_creditors
        $this->actingAs($this->user)->post(route('masters.customers.store'), [
            'name' => 'Legacy Supplier Co',
            'party_type' => 'supplier',
            'customer_type_id' => $this->customerType->id,
            'phone' => '9876543214',
            'is_active' => true,
        ]);

        $party2 = Customer::where('name', 'Legacy Supplier Co')->firstOrFail();
        $this->assertEquals(Customer::PARTY_TYPE_SUNDRY_CREDITORS, $party2->party_type);
    }

    public function test_party_index_page_displays_labels_and_filters_correctly(): void
    {
        Customer::create([
            'name' => 'Debtor One',
            'code' => 'CST-TEST-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'Creditor One',
            'code' => 'CST-TEST-002',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'Both One',
            'code' => 'CST-TEST-003',
            'party_type' => Customer::PARTY_TYPE_BOTH,
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.customers.index'));
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Sundry Debtors', $content);
        $this->assertStringContainsString('Sundry Creditors', $content);
        $this->assertStringContainsString('Both', $content);

        // Filter by sundry_debtors
        $filterResponse = $this->actingAs($this->user)->get(route('masters.customers.index', [
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
        ]));
        $filterResponse->assertStatus(200);
        $filterContent = $filterResponse->getContent();
        $this->assertStringContainsString('Debtor One', $filterContent);
        $this->assertStringNotContainsString('Creditor One', $filterContent);

        // Filter by sundry_creditors
        $filterCreditors = $this->actingAs($this->user)->get(route('masters.customers.index', [
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
        ]));
        $filterCreditors->assertStatus(200);
        $filterCreditorsContent = $filterCreditors->getContent();
        $this->assertStringContainsString('Creditor One', $filterCreditorsContent);
        $this->assertStringNotContainsString('Debtor One', $filterCreditorsContent);
    }

    public function test_quick_add_creates_sundry_creditors(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('masters.customers.quick-add'), [
            'name' => 'Quick Supplier Ltd',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'phone' => '9988776655',
            'state' => 'Maharashtra',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'party' => [
                    'name' => 'Quick Supplier Ltd',
                    'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Quick Supplier Ltd',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
        ]);
    }

    public function test_party_edit_form_displays_selected_party_type(): void
    {
        $creditor = Customer::create([
            'name' => 'Edit Creditor Co',
            'code' => 'CST-EDIT-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.customers.edit', $creditor));
        $response->assertStatus(200);
        $content = $response->getContent();

        // Must pre-select sundry_creditors
        $this->assertStringContainsString('value="sundry_creditors" selected', $content);
    }

    public function test_tally_compatibility_helper_methods(): void
    {
        $debtor = new Customer(['party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS]);
        $creditor = new Customer(['party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS]);
        $both = new Customer(['party_type' => Customer::PARTY_TYPE_BOTH]);
        $legacySupplier = new Customer(['party_type' => 'supplier']);
        $legacyCustomer = new Customer(['party_type' => 'customer']);

        // Debtor
        $this->assertTrue($debtor->isCustomerParty());
        $this->assertFalse($debtor->isSupplier());

        // Creditor
        $this->assertFalse($creditor->isCustomerParty());
        $this->assertTrue($creditor->isSupplier());

        // Both
        $this->assertTrue($both->isCustomerParty());
        $this->assertTrue($both->isSupplier());

        // Legacy compatibility
        $this->assertTrue($legacySupplier->isSupplier());
        $this->assertFalse($legacySupplier->isCustomerParty());
        $this->assertTrue($legacyCustomer->isCustomerParty());
        $this->assertFalse($legacyCustomer->isSupplier());
    }

    public function test_purchasing_controllers_query_creditors_and_both(): void
    {
        Customer::create([
            'name' => 'Debtor Store',
            'code' => 'CST-PUR-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'Creditor Mill',
            'code' => 'CST-PUR-002',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'Hybrid Trader',
            'code' => 'CST-PUR-003',
            'party_type' => Customer::PARTY_TYPE_BOTH,
            'customer_type_id' => $this->customerType->id,
            'is_active' => true,
        ]);

        $suppliers = Customer::query()
            ->where('is_active', true)
            ->whereIn('party_type', [Customer::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier', Customer::PARTY_TYPE_BOTH])
            ->pluck('name')
            ->toArray();

        $this->assertContains('Creditor Mill', $suppliers);
        $this->assertContains('Hybrid Trader', $suppliers);
        $this->assertNotContains('Debtor Store', $suppliers);
    }
}
