<?php

namespace Tests\Feature\Crm;

use App\Domains\Crm\Models\Lead;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeadCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['crm.view', 'crm.create', 'crm.edit', 'crm.manage', 'masters.customers.create', 'masters.customers.view'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(array $permissions = ['crm.view', 'crm.create', 'crm.edit', 'crm.manage', 'masters.customers.create']): User
    {
        $company = Company::firstOrCreate(
            ['code' => 'TESTCO'],
            ['name' => 'Test Company', 'currency' => 'INR', 'is_active' => true]
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_lead_model_has_category_constants_and_label_accessor(): void
    {
        $this->assertSame('prospect', Lead::CATEGORY_PROSPECT);
        $this->assertSame('customer', Lead::CATEGORY_CUSTOMER);
        $this->assertSame('partner', Lead::CATEGORY_PARTNER);

        $lead1 = new Lead(['category' => 'prospect']);
        $this->assertSame('Prospect', $lead1->category_label);

        $lead2 = new Lead(['category' => 'customer']);
        $this->assertSame('Customer', $lead2->category_label);

        $lead3 = new Lead(['category' => 'partner']);
        $this->assertSame('Partner', $lead3->category_label);

        $lead4 = new Lead(['category' => null]);
        $this->assertSame('Uncategorized', $lead4->category_label);

        $lead5 = new Lead(['category' => '']);
        $this->assertSame('Uncategorized', $lead5->category_label);
    }

    public function test_user_can_view_category_dropdown_on_create_lead_page(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('crm.leads.create'));

        $response->assertOk();
        $response->assertSee('Category');
        $response->assertSee('Select Category');
        $response->assertSee('Prospect');
        $response->assertSee('Customer');
        $response->assertSee('Partner');
    }

    public function test_user_can_create_lead_with_category(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post(route('crm.leads.store'), [
            'name' => 'Anil Verma',
            'company_name' => 'Verma Enterprises',
            'category' => 'prospect',
            'mobile' => '9876543210',
            'email' => 'anil@verma.com',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'name' => 'Anil Verma',
            'category' => 'prospect',
            'company_id' => $user->company_id,
        ]);
    }

    public function test_user_can_update_lead_category(): void
    {
        $user = $this->makeUser();
        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Suman Roy',
            'category' => 'prospect',
            'priority' => 'high',
            'status' => 'contacted',
        ]);

        $response = $this->actingAs($user)->put(route('crm.leads.update', $lead), [
            'name' => 'Suman Roy',
            'category' => 'partner',
            'priority' => 'high',
            'status' => 'contacted',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'category' => 'partner',
        ]);
    }

    public function test_lead_creation_rejects_invalid_category(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post(route('crm.leads.store'), [
            'name' => 'Invalid Category Test',
            'category' => 'nonexistent_category',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response->assertSessionHasErrors('category');
        $this->assertDatabaseMissing('leads', [
            'name' => 'Invalid Category Test',
        ]);
    }

    public function test_leads_listing_shows_category_filter_and_badges(): void
    {
        $user = $this->makeUser();

        $leadProspect = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Prospect Person',
            'category' => 'prospect',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $leadPartner = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Partner Person',
            'category' => 'partner',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $leadUncat = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Uncategorized Person',
            'category' => null,
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->get(route('crm.leads.index'));

        $response->assertOk();
        $response->assertSee('Category: All');
        $response->assertSee('Prospects');
        $response->assertSee('Customers');
        $response->assertSee('Partners');
        $response->assertSee('Uncategorized');

        $response->assertSee('Prospect Person');
        $response->assertSee('Partner Person');
        $response->assertSee('Uncategorized Person');
    }

    public function test_leads_listing_filters_by_category(): void
    {
        $user = $this->makeUser();

        $leadProspect = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Alpha Prospect',
            'category' => 'prospect',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $leadCustomer = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Beta Customer',
            'category' => 'customer',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $leadPartner = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Gamma Partner',
            'category' => 'partner',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $leadUncat = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Delta Unknown',
            'category' => null,
            'priority' => 'normal',
            'status' => 'new',
        ]);

        // 1. Filter by Prospect
        $resProspect = $this->actingAs($user)->get(route('crm.leads.index', ['category' => 'prospect']));
        $resProspect->assertOk();
        $resProspect->assertSee('Alpha Prospect');
        $resProspect->assertDontSee('Beta Customer');
        $resProspect->assertDontSee('Gamma Partner');
        $resProspect->assertDontSee('Delta Unknown');

        // 2. Filter by Customer
        $resCustomer = $this->actingAs($user)->get(route('crm.leads.index', ['category' => 'customer']));
        $resCustomer->assertOk();
        $resCustomer->assertSee('Beta Customer');
        $resCustomer->assertDontSee('Alpha Prospect');
        $resCustomer->assertDontSee('Gamma Partner');
        $resCustomer->assertDontSee('Delta Unknown');

        // 3. Filter by Partner
        $resPartner = $this->actingAs($user)->get(route('crm.leads.index', ['category' => 'partner']));
        $resPartner->assertOk();
        $resPartner->assertSee('Gamma Partner');
        $resPartner->assertDontSee('Alpha Prospect');
        $resPartner->assertDontSee('Beta Customer');
        $resPartner->assertDontSee('Delta Unknown');

        // 4. Filter by Uncategorized
        $resUncat = $this->actingAs($user)->get(route('crm.leads.index', ['category' => 'uncategorized']));
        $resUncat->assertOk();
        $resUncat->assertSee('Delta Unknown');
        $resUncat->assertDontSee('Alpha Prospect');
        $resUncat->assertDontSee('Beta Customer');
        $resUncat->assertDontSee('Gamma Partner');
    }

    public function test_multi_filter_category_status_and_search(): void
    {
        $user = $this->makeUser();

        $lead1 = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Vikram Seth',
            'company_name' => 'Seth Logistics',
            'category' => 'prospect',
            'status' => 'qualified',
            'priority' => 'high',
        ]);

        $lead2 = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Vikram Malhotra',
            'company_name' => 'Malhotra Traders',
            'category' => 'prospect',
            'status' => 'new',
            'priority' => 'high',
        ]);

        $lead3 = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Vikram Singh',
            'company_name' => 'Singh Enterprises',
            'category' => 'partner',
            'status' => 'qualified',
            'priority' => 'high',
        ]);

        $res = $this->actingAs($user)->get(route('crm.leads.index', [
            'category' => 'prospect',
            'status' => 'qualified',
            'search' => 'Seth',
        ]));

        $res->assertOk();
        $res->assertSee('Vikram Seth');
        $res->assertDontSee('Malhotra Traders');
        $res->assertDontSee('Singh Enterprises');
    }

    public function test_lead_conversion_updates_category_to_customer(): void
    {
        $user = $this->makeUser();

        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Meera Patel',
            'company_name' => 'Patel Solar Solutions',
            'mobile' => '9822001199',
            'category' => 'prospect',
            'status' => 'qualified',
            'priority' => 'high',
        ]);

        $response = $this->actingAs($user)->post(route('crm.leads.convert', $lead));

        $response->assertRedirect();
        $lead->refresh();

        $this->assertSame('converted', $lead->status);
        $this->assertSame('customer', $lead->category);
        $this->assertNotNull($lead->converted_customer_id);
    }

    public function test_customer_store_from_lead_updates_category_to_customer(): void
    {
        $user = $this->makeUser();
        $type = CustomerType::create(['name' => 'General', 'code' => 'GEN', 'is_active' => true]);

        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Rohan Joshi',
            'company_name' => 'Joshi Automation Ltd',
            'mobile' => '9833445566',
            'email' => 'rohan@joshiauto.com',
            'category' => 'partner',
            'status' => 'qualified',
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($user)->post(route('masters.customers.store'), [
            'lead_id' => $lead->id,
            'name' => 'Joshi Automation Ltd',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'customer_type_id' => $type->id,
            'phone' => '9833445566',
            'email' => 'rohan@joshiauto.com',
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $lead->refresh();

        $this->assertSame('converted', $lead->status);
        $this->assertSame('customer', $lead->category);
        $this->assertNotNull($lead->converted_customer_id);
    }

    public function test_lead_detail_page_displays_category_badge(): void
    {
        $user = $this->makeUser();

        $lead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Kiran Rao',
            'category' => 'partner',
            'priority' => 'normal',
            'status' => 'contacted',
        ]);

        $response = $this->actingAs($user)->get(route('crm.leads.show', $lead));

        $response->assertOk();
        $response->assertSee('Category');
        $response->assertSee('Partner');
    }

    public function test_bulk_upload_template_contains_category_column(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('crm.leads.bulk-upload.template', ['format' => 'csv']));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Category', $content);
        $this->assertStringContainsString('Prospect', $content);
    }

    public function test_bulk_upload_parses_category_and_validates_invalid_values(): void
    {
        $user = $this->makeUser();

        $headers = [
            'Contact Name', 'Company Name', 'Email', 'Secondary Email', 'Mobile', 'SECND MOB',
            'Phone', 'LANDLINE', 'Title', 'SALES PERSON', 'Tag', 'Category', 'Mailing City',
            'Mailing Zip', 'Mailing State', 'Mailing Street', 'SUB CATEGORY',
        ];

        $csvContent = implode(',', $headers) . "\n";

        // Row 1: Valid Prospect
        $csvContent .= implode(',', [
            'Valid Prospect Lead', 'Apex Corp', 'apex@example.com', '', '9811122233', '',
            '', '', 'Manager', '', 'TagA', 'Prospect', 'Pune', '411001', 'Maharashtra', 'FC Road', ''
        ]) . "\n";

        // Row 2: Valid Partner
        $csvContent .= implode(',', [
            'Valid Partner Lead', 'Zenith Systems', 'zenith@example.com', '', '9811122244', '',
            '', '', 'Director', '', 'TagB', 'Partner', 'Mumbai', '400001', 'Maharashtra', 'MG Road', ''
        ]) . "\n";

        // Row 3: Invalid Category
        $csvContent .= implode(',', [
            'Invalid Cat Lead', 'Nova Ltd', 'nova@example.com', '', '9811122255', '',
            '', '', '', '', '', 'UnknownCategory', 'Delhi', '110001', 'Delhi', 'Connaught Place', ''
        ]) . "\n";

        $file = UploadedFile::fake()->createWithContent('leads_with_categories.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('crm.leads.bulk-upload.preview'), [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertSee('Valid Prospect Lead');
        $response->assertSee('Valid Partner Lead');
        $response->assertSee('Invalid Category: \'UnknownCategory\'. Allowed categories are Prospect, Customer, Partner.');
        $response->assertSee('Confirm &amp; Import 2 Lead(s)', false);
    }

    public function test_bulk_upload_import_persists_categories_in_database(): void
    {
        $user = $this->makeUser();

        $headers = [
            'Contact Name', 'Company Name', 'Email', 'Secondary Email', 'Mobile', 'SECND MOB',
            'Phone', 'LANDLINE', 'Title', 'SALES PERSON', 'Tag', 'Category', 'Mailing City',
            'Mailing Zip', 'Mailing State', 'Mailing Street', 'SUB CATEGORY',
        ];

        $csvContent = implode(',', $headers) . "\n";
        $csvContent .= implode(',', [
            'Bulk Lead Customer', 'Customer Corp', 'cust@example.com', '', '9899112233', '',
            '', '', 'CEO', '', '', 'Customer', 'Bangalore', '560001', 'Karnataka', 'MG Road', ''
        ]) . "\n";

        $file = UploadedFile::fake()->createWithContent('leads_import.csv', $csvContent);

        // Preview
        $previewRes = $this->actingAs($user)->post(route('crm.leads.bulk-upload.preview'), [
            'file' => $file,
        ]);
        $previewRes->assertOk();

        // Retrieve import token from preview view data
        $token = $previewRes->viewData('importToken');
        $this->assertNotNull($token);

        // Execute import
        $importRes = $this->actingAs($user)->post(route('crm.leads.bulk-upload.import'), [
            'import_token' => $token,
        ]);

        $importRes->assertRedirect(route('crm.leads.index'));

        $this->assertDatabaseHas('leads', [
            'name' => 'Bulk Lead Customer',
            'category' => 'customer',
            'mobile' => '9899112233',
        ]);
    }
}

