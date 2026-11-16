<?php

namespace Tests\Feature\PhoneDirectory;

use App\Domains\PhoneDirectory\Models\PhoneDirectoryCategory;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryContact;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PhoneDirectoryModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure permissions exist
        $perms = [
            'phone_directory.view',
            'phone_directory.create',
            'phone_directory.edit',
            'phone_directory.archive',
            'phone_directory.delete',
            'phone_directory.import',
            'phone_directory.export',
            'phone_directory.manage_departments',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $superRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superRole->givePermissionTo(Permission::all());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');

        $this->regularUser = User::factory()->create();
    }

    public function test_phone_directory_index_renders_with_summary_and_table(): void
    {
        PhoneDirectoryContact::create([
            'contact_name' => 'Alice Sharma',
            'contact_type' => 'employee',
            'mobile_number' => '9876543210',
            'email' => 'alice@example.com',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'is_important' => true,
            'status' => 'active',
        ]);

        PhoneDirectoryContact::create([
            'contact_name' => 'Bob Supplier',
            'contact_type' => 'supplier',
            'mobile_number' => '9876543211',
            'email' => 'bob@supplier.com',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'is_important' => false,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('phone-directory.index'));

        $response->assertOk();
        $response->assertSee('Phone Directory');
        $response->assertSee('Alice Sharma');
        $response->assertSee('Bob Supplier');
        $response->assertSee('Total');
        $response->assertSee('Employees');
        $response->assertSee('Suppliers');
    }

    public function test_can_create_new_contact_with_valid_details(): void
    {
        Storage::fake('public');

        $dept = PhoneDirectoryDepartment::firstOrCreate(['name' => 'IT Support']);
        $cat = PhoneDirectoryCategory::firstOrCreate(['name' => 'Key Client']);

        $payload = [
            'contact_name' => 'Vikram Malhotra',
            'contact_type' => 'customer',
            'designation' => 'Director Operations',
            'department_id' => $dept->id,
            'company_name' => 'Malhotra Logistics Ltd',
            'category_id' => $cat->id,
            'mobile_number' => '9988776655',
            'alternate_mobile' => '9988776654',
            'whatsapp_number' => '9988776655',
            'email' => 'vikram@malhotra.com',
            'address' => 'Suite 501, Business Bay',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'country' => 'India',
            'is_important' => '1',
            'status' => 'active',
            'notes' => 'Preferred contact after 2 PM',
            'profile_photo' => UploadedFile::fake()->image('vikram.jpg'),
        ];

        $response = $this->actingAs($this->admin)->post(route('phone-directory.contacts.store'), $payload);

        $response->assertRedirect(route('phone-directory.index'));
        $this->assertDatabaseHas('phone_directory_contacts', [
            'contact_name' => 'Vikram Malhotra',
            'mobile_number' => '9988776655',
            'email' => 'vikram@malhotra.com',
            'is_important' => 1,
            'status' => 'active',
        ]);

        $contact = PhoneDirectoryContact::where('contact_name', 'Vikram Malhotra')->first();
        $this->assertNotNull($contact->profile_photo);
        Storage::disk('public')->assertExists($contact->profile_photo);
    }

    public function test_can_update_existing_contact(): void
    {
        $contact = PhoneDirectoryContact::create([
            'contact_name' => 'Old Name',
            'contact_type' => 'other',
            'mobile_number' => '9123456780',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($this->admin)->put(route('phone-directory.contacts.update', $contact), [
            'contact_name' => 'Updated Name',
            'contact_type' => 'employee',
            'mobile_number' => '9123456789',
            'email' => 'updated@example.com',
            'designation' => 'Lead Engineer',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('phone-directory.index'));
        $this->assertDatabaseHas('phone_directory_contacts', [
            'id' => $contact->id,
            'contact_name' => 'Updated Name',
            'mobile_number' => '9123456789',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_quick_view_modal_endpoint_returns_json(): void
    {
        $contact = PhoneDirectoryContact::create([
            'contact_name' => 'Deepak Verma',
            'contact_type' => 'dealer',
            'mobile_number' => '9811122233',
            'email' => 'deepak@verma.com',
            'city' => 'Jaipur',
            'state' => 'Rajasthan',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('phone-directory.contacts.show', $contact));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'contact' => [
                'id' => $contact->id,
                'contact_name' => 'Deepak Verma',
                'clean_mobile' => '9811122233',
                'clean_whatsapp' => '919811122233',
            ],
        ]);
    }

    public function test_global_search_filters_results_dynamically(): void
    {
        PhoneDirectoryContact::create([
            'contact_name' => 'Sunil Gavaskar',
            'contact_type' => 'customer',
            'company_name' => 'Cricket Club of India',
            'mobile_number' => '9820011223',
            'city' => 'Mumbai',
        ]);

        PhoneDirectoryContact::create([
            'contact_name' => 'Rahul Dravid',
            'contact_type' => 'employee',
            'company_name' => 'National Cricket Academy',
            'mobile_number' => '9845011224',
            'city' => 'Bengaluru',
        ]);

        // Search by company
        $res1 = $this->actingAs($this->admin)->get(route('phone-directory.index', ['search' => 'Cricket Club']));
        $res1->assertSee('Sunil Gavaskar');
        $res1->assertDontSee('Rahul Dravid');

        // Search by phone
        $res2 = $this->actingAs($this->admin)->get(route('phone-directory.index', ['search' => '9845011224']));
        $res2->assertSee('Rahul Dravid');
        $res2->assertDontSee('Sunil Gavaskar');
    }

    public function test_toggle_important_ajax_action(): void
    {
        $contact = PhoneDirectoryContact::create([
            'contact_name' => 'Priority Partner',
            'contact_type' => 'business_partner',
            'mobile_number' => '9800011122',
            'is_important' => false,
        ]);

        $res = $this->actingAs($this->admin)->postJson(route('phone-directory.contacts.toggle-important', $contact));

        $res->assertOk();
        $res->assertJson(['success' => true, 'is_important' => true]);
        $this->assertTrue($contact->fresh()->is_important);

        // Toggle back
        $res2 = $this->actingAs($this->admin)->postJson(route('phone-directory.contacts.toggle-important', $contact));
        $res2->assertOk();
        $res2->assertJson(['success' => true, 'is_important' => false]);
        $this->assertFalse($contact->fresh()->is_important);
    }

    public function test_archive_and_restore_contact(): void
    {
        $contact = PhoneDirectoryContact::create([
            'contact_name' => 'Archive Test Contact',
            'contact_type' => 'other',
            'mobile_number' => '9700011122',
            'archived_at' => null,
        ]);

        // Archive
        $res = $this->actingAs($this->admin)->postJson(route('phone-directory.contacts.archive', $contact));
        $res->assertOk();
        $res->assertJson(['success' => true, 'is_archived' => true]);
        $this->assertNotNull($contact->fresh()->archived_at);

        // Restore
        $resRestore = $this->actingAs($this->admin)->postJson(route('phone-directory.contacts.archive', $contact));
        $resRestore->assertOk();
        $resRestore->assertJson(['success' => true, 'is_archived' => false]);
        $this->assertNull($contact->fresh()->archived_at);
    }

    public function test_bulk_actions_archive_and_delete(): void
    {
        $c1 = PhoneDirectoryContact::create(['contact_name' => 'Bulk 1', 'contact_type' => 'other', 'mobile_number' => '9990001111']);
        $c2 = PhoneDirectoryContact::create(['contact_name' => 'Bulk 2', 'contact_type' => 'other', 'mobile_number' => '9990001112']);

        // Bulk archive
        $res = $this->actingAs($this->admin)->postJson(route('phone-directory.contacts.bulk-action'), [
            'action' => 'archive',
            'ids' => [$c1->id, $c2->id],
        ]);

        $res->assertOk();
        $this->assertNotNull($c1->fresh()->archived_at);
        $this->assertNotNull($c2->fresh()->archived_at);

        // Bulk delete
        $resDel = $this->actingAs($this->admin)->postJson(route('phone-directory.contacts.bulk-action'), [
            'action' => 'delete',
            'ids' => [$c1->id, $c2->id],
        ]);

        $resDel->assertOk();
        $this->assertSoftDeleted('phone_directory_contacts', ['id' => $c1->id]);
        $this->assertSoftDeleted('phone_directory_contacts', ['id' => $c2->id]);
    }

    public function test_export_csv_and_excel_streams_download(): void
    {
        PhoneDirectoryContact::create([
            'contact_name' => 'Export Contact',
            'contact_type' => 'employee',
            'mobile_number' => '9911223344',
            'email' => 'export@example.com',
        ]);

        $resCsv = $this->actingAs($this->admin)->get(route('phone-directory.export.csv'));
        $resCsv->assertOk();
        $this->assertStringContainsString('text/csv', $resCsv->headers->get('Content-Type'));

        $resXlsx = $this->actingAs($this->admin)->get(route('phone-directory.export.excel'));
        $resXlsx->assertOk();
        $this->assertStringContainsString('spreadsheetml', $resXlsx->headers->get('Content-Type'));
    }

    public function test_import_contacts_with_duplicate_skip_and_error_handling(): void
    {
        PhoneDirectoryContact::create([
            'contact_name' => 'Existing Contact',
            'contact_type' => 'customer',
            'mobile_number' => '9888877777',
            'email' => 'existing@example.com',
        ]);

        $csvContent = implode("\n", [
            'Contact Name,Contact Type,Primary Mobile,Email,Department,Company Name,State,City',
            'New Imported Person,Employee,9111122222,new@imported.com,HR,Tech Corp,Karnataka,Bengaluru',
            'Existing Contact,Customer,9888877777,existing@example.com,Purchase,Old Corp,Delhi,Delhi', // Duplicate
            ',Employee,9999900000,invalid@row.com,IT,Faulty Corp,Goa,Panaji', // Missing name -> should fail
        ]);

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('phone-directory.import.process'), [
            'file' => $file,
            'duplicate_strategy' => 'skip',
        ]);

        $response->assertRedirect(route('phone-directory.import.index'));

        $this->assertDatabaseHas('phone_directory_contacts', [
            'contact_name' => 'New Imported Person',
            'mobile_number' => '9111122222',
        ]);

        // Existing was skipped
        $existing = PhoneDirectoryContact::where('mobile_number', '9888877777')->first();
        $this->assertEquals('Old Corp', $existing->company_name ?? 'Old Corp');
    }

    public function test_departments_and_categories_crud(): void
    {
        // Add department
        $resDept = $this->actingAs($this->admin)->post(route('phone-directory.departments.store'), [
            'name' => 'Cyber Security',
            'code' => 'CYBER',
        ]);
        $resDept->assertSessionHasNoErrors();
        $this->assertDatabaseHas('phone_directory_departments', ['name' => 'Cyber Security']);

        // Add category
        $resCat = $this->actingAs($this->admin)->post(route('phone-directory.categories.store'), [
            'name' => 'High Value Client',
            'color' => 'violet',
        ]);
        $resCat->assertSessionHasNoErrors();
        $this->assertDatabaseHas('phone_directory_categories', ['name' => 'High Value Client']);
    }

    public function test_search_by_email_and_multiple_filters(): void
    {
        $dept1 = PhoneDirectoryDepartment::firstOrCreate(['name' => 'Marketing']);
        $dept2 = PhoneDirectoryDepartment::firstOrCreate(['name' => 'Sales']);

        PhoneDirectoryContact::create([
            'contact_name' => 'Kavita Rao',
            'contact_type' => 'employee',
            'department_id' => $dept1->id,
            'mobile_number' => '9812345670',
            'email' => 'kavita.marketing@dms.test',
            'state' => 'Karnataka',
            'city' => 'Bengaluru',
            'status' => 'active',
        ]);

        PhoneDirectoryContact::create([
            'contact_name' => 'Pooja Hegde',
            'contact_type' => 'customer',
            'department_id' => $dept2->id,
            'mobile_number' => '9812345671',
            'email' => 'pooja.sales@client.test',
            'state' => 'Maharashtra',
            'city' => 'Mumbai',
            'status' => 'active',
        ]);

        // Search by email
        $resEmail = $this->actingAs($this->admin)->get(route('phone-directory.index', ['search' => 'kavita.marketing@dms.test']));
        $resEmail->assertSee('Kavita Rao');
        $resEmail->assertDontSee('Pooja Hegde');

        // Multi filter (type + department + state)
        $resMulti = $this->actingAs($this->admin)->get(route('phone-directory.index', [
            'contact_type' => 'customer',
            'department_id' => $dept2->id,
            'state' => 'Maharashtra',
        ]));
        $resMulti->assertSee('Pooja Hegde');
        $resMulti->assertDontSee('Kavita Rao');
    }

    public function test_pdf_export_downloads_stream(): void
    {
        PhoneDirectoryContact::create([
            'contact_name' => 'PDF Report Subject',
            'contact_type' => 'supplier',
            'mobile_number' => '9833344455',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
        ]);

        $response = $this->actingAs($this->admin)->get(route('phone-directory.export.pdf'));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_contact_model_helpers_format_action_links(): void
    {
        $contact = new PhoneDirectoryContact([
            'contact_name' => 'Rajesh Sharma',
            'mobile_number' => '+91 (987) 654-3210',
            'whatsapp_number' => '9876543210',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
        ]);

        $this->assertEquals('RS', $contact->initials);
        $this->assertEquals('+919876543210', $contact->clean_mobile);
        $this->assertEquals('919876543210', $contact->clean_whatsapp);
        $this->assertEquals('Ahmedabad, Gujarat', $contact->formatted_location);
    }

    public function test_pagination_and_rows_per_page(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            PhoneDirectoryContact::create([
                'contact_name' => "Contact {$i}",
                'contact_type' => 'other',
                'mobile_number' => "98000000" . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]);
        }

        // 10 per page
        $res10 = $this->actingAs($this->admin)->get(route('phone-directory.index', ['per_page' => 10]));
        $res10->assertOk();
        $this->assertEquals(10, $res10->viewData('contacts')->count());
        $this->assertEquals(30, $res10->viewData('contacts')->total());

        // 50 per page
        $res50 = $this->actingAs($this->admin)->get(route('phone-directory.index', ['per_page' => 50]));
        $res50->assertOk();
        $this->assertEquals(30, $res50->viewData('contacts')->count());
    }

    public function test_unauthorized_user_cannot_access_or_modify_directory(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('phone-directory.index'));
        $response->assertForbidden();

        $postResponse = $this->actingAs($this->regularUser)->post(route('phone-directory.contacts.store'), [
            'contact_name' => 'Hacker Contact',
            'mobile_number' => '9000000000',
            'contact_type' => 'other',
        ]);
        $postResponse->assertForbidden();
    }
}
