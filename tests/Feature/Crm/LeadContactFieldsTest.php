<?php

namespace Tests\Feature\Crm;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Crm\Imports\LeadsImport;
use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\LeadCampaign;
use App\Domains\Crm\Models\LeadSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeadContactFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['crm.view', 'crm.create', 'crm.edit', 'crm.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(array $permissions = ['crm.view', 'crm.create', 'crm.edit']): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_create_lead_form_contains_all_specified_contact_and_address_fields(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('crm.leads.create'));

        $response->assertOk();
        $response->assertSee('Contact Name');
        $response->assertSee('Company Name');
        $response->assertSee('Title');
        $response->assertSee('Secondary Email');
        $response->assertSee('Second Mobile Number (SECND MOB)');
        $response->assertSee('Phone');
        $response->assertSee('Landline');
        $response->assertSee('Sales Person');
        $response->assertSee('Tag');
        $response->assertSee('Sub Category');
        $response->assertSee('Mailing Street');
        $response->assertSee('Mailing City');
        $response->assertSee('Mailing State');
        $response->assertSee('Mailing Zip');
    }

    public function test_can_create_lead_with_all_contact_and_address_fields(): void
    {
        $user = $this->makeUser();
        $salesperson = User::factory()->create(['name' => 'Amit Verma']);
        $cat = Category::create(['name' => 'Solar Power', 'code' => 'SOLAR', 'is_active' => true]);
        $subCat = SubCategory::create(['category_id' => $cat->id, 'name' => 'Inverters', 'code' => 'INV', 'is_active' => true]);
        $source = LeadSource::create(['name' => 'LinkedIn', 'code' => 'LNK', 'is_active' => true]);
        $campaign = LeadCampaign::create(['name' => 'Q4 Outreach', 'platform' => 'meta', 'is_active' => true]);

        $payload = [
            'name' => 'Sunil Gupta',
            'company_name' => 'Gupta Tech Corp',
            'title' => 'Managing Director',
            'email' => 'sunil@guptatech.com',
            'secondary_email' => 'finance@guptatech.com',
            'mobile' => '9820011222',
            'secondary_mobile' => '9820033444',
            'phone' => '022-28776655',
            'landline' => '022-28776650',
            'assigned_to' => $salesperson->id,
            'tag' => 'VIP, High Value',
            'sub_category_id' => $subCat->id,
            'street' => 'Suite 501, Crystal Plaza, New Link Road',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'zip' => '400053',
            'lead_source_id' => $source->id,
            'lead_campaign_id' => $campaign->id,
            'priority' => 'high',
            'status' => 'new',
            'interested_product' => 'Grid-Tie Solar Inverter 10kW',
            'notes' => 'Looking for complete distributor pricing.',
        ];

        $response = $this->actingAs($user)->post(route('crm.leads.store'), $payload);

        $lead = Lead::where('mobile', '9820011222')->first();
        $this->assertNotNull($lead);
        $this->assertEquals('Sunil Gupta', $lead->name);
        $this->assertEquals('Gupta Tech Corp', $lead->company_name);
        $this->assertEquals('Gupta Tech Corp', $lead->organization);
        $this->assertEquals('Managing Director', $lead->title);
        $this->assertEquals('sunil@guptatech.com', $lead->email);
        $this->assertEquals('finance@guptatech.com', $lead->secondary_email);
        $this->assertEquals('9820033444', $lead->secondary_mobile);
        $this->assertEquals('022-28776655', $lead->phone);
        $this->assertEquals('022-28776650', $lead->landline);
        $this->assertEquals($salesperson->id, $lead->assigned_to);
        $this->assertEquals('VIP, High Value', $lead->tag);
        $this->assertEquals($subCat->id, $lead->sub_category_id);
        $this->assertEquals('Inverters', $lead->sub_category);
        $this->assertEquals('Suite 501, Crystal Plaza, New Link Road', $lead->street);
        $this->assertEquals('Mumbai', $lead->city);
        $this->assertEquals('Maharashtra', $lead->state);
        $this->assertEquals('400053', $lead->zip);

        $response->assertRedirect(route('crm.leads.show', $lead));
    }

    public function test_edit_lead_screen_displays_persisted_values(): void
    {
        $user = $this->makeUser();
        $cat = Category::create(['name' => 'Industrial', 'code' => 'IND', 'is_active' => true]);
        $subCat = SubCategory::create(['category_id' => $cat->id, 'name' => 'Batteries', 'code' => 'BAT', 'is_active' => true]);

        $lead = Lead::create([
            'name' => 'Priya Mehta',
            'company_name' => 'Mehta Logistics',
            'title' => 'Operations Head',
            'email' => 'priya@mehtalogistics.in',
            'secondary_email' => 'ops@mehtalogistics.in',
            'mobile' => '9988112233',
            'secondary_mobile' => '9988445566',
            'phone' => '011-45678901',
            'landline' => '011-45678900',
            'tag' => 'Logistics Partner',
            'sub_category_id' => $subCat->id,
            'sub_category' => 'Batteries',
            'street' => 'Warehouse 14, Transport Nagar',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'zip' => '110037',
            'priority' => 'urgent',
            'status' => 'contacted',
        ]);

        $response = $this->actingAs($user)->get(route('crm.leads.edit', $lead));

        $response->assertOk();
        $response->assertSee('Edit CRM Lead');
        $response->assertSee('Priya Mehta');
        $response->assertSee('Mehta Logistics');
        $response->assertSee('Operations Head');
        $response->assertSee('priya@mehtalogistics.in');
        $response->assertSee('ops@mehtalogistics.in');
        $response->assertSee('9988112233');
        $response->assertSee('9988445566');
        $response->assertSee('011-45678901');
        $response->assertSee('011-45678900');
        $response->assertSee('Logistics Partner');
        $response->assertSee('Warehouse 14, Transport Nagar');
        $response->assertSee('Delhi');
        $response->assertSee('110037');
    }

    public function test_can_update_lead_and_save_modifications(): void
    {
        $user = $this->makeUser();

        $lead = Lead::create([
            'name' => 'Original Name',
            'mobile' => '9800000001',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $updatePayload = [
            'name' => 'Updated Contact Name',
            'company_name' => 'New Company Ltd',
            'title' => 'Chief Executive',
            'email' => 'ceo@newcompany.com',
            'secondary_email' => 'contact@newcompany.com',
            'mobile' => '9800000001',
            'secondary_mobile' => '9800000002',
            'phone' => '020-25556666',
            'landline' => '020-25556667',
            'tag' => 'Tier 1 Account',
            'street' => 'B-wing, Cerebrum IT Park',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'zip' => '411014',
            'priority' => 'high',
            'status' => 'qualified',
        ];

        $response = $this->actingAs($user)->put(route('crm.leads.update', $lead), $updatePayload);

        $response->assertRedirect(route('crm.leads.show', $lead));

        $lead->refresh();
        $this->assertEquals('Updated Contact Name', $lead->name);
        $this->assertEquals('New Company Ltd', $lead->company_name);
        $this->assertEquals('Chief Executive', $lead->title);
        $this->assertEquals('ceo@newcompany.com', $lead->email);
        $this->assertEquals('contact@newcompany.com', $lead->secondary_email);
        $this->assertEquals('9800000002', $lead->secondary_mobile);
        $this->assertEquals('020-25556666', $lead->phone);
        $this->assertEquals('020-25556667', $lead->landline);
        $this->assertEquals('Tier 1 Account', $lead->tag);
        $this->assertEquals('B-wing, Cerebrum IT Park', $lead->street);
        $this->assertEquals('Pune', $lead->city);
        $this->assertEquals('411014', $lead->zip);
        $this->assertEquals('qualified', $lead->status);
    }

    public function test_search_matches_contact_name_company_mobile_email_and_tag(): void
    {
        $user = $this->makeUser();

        Lead::create([
            'name' => 'Aarav Singhania',
            'company_name' => 'Singhania Textiles',
            'email' => 'aarav@singhaniatex.com',
            'mobile' => '9111222333',
            'tag' => 'TextileWholesale',
            'city' => 'Surat',
            'state' => 'Gujarat',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        Lead::create([
            'name' => 'Bhavin Patel',
            'company_name' => 'Patel Electronics',
            'email' => 'bhavin@patelelec.com',
            'mobile' => '9444555666',
            'tag' => 'RetailElectronics',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        // Search by company name
        $response = $this->actingAs($user)->get(route('crm.leads.index', ['search' => 'Singhania']));
        $response->assertSee('Aarav Singhania');
        $response->assertDontSee('Bhavin Patel');

        // Search by tag
        $response = $this->actingAs($user)->get(route('crm.leads.index', ['search' => 'RetailElectronics']));
        $response->assertSee('Bhavin Patel');
        $response->assertDontSee('Aarav Singhania');

        // Search by mobile
        $response = $this->actingAs($user)->get(route('crm.leads.index', ['search' => '9111222333']));
        $response->assertSee('Aarav Singhania');
        $response->assertDontSee('Bhavin Patel');
    }

    public function test_leads_bulk_import_maps_excel_columns_correctly(): void
    {
        $cat = Category::create(['name' => 'Renewables', 'code' => 'REN', 'is_active' => true]);
        $subCat = SubCategory::create(['category_id' => $cat->id, 'name' => 'Solar Panels', 'code' => 'PANEL', 'is_active' => true]);
        $salesUser = User::factory()->create(['name' => 'Karan Malhotra', 'email' => 'karan@example.com']);

        $importRows = new Collection([
            [
                'Contact Name' => 'Vikram Oberoi',
                'Company Name' => 'Oberoi Heavy Electricals',
                'Title' => 'Director',
                'Email' => 'vikram@oberoipower.com',
                'Secondary Email' => 'accounts@oberoipower.com',
                'Mobile' => '9877001122',
                'SECND MOB' => '9877003344',
                'Phone' => '022-24331122',
                'Landline' => '022-24331100',
                'Sales Person' => 'Karan Malhotra',
                'Tag' => 'Large Tender',
                'Sub Category' => 'Solar Panels',
                'Mailing Street' => 'Plot 88, Kanjurmarg West',
                'Mailing City' => 'Mumbai',
                'Mailing State' => 'Maharashtra',
                'Mailing Zip' => '400078',
            ],
            [
                'Contact Name' => '', // Invalid row: missing contact name
                'Mobile' => '9999999999',
            ],
        ]);

        $import = new LeadsImport();
        $import->collection($importRows);

        $this->assertEquals(1, $import->created);
        $this->assertEquals(1, $import->skipped);
        $this->assertNotEmpty($import->errors);
        $this->assertEquals('contact_name', $import->errors[0]['field']);

        $lead = Lead::where('mobile', '9877001122')->first();
        $this->assertNotNull($lead);
        $this->assertEquals('Vikram Oberoi', $lead->name);
        $this->assertEquals('Oberoi Heavy Electricals', $lead->company_name);
        $this->assertEquals('Director', $lead->title);
        $this->assertEquals('vikram@oberoipower.com', $lead->email);
        $this->assertEquals('accounts@oberoipower.com', $lead->secondary_email);
        $this->assertEquals('9877003344', $lead->secondary_mobile);
        $this->assertEquals('022-24331122', $lead->phone);
        $this->assertEquals('022-24331100', $lead->landline);
        $this->assertEquals($salesUser->id, $lead->assigned_to);
        $this->assertEquals('Large Tender', $lead->tag);
        $this->assertEquals($subCat->id, $lead->sub_category_id);
        $this->assertEquals('Solar Panels', $lead->sub_category);
        $this->assertEquals('Plot 88, Kanjurmarg West', $lead->street);
        $this->assertEquals('Mumbai', $lead->city);
        $this->assertEquals('Maharashtra', $lead->state);
        $this->assertEquals('400078', $lead->zip);
    }
}

