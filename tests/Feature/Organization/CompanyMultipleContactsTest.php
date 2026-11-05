<?php

namespace Tests\Feature\Organization;

use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyMultipleContactsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.edit', 'guard_name' => 'web']);

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Acme Corporation',
            'code' => 'ACME-001',
            'phone' => '+91 9876543210',
            'email' => 'primary@acme.com',
            'website' => 'https://acme.com',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'address' => '101 Nariman Point, Mumbai',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);
    }

    public function test_can_save_multiple_contacts_from_company_profile(): void
    {
        $payload = [
            'name' => 'Acme Corporation',
            'code' => 'ACME-001',
            'is_active' => 1,
            'contacts' => [
                [
                    'phone' => '+91 9876543210',
                    'email' => 'primary@acme.com',
                    'website' => 'https://acme.com',
                    'state' => 'Maharashtra',
                    'pincode' => '400001',
                    'address' => '101 Nariman Point, Mumbai',
                ],
                [
                    'phone' => '+91 9123456789',
                    'email' => 'support@acme.com',
                    'website' => 'https://support.acme.com',
                    'state' => 'Karnataka',
                    'pincode' => '560001',
                    'address' => '202 Indiranagar, Bengaluru',
                ],
                [
                    'phone' => '+91 9988776655',
                    'email' => 'billing@acme.com',
                    'website' => 'https://billing.acme.com',
                    'state' => 'Delhi',
                    'pincode' => '110001',
                    'address' => '303 Connaught Place, New Delhi',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), $payload);

        $response->assertRedirect(route('organization.company-profile'));
        $response->assertSessionHas('status');

        $this->company->refresh();

        // Primary contact persists in direct columns
        $this->assertSame('+91 9876543210', $this->company->phone);
        $this->assertSame('primary@acme.com', $this->company->email);
        $this->assertSame('101 Nariman Point, Mumbai', $this->company->address);

        // Additional contacts persist in additional_details['contacts']
        $additional = $this->company->additional_details['contacts'] ?? [];
        $this->assertCount(2, $additional);
        $this->assertSame('+91 9123456789', $additional[0]['phone']);
        $this->assertSame('support@acme.com', $additional[0]['email']);
        $this->assertSame('+91 9988776655', $additional[1]['phone']);
        $this->assertSame('billing@acme.com', $additional[1]['email']);

        // Model contacts accessor returns all 3 contacts
        $allContacts = $this->company->contacts;
        $this->assertCount(3, $allContacts);
        $this->assertSame('primary@acme.com', $allContacts[0]['email']);
        $this->assertSame('support@acme.com', $allContacts[1]['email']);
        $this->assertSame('billing@acme.com', $allContacts[2]['email']);
    }

    public function test_company_profile_screen_renders_all_contacts_correctly(): void
    {
        $this->company->update([
            'phone' => '1111111111',
            'email' => 'primary@test.com',
            'additional_details' => [
                'contacts' => [
                    [
                        'phone' => '2222222222',
                        'email' => 'second@test.com',
                        'website' => '',
                        'state' => 'Gujarat',
                        'pincode' => '380001',
                        'address' => 'Branch office, Ahmedabad',
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('organization.company-profile'));

        $response->assertOk();
        $response->assertSee('primary@test.com');
        $response->assertSee('second@test.com');
        $response->assertSee('Branch office, Ahmedabad');
        $response->assertSee('Contact Details');
        $response->assertSee('Primary Contact');
        $response->assertSee('Additional Contact');
    }

    public function test_can_remove_second_contact_and_save(): void
    {
        // Setup initial 2 contacts
        $this->company->update([
            'phone' => '1111111111',
            'email' => 'primary@test.com',
            'additional_details' => [
                'contacts' => [
                    [
                        'phone' => '2222222222',
                        'email' => 'second@test.com',
                        'website' => '',
                        'state' => 'Goa',
                        'pincode' => '403001',
                        'address' => 'Goa branch',
                    ],
                ],
            ],
        ]);

        // Submit form with only Contact #1 (Contact #2 was removed in UI)
        $payload = [
            'name' => $this->company->name,
            'code' => $this->company->code,
            'is_active' => 1,
            'contacts' => [
                [
                    'phone' => '1111111111',
                    'email' => 'primary@test.com',
                    'website' => '',
                    'state' => '',
                    'pincode' => '',
                    'address' => '',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), $payload);

        $response->assertRedirect(route('organization.company-profile'));

        $this->company->refresh();
        $this->assertEmpty($this->company->additional_details['contacts'] ?? []);
        $this->assertCount(1, $this->company->contacts);
    }

    public function test_saving_from_company_controller_preserves_additional_contacts(): void
    {
        $this->company->update([
            'additional_details' => [
                'contacts' => [
                    [
                        'phone' => '9999999999',
                        'email' => 'dept@acme.com',
                        'website' => '',
                        'state' => 'Maharashtra',
                        'pincode' => '400050',
                        'address' => 'Bandra office',
                    ],
                ],
            ],
        ]);

        $payload = [
            'name' => 'Acme Corporation Updated',
            'code' => 'ACME-001',
            'company_type' => 'pvt_ltd',
            'gstin' => '27ABCDE1234F1Z5',
            'pan' => 'ABCDE1234F',
            'phone' => '+91 9876543210',
            'email' => 'primary@acme.com',
            'is_active' => '1',
            'contacts' => [
                [
                    'phone' => '+91 9876543210',
                    'email' => 'primary@acme.com',
                    'website' => '',
                    'state' => 'Maharashtra',
                    'pincode' => '400001',
                    'address' => '101 Nariman Point',
                ],
                [
                    'phone' => '9999999999',
                    'email' => 'dept@acme.com',
                    'website' => '',
                    'state' => 'Maharashtra',
                    'pincode' => '400050',
                    'address' => 'Bandra office',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->put(route('organization.companies.update', $this->company), $payload);

        $response->assertRedirect(route('organization.companies.index'));

        $this->company->refresh();
        $this->assertSame('Acme Corporation Updated', $this->company->name);
        $additional = $this->company->additional_details['contacts'] ?? [];
        $this->assertCount(1, $additional);
        $this->assertSame('dept@acme.com', $additional[0]['email']);
    }

    public function test_invalid_email_in_additional_contact_triggers_validation_error(): void
    {
        $payload = [
            'name' => $this->company->name,
            'code' => $this->company->code,
            'contacts' => [
                [
                    'phone' => '1111111111',
                    'email' => 'valid@test.com',
                ],
                [
                    'phone' => '2222222222',
                    'email' => 'not-an-email',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), $payload);

        $response->assertSessionHasErrors('contacts.1.email');
    }
}
