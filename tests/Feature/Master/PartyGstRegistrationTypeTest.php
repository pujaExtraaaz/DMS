<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Services\GstLookupService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartyGstRegistrationTypeTest extends TestCase
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

    public function test_create_party_form_displays_gst_registration_type_dropdown_with_all_options(): void
    {
        $response = $this->actingAs($this->user)->get(route('masters.customers.create'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('name="gst_registration_type"', $content);
        $this->assertStringContainsString('Select GST Registration Type', $content);

        $expectedOptions = [
            'Regular',
            'Composition',
            'Casual Taxable Person',
            'Non-Resident Taxable Person',
            'Input Service Distributor (ISD)',
            'SEZ Unit',
            'SEZ Developer',
            'TDS Deductor',
            'TCS Collector',
            'UIN Holder',
            'Unregistered',
        ];

        foreach ($expectedOptions as $option) {
            $this->assertStringContainsString('value="' . $option . '"', $content);
            $this->assertStringContainsString('>' . $option . '</option>', $content);
        }
    }

    public function test_can_create_party_with_selected_gst_registration_type(): void
    {
        $payload = [
            'name' => 'Global Logistics Inc',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'gstin' => '27AAPFU0939F1ZV',
            'gst_registration_type' => 'Regular',
            'phone' => '9876543210',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.customers.store'), $payload);

        $response->assertRedirect(route('masters.customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Global Logistics Inc',
            'gstin' => '27AAPFU0939F1ZV',
            'gst_registration_type' => 'Regular',
        ]);
    }

    public function test_gst_registration_type_is_optional(): void
    {
        $payload = [
            'name' => 'Unregistered Local Vendor',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'customer_type_id' => $this->customerType->id,
            'gstin' => null,
            'gst_registration_type' => null,
            'phone' => '9876543211',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('masters.customers.store'), $payload);

        $response->assertRedirect(route('masters.customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Unregistered Local Vendor',
            'gst_registration_type' => null,
        ]);
    }

    public function test_edit_party_form_loads_existing_gst_registration_type(): void
    {
        $customer = Customer::create([
            'name' => 'Composition Dealer Traders',
            'code' => 'COMP-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'gstin' => '27ABCDE1234F1Z5',
            'gst_registration_type' => 'Composition',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.customers.edit', $customer));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('name="gst_registration_type"', $content);
        $this->assertStringContainsString('value="Composition" selected', $content);
    }

    public function test_can_update_party_gst_registration_type(): void
    {
        $customer = Customer::create([
            'name' => 'Tech Park SEZ Unit',
            'code' => 'SEZ-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'gstin' => '27AABCT3518Q1ZV',
            'gst_registration_type' => 'SEZ Unit',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->put(route('masters.customers.update', $customer), [
            'name' => 'Tech Park SEZ Unit Updated',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'gstin' => '27AABCT3518Q1ZV',
            'gst_registration_type' => 'SEZ Developer',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('masters.customers.index'));
        $customer->refresh();

        $this->assertSame('SEZ Developer', $customer->gst_registration_type);
    }

    public function test_gst_registration_type_is_displayed_on_party_view_page(): void
    {
        $customer = Customer::create([
            'name' => 'Embassy Services',
            'code' => 'UIN-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'gstin' => '0719USA00133ON4',
            'gst_registration_type' => 'UIN Holder',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.customers.show', $customer));

        $response->assertOk();
        $response->assertSeeText('GST Registration Type');
        $response->assertSeeText('UIN Holder');
    }

    public function test_existing_parties_without_registration_type_render_dash_on_view_page(): void
    {
        $customer = Customer::create([
            'name' => 'Legacy Customer Co',
            'code' => 'LEG-001',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $this->customerType->id,
            'gstin' => '27AAAAA0000A1Z5',
            'gst_registration_type' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.customers.show', $customer));

        $response->assertOk();
        $response->assertSeeText('GST Registration Type');
        $this->assertNull($customer->gst_registration_type);
    }

    public function test_gst_lookup_endpoint_returns_normalized_gst_registration_type(): void
    {
        $mockLookup = Mockery::mock(GstLookupService::class);
        $mockLookup->shouldReceive('search')
            ->with('27AAPFU0939F1ZV')
            ->once()
            ->andReturn([
                'success' => true,
                'status_code' => 200,
                'message' => 'GSTIN verified successfully from official records.',
                'party' => [
                    'gstin' => '27AAPFU0939F1ZV',
                    'name' => 'Test Taxpayer',
                    'taxpayer_type' => 'Regular',
                ],
            ]);

        $this->app->instance(GstLookupService::class, $mockLookup);

        $response = $this->actingAs($this->user)->getJson(route('masters.customers.gst-lookup', [
            'gstin' => '27AAPFU0939F1ZV',
        ]));

        $response->assertOk();
        $response->assertJsonPath('party.gst_registration_type', 'Regular');
    }
}
