<?php

namespace Tests\Feature\Crm;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Crm\Models\Lead;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeadBulkUploadFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['crm.view', 'crm.create', 'crm.edit', 'crm.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(array $permissions = ['crm.view', 'crm.create', 'crm.edit', 'crm.manage']): User
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

    public function test_crm_leads_listing_displays_bulk_upload_button(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('crm.leads.index'));

        $response->assertOk();
        $response->assertSee('Bulk Upload');
        $response->assertSee(route('crm.leads.bulk-upload'));
        $response->assertSee('+ New Lead');
    }

    public function test_template_download_csv_contains_all_specified_headers(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('crm.leads.bulk-upload.template', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('Contact Name', $content);
        $this->assertStringContainsString('Company Name', $content);
        $this->assertStringContainsString('Email', $content);
        $this->assertStringContainsString('Secondary Email', $content);
        $this->assertStringContainsString('Mobile', $content);
        $this->assertStringContainsString('SECND MOB', $content);
        $this->assertStringContainsString('Phone', $content);
        $this->assertStringContainsString('LANDLINE', $content);
        $this->assertStringContainsString('Title', $content);
        $this->assertStringContainsString('SALES PERSON', $content);
        $this->assertStringContainsString('Tag', $content);
        $this->assertStringContainsString('Mailing City', $content);
        $this->assertStringContainsString('Mailing Zip', $content);
        $this->assertStringContainsString('Mailing State', $content);
        $this->assertStringContainsString('Mailing Street', $content);
        $this->assertStringContainsString('SUB CATEGORY', $content);
    }

    public function test_template_download_xlsx_succeeds(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('crm.leads.bulk-upload.template', ['format' => 'xlsx']));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type'));
    }

    public function test_preview_validates_rows_and_detects_valid_invalid_and_duplicates(): void
    {
        $user = $this->makeUser();
        $salesperson = User::factory()->create(['name' => 'Deepak Shah', 'email' => 'deepak@example.com']);
        $cat = Category::create(['name' => 'Electricals', 'code' => 'ELEC', 'is_active' => true]);
        $subCat = SubCategory::create(['category_id' => $cat->id, 'name' => 'Cables', 'code' => 'CAB', 'is_active' => true]);

        // Pre-existing lead in DB to test duplicate detection
        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Existing Person',
            'mobile' => '9820011111',
            'email' => 'existing@test.com',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        // Build a CSV with 3 rows: 1 valid, 1 invalid (missing name), 1 duplicate (matching existing mobile)
        $csvContent = implode(',', [
            'Contact Name', 'Company Name', 'Email', 'Secondary Email', 'Mobile', 'SECND MOB',
            'Phone', 'LANDLINE', 'Title', 'SALES PERSON', 'Tag', 'Mailing City', 'Mailing Zip',
            'Mailing State', 'Mailing Street', 'SUB CATEGORY'
        ]) . "\n";

        // Row 2: Valid
        $csvContent .= implode(',', [
            'Kavita Nair', 'Nair Industries', 'kavita@nairind.com', 'info@nairind.com', '9833002233', '9833004455',
            '022-25551122', '022-25551133', 'Director', 'Deepak Shah', 'Wholesale VIP', 'Mumbai', '400001',
            'Maharashtra', '101 Marine Lines', 'Cables'
        ]) . "\n";

        // Row 3: Invalid (Missing Contact Name)
        $csvContent .= implode(',', [
            '', 'Ghost Corp', 'ghost@example.com', '', '9811223344', '',
            '', '', '', '', '', '', '', '', '', ''
        ]) . "\n";

        // Row 4: Duplicate mobile
        $csvContent .= implode(',', [
            'Duplicate Contact', 'Clone Tech', 'clone@example.com', '', '9820011111', '',
            '', '', '', '', '', '', '', '', '', ''
        ]) . "\n";

        $file = UploadedFile::fake()->createWithContent('leads_sample.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('crm.leads.bulk-upload.preview'), [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertSee('CRM Leads: Bulk Upload');
        $response->assertSee('Kavita Nair');
        $response->assertSee('Contact Name is required.');
        $response->assertSee('Duplicate mobile/contact number already exists in the database');
        $response->assertSee('Confirm &amp; Import 1 Lead(s)', false);
    }

    public function test_import_saves_valid_leads_and_handles_duplicates_and_errors(): void
    {
        $user = $this->makeUser();
        $salesperson = User::factory()->create(['name' => 'Sanjay Rawat']);

        // Build sample CSV
        $csvContent = implode(',', [
            'Contact Name', 'Company Name', 'Email', 'Secondary Email', 'Mobile', 'SECND MOB',
            'Phone', 'LANDLINE', 'Title', 'SALES PERSON', 'Tag', 'Mailing City', 'Mailing Zip',
            'Mailing State', 'Mailing Street', 'SUB CATEGORY'
        ]) . "\n";

        $csvContent .= implode(',', [
            'Rohan Deshmukh', 'Deshmukh Solar', 'rohan@deshmukhsolar.in', 'sec@deshmukhsolar.in', '9766554433', '9766554422',
            '020-24441122', '020-24441133', 'Owner', 'Sanjay Rawat', 'Key Account', 'Pune', '411001',
            'Maharashtra', 'FC Road 12', 'Solar'
        ]) . "\n";

        $file = UploadedFile::fake()->createWithContent('leads.csv', $csvContent);

        // First, preview to generate import token
        $previewResponse = $this->actingAs($user)->post(route('crm.leads.bulk-upload.preview'), [
            'file' => $file,
        ]);
        $previewResponse->assertOk();

        // Extract import token from rendered HTML
        preg_match('/name="import_token"\s+value="([^"]+)"/', $previewResponse->getContent(), $matches);
        $this->assertNotEmpty($matches[1], 'Import token not found in preview HTML');
        $token = $matches[1];

        // Now confirm the import
        $importResponse = $this->actingAs($user)->post(route('crm.leads.bulk-upload.import'), [
            'import_token' => $token,
            'skip_duplicates' => 1,
        ]);

        $importResponse->assertRedirect(route('crm.leads.index'));
        $importResponse->assertSessionHas('success');

        // Verify lead is created with all contact and address fields
        $lead = Lead::where('mobile', '9766554433')->first();
        $this->assertNotNull($lead);
        $this->assertEquals('Rohan Deshmukh', $lead->name);
        $this->assertEquals('Deshmukh Solar', $lead->company_name);
        $this->assertEquals('rohan@deshmukhsolar.in', $lead->email);
        $this->assertEquals('sec@deshmukhsolar.in', $lead->secondary_email);
        $this->assertEquals('9766554422', $lead->secondary_mobile);
        $this->assertEquals('020-24441122', $lead->phone);
        $this->assertEquals('020-24441133', $lead->landline);
        $this->assertEquals('Owner', $lead->title);
        $this->assertEquals($salesperson->id, $lead->assigned_to);
        $this->assertEquals('Key Account', $lead->tag);
        $this->assertEquals('Pune', $lead->city);
        $this->assertEquals('411001', $lead->zip);
        $this->assertEquals('Maharashtra', $lead->state);
        $this->assertEquals('FC Road 12', $lead->street);
    }

    public function test_cancel_or_missing_import_token_does_not_create_records(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post(route('crm.leads.bulk-upload.import'), [
            'import_token' => 'invalid-non-existent-token',
        ]);

        $response->assertRedirect(route('crm.leads.bulk-upload'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_preview_pagination_with_45_records_displays_20_per_page_and_allows_full_import(): void
    {
        $user = $this->makeUser();

        // Build CSV with 45 records: 40 valid, 5 invalid (blank Contact Name)
        $headers = [
            'Contact Name', 'Company Name', 'Email', 'Secondary Email', 'Mobile', 'SECND MOB',
            'Phone', 'LANDLINE', 'Title', 'SALES PERSON', 'Tag', 'Mailing City', 'Mailing Zip',
            'Mailing State', 'Mailing Street', 'SUB CATEGORY'
        ];
        $csvContent = implode(',', $headers) . "\n";

        for ($i = 1; $i <= 45; $i++) {
            $isValid = $i <= 40;
            $name = $isValid ? "Lead Person {$i}" : '';
            $company = "Company {$i}";
            $email = "lead{$i}@example.com";
            $mobile = sprintf('9800%06d', $i);
            $csvContent .= implode(',', [
                $name, $company, $email, '', $mobile, '',
                '', '', 'Manager', '', 'Tier 1', 'Mumbai', '400001',
                'Maharashtra', "Street {$i}", ''
            ]) . "\n";
        }

        $file = UploadedFile::fake()->createWithContent('leads_45.csv', $csvContent);

        // Step 1: POST to preview
        $previewResponse = $this->actingAs($user)->post(route('crm.leads.bulk-upload.preview'), [
            'file' => $file,
        ]);

        $previewResponse->assertOk();

        // 1. Validation summary KPI reflects entire file (45 total, 40 valid, 5 invalid)
        $content = $previewResponse->getContent();
        $this->assertStringContainsString('Total Rows', $content);
        $this->assertStringContainsString('45', $content);
        $this->assertStringContainsString('Valid Rows', $content);
        $this->assertStringContainsString('40', $content);
        $this->assertStringContainsString('Invalid Rows', $content);
        $this->assertStringContainsString('5', $content);

        // 2. Pagination controls and record summary are present
        $previewResponse->assertSee('Showing 1–20 of 45 records');
        $previewResponse->assertSee('Previous');
        $previewResponse->assertSee('Next');
        $previewResponse->assertSee('searchQuery');
        $previewResponse->assertSee('Search preview records...');

        // 3. Confirm button displays ALL 40 valid records ready to import
        $previewResponse->assertSee('Confirm &amp; Import 40 Lead(s)', false);

        // Extract token
        preg_match('/name="import_token"\s+value="([^"]+)"/', $content, $matches);
        $this->assertNotEmpty($matches[1], 'Import token not found');
        $token = $matches[1];

        // 4. Import action processes ALL 40 valid records across all 3 pages, not just the first 20
        $importResponse = $this->actingAs($user)->post(route('crm.leads.bulk-upload.import'), [
            'import_token' => $token,
            'skip_duplicates' => 1,
        ]);

        $importResponse->assertRedirect(route('crm.leads.index'));
        $importResponse->assertSessionHas('success');

        // All 40 valid records must exist in the database
        $this->assertEquals(40, Lead::where('company_id', $user->company_id)->count());
        $this->assertTrue(Lead::where('mobile', sprintf('9800%06d', 1))->exists());
        $this->assertTrue(Lead::where('mobile', sprintf('9800%06d', 20))->exists());
        $this->assertTrue(Lead::where('mobile', sprintf('9800%06d', 21))->exists());
        $this->assertTrue(Lead::where('mobile', sprintf('9800%06d', 40))->exists());
        // Invalid records (41-45) must not exist
        $this->assertFalse(Lead::where('mobile', sprintf('9800%06d', 41))->exists());
    }
}

