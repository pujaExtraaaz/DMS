<?php

namespace Tests\Feature\Crm;

use App\Domains\Crm\Models\Lead;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeadDuplicateValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['crm.view', 'crm.create', 'crm.edit', 'crm.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(): User
    {
        $company = Company::firstOrCreate(
            ['code' => 'TESTCO'],
            ['name' => 'Test Company', 'currency' => 'INR', 'is_active' => true]
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $user->givePermissionTo(['crm.view', 'crm.create', 'crm.edit', 'crm.manage']);

        return $user;
    }

    /**
     * Scenario 1: Unique lead creation succeeds.
     */
    public function test_scenario_1_unique_lead_creation_succeeds(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post(route('crm.leads.store'), [
            'name' => 'Unique Contact',
            'email' => 'unique@example.com',
            'mobile' => '9876543210',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'name' => 'Unique Contact',
            'email' => 'unique@example.com',
            'mobile' => '9876543210',
        ]);
    }

    /**
     * Scenario 2: Duplicate email on create is blocked with exact error message.
     */
    public function test_scenario_2_duplicate_email_on_create_is_blocked(): void
    {
        $user = $this->makeUser();

        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Existing Lead',
            'email' => 'duplicate@example.com',
            'mobile' => '9111111111',
            'status' => 'new',
        ]);

        // Attempt creation with same email (even with different casing/whitespace)
        $response = $this->actingAs($user)->post(route('crm.leads.store'), [
            'name' => 'New Lead With Duplicate Email',
            'email' => '  DUPLICATE@example.com ',
            'mobile' => '9222222222',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'This email address is already registered with another lead.',
        ]);
        $this->assertDatabaseMissing('leads', ['name' => 'New Lead With Duplicate Email']);
    }

    /**
     * Scenario 3: Duplicate mobile on create is blocked with exact error message.
     */
    public function test_scenario_3_duplicate_mobile_on_create_is_blocked(): void
    {
        $user = $this->makeUser();

        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Existing Mobile Lead',
            'email' => 'lead1@example.com',
            'mobile' => '9876543210',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->post(route('crm.leads.store'), [
            'name' => 'New Lead With Duplicate Mobile',
            'email' => 'lead2@example.com',
            'mobile' => '9876543210',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response->assertSessionHasErrors([
            'mobile' => 'This mobile/contact number is already registered with another lead.',
        ]);
        $this->assertDatabaseMissing('leads', ['name' => 'New Lead With Duplicate Mobile']);
    }

    /**
     * Scenario 4: Both email and mobile duplicate on create displays both errors simultaneously.
     */
    public function test_scenario_4_both_duplicate_on_create_displays_both_errors(): void
    {
        $user = $this->makeUser();

        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'First Lead',
            'email' => 'first@example.com',
            'mobile' => '9876543210',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->post(route('crm.leads.store'), [
            'name' => 'Cloned Lead',
            'email' => 'first@example.com',
            'mobile' => '9876543210',
            'priority' => 'normal',
            'status' => 'new',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'This email address is already registered with another lead.',
            'mobile' => 'This mobile/contact number is already registered with another lead.',
        ]);
        $this->assertDatabaseMissing('leads', ['name' => 'Cloned Lead']);
    }

    /**
     * Scenario 5: Bulk upload with duplicates within the file marks second row as Invalid.
     */
    public function test_scenario_5_bulk_upload_file_duplicates_marked_as_invalid(): void
    {
        $user = $this->makeUser();

        $headers = [
            'Contact Name', 'Company Name', 'Email', 'Secondary Email', 'Mobile', 'SECND MOB',
            'Phone', 'LANDLINE', 'Title', 'SALES PERSON', 'Tag', 'Mailing City', 'Mailing Zip',
            'Mailing State', 'Mailing Street', 'SUB CATEGORY',
        ];
        $csvContent = implode(',', $headers) . "\n";

        // Row 2: First lead
        $csvContent .= implode(',', [
            'First Contact', 'Company A', 'shared@example.com', '', '9876500001', '',
            '', '', '', '', '', '', '', '', '', '',
        ]) . "\n";

        // Row 3: Duplicate email in file
        $csvContent .= implode(',', [
            'Second Contact', 'Company B', 'shared@example.com', '', '9876500002', '',
            '', '', '', '', '', '', '', '', '', '',
        ]) . "\n";

        // Row 4: Duplicate mobile in file (duplicate of row 2 mobile)
        $csvContent .= implode(',', [
            'Third Contact', 'Company C', 'third@example.com', '', '9876500001', '',
            '', '', '', '', '', '', '', '', '', '',
        ]) . "\n";

        $file = UploadedFile::fake()->createWithContent('file_duplicates.csv', $csvContent);

        $previewResponse = $this->actingAs($user)->post(route('crm.leads.bulk-upload.preview'), [
            'file' => $file,
        ]);

        $previewResponse->assertOk();
        $content = $previewResponse->getContent();

        // 3 Total rows: 1 valid, 2 invalid
        $this->assertStringContainsString('Duplicate email address found in the uploaded file (matches Row 2)', $content);
        $this->assertStringContainsString('Duplicate mobile/contact number found in the uploaded file (matches Row 2)', $content);
        $this->assertStringContainsString('Confirm &amp; Import 1 Lead(s)', $content);
    }

    /**
     * Scenario 6: Bulk upload with database duplicates marks rows as Invalid.
     */
    public function test_scenario_6_bulk_upload_database_duplicates_marked_as_invalid(): void
    {
        $user = $this->makeUser();

        $existingLead = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Existing Database Lead',
            'email' => 'dbexisting@example.com',
            'mobile' => '9876511111',
            'status' => 'new',
        ]);

        $headers = [
            'Contact Name', 'Company Name', 'Email', 'Secondary Email', 'Mobile', 'SECND MOB',
            'Phone', 'LANDLINE', 'Title', 'SALES PERSON', 'Tag', 'Mailing City', 'Mailing Zip',
            'Mailing State', 'Mailing Street', 'SUB CATEGORY',
        ];
        $csvContent = implode(',', $headers) . "\n";

        // Row 2: Valid new lead
        $csvContent .= implode(',', [
            'New Valid Person', 'Acme', 'validnew@example.com', '', '9876522222', '',
            '', '', '', '', '', '', '', '', '', '',
        ]) . "\n";

        // Row 3: Matches DB email and mobile
        $csvContent .= implode(',', [
            'Duplicate Person', 'Beta Corp', 'dbexisting@example.com', '', '9876511111', '',
            '', '', '', '', '', '', '', '', '', '',
        ]) . "\n";

        $file = UploadedFile::fake()->createWithContent('db_duplicates.csv', $csvContent);

        $previewResponse = $this->actingAs($user)->post(route('crm.leads.bulk-upload.preview'), [
            'file' => $file,
        ]);

        $previewResponse->assertOk();
        $content = $previewResponse->getContent();

        $this->assertStringContainsString('Duplicate email address already exists in the database', $content);
        $this->assertStringContainsString('Duplicate mobile/contact number already exists in the database', $content);
        $this->assertStringContainsString("Lead #{$existingLead->id}", $content);
        $this->assertStringContainsString('Confirm &amp; Import 1 Lead(s)', $content);
    }

    /**
     * Scenario 7: Number formatting variations (+91, spaces, hyphens, leading 0) detected as duplicates.
     */
    public function test_scenario_7_contact_number_formatting_variations_detected(): void
    {
        $user = $this->makeUser();

        // Lead stored with +91 country prefix and spaces
        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Country Code Lead',
            'email' => 'cc@example.com',
            'mobile' => '+91 98765 43210',
            'status' => 'new',
        ]);

        // Attempt manual creation with 10 digits
        $response1 = $this->actingAs($user)->post(route('crm.leads.store'), [
            'name' => 'Variation 1',
            'email' => 'var1@example.com',
            'mobile' => '9876543210',
            'priority' => 'normal',
            'status' => 'new',
        ]);
        $response1->assertSessionHasErrors(['mobile']);

        // Attempt manual creation with leading zero
        $response2 = $this->actingAs($user)->post(route('crm.leads.store'), [
            'name' => 'Variation 2',
            'email' => 'var2@example.com',
            'mobile' => '09876543210',
            'priority' => 'normal',
            'status' => 'new',
        ]);
        $response2->assertSessionHasErrors(['mobile']);

        // Attempt manual creation with hyphens
        $response3 = $this->actingAs($user)->post(route('crm.leads.store'), [
            'name' => 'Variation 3',
            'email' => 'var3@example.com',
            'mobile' => '98765-43210',
            'priority' => 'normal',
            'status' => 'new',
        ]);
        $response3->assertSessionHasErrors(['mobile']);
    }

    /**
     * Scenario 8: Edit existing lead allows keeping its own email/mobile, but blocks taking another lead's details.
     */
    public function test_scenario_8_edit_existing_lead_duplicate_validation(): void
    {
        $user = $this->makeUser();

        $leadA = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Lead Alpha',
            'email' => 'alpha@example.com',
            'mobile' => '9876500001',
            'status' => 'new',
        ]);

        $leadB = Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Lead Beta',
            'email' => 'beta@example.com',
            'mobile' => '9876500002',
            'status' => 'new',
        ]);

        // 1. Edit Lead Alpha without changing contact details -> succeeds
        $editSuccess = $this->actingAs($user)->put(route('crm.leads.update', $leadA), [
            'name' => 'Lead Alpha Renamed',
            'email' => 'alpha@example.com',
            'mobile' => '9876500001',
            'priority' => 'high',
            'status' => 'contacted',
        ]);
        $editSuccess->assertRedirect(route('crm.leads.show', $leadA));
        $this->assertEquals('Lead Alpha Renamed', $leadA->fresh()->name);

        // 2. Change Lead Alpha's email to Lead Beta's email -> blocked
        $editFailEmail = $this->actingAs($user)->put(route('crm.leads.update', $leadA), [
            'name' => 'Lead Alpha Renamed',
            'email' => 'beta@example.com',
            'mobile' => '9876500001',
            'priority' => 'high',
            'status' => 'contacted',
        ]);
        $editFailEmail->assertSessionHasErrors(['email']);

        // 3. Change Lead Alpha's mobile to Lead Beta's mobile -> blocked
        $editFailMobile = $this->actingAs($user)->put(route('crm.leads.update', $leadA), [
            'name' => 'Lead Alpha Renamed',
            'email' => 'alpha@example.com',
            'mobile' => '9876500002',
            'priority' => 'high',
            'status' => 'contacted',
        ]);
        $editFailMobile->assertSessionHasErrors(['mobile']);
    }

    /**
     * Scenario 9: Bulk upload pagination with duplicates on page 1 and page 2.
     */
    public function test_scenario_9_bulk_upload_pagination_with_duplicates(): void
    {
        $user = $this->makeUser();

        // Pre-create 1 lead in DB that conflicts with Row 25 (page 2)
        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Existing Database Boss',
            'email' => 'existingboss@example.com',
            'mobile' => '9800000025',
            'status' => 'new',
        ]);

        // Build 25 rows:
        // Row 2: Valid
        // Row 3: Duplicate of Row 2 (page 1 file duplicate)
        // Rows 4-24: Valid
        // Row 25: Conflicts with DB lead (page 2 db duplicate)
        // Row 26: Valid
        $headers = [
            'Contact Name', 'Company Name', 'Email', 'Secondary Email', 'Mobile', 'SECND MOB',
            'Phone', 'LANDLINE', 'Title', 'SALES PERSON', 'Tag', 'Mailing City', 'Mailing Zip',
            'Mailing State', 'Mailing Street', 'SUB CATEGORY',
        ];
        $csvContent = implode(',', $headers) . "\n";

        // Row 2:
        $csvContent .= "Lead 1,Company,lead1@example.com,,9800000001,,,,,,,,,,\n";
        // Row 3: duplicate of row 2
        $csvContent .= "Lead 2 Duplicate,Company,lead1@example.com,,9800000001,,,,,,,,,,\n";

        for ($i = 3; $i <= 23; $i++) {
            $csvContent .= "Lead {$i},Company,lead{$i}@example.com,,98000000{$i},,,,,,,,,,\n";
        }

        // Row 25: conflicts with DB
        $csvContent .= "Lead 24 DB Conflict,Company,existingboss@example.com,,9800000025,,,,,,,,,,\n";
        // Row 26:
        $csvContent .= "Lead 25,Company,lead25@example.com,,9800000026,,,,,,,,,,\n";

        $file = UploadedFile::fake()->createWithContent('leads_pagination_dups.csv', $csvContent);

        $previewResponse = $this->actingAs($user)->post(route('crm.leads.bulk-upload.preview'), [
            'file' => $file,
        ]);

        $previewResponse->assertOk();
        $content = $previewResponse->getContent();

        // 25 rows total: 23 valid, 2 invalid (1 file duplicate, 1 DB duplicate)
        $this->assertStringContainsString('Total Rows', $content);
        $this->assertStringContainsString('25', $content);
        $this->assertStringContainsString('Confirm &amp; Import 23 Lead(s)', $content);

        // Extract token and import
        preg_match('/name="import_token"\s+value="([^"]+)"/', $content, $matches);
        $token = $matches[1];

        $importResponse = $this->actingAs($user)->post(route('crm.leads.bulk-upload.import'), [
            'import_token' => $token,
        ]);

        $importResponse->assertRedirect(route('crm.leads.index'));

        // DB had 1 initially, imported 23 valid leads -> total 24
        $this->assertEquals(24, Lead::where('company_id', $user->company_id)->count());
    }

    /**
     * Scenario 10: Import revalidation prevents race condition duplicate insertions.
     */
    public function test_scenario_10_import_revalidation_prevents_race_conditions(): void
    {
        $user = $this->makeUser();

        $headers = [
            'Contact Name', 'Company Name', 'Email', 'Secondary Email', 'Mobile', 'SECND MOB',
            'Phone', 'LANDLINE', 'Title', 'SALES PERSON', 'Tag', 'Mailing City', 'Mailing Zip',
            'Mailing State', 'Mailing Street', 'SUB CATEGORY',
        ];
        $csvContent = implode(',', $headers) . "\n";
        $csvContent .= "Race Lead,Company,race@example.com,,9876599999,,,,,,,,,,\n";

        $file = UploadedFile::fake()->createWithContent('race.csv', $csvContent);

        // Preview when DB does not have the lead yet
        $previewResponse = $this->actingAs($user)->post(route('crm.leads.bulk-upload.preview'), [
            'file' => $file,
        ]);
        preg_match('/name="import_token"\s+value="([^"]+)"/', $previewResponse->getContent(), $matches);
        $token = $matches[1];

        // Now simulate another user inserting the lead BEFORE import is clicked!
        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Concurrent Lead',
            'email' => 'race@example.com',
            'mobile' => '9876599999',
            'status' => 'new',
        ]);

        // Attempt import
        $importResponse = $this->actingAs($user)->post(route('crm.leads.bulk-upload.import'), [
            'import_token' => $token,
        ]);

        $importResponse->assertRedirect(route('crm.leads.index'));

        // The concurrent lead remains, and no duplicate was created (count remains 1)
        $this->assertEquals(1, Lead::where('company_id', $user->company_id)->where('email', 'race@example.com')->count());
    }

    /**
     * Scenario 11: Real-time duplicate check AJAX endpoint.
     */
    public function test_scenario_11_realtime_check_duplicate_endpoint(): void
    {
        $user = $this->makeUser();

        Lead::create([
            'company_id' => $user->company_id,
            'name' => 'Ajax Lead',
            'email' => 'ajax@example.com',
            'mobile' => '9876588888',
            'status' => 'new',
        ]);

        // Check unique values -> duplicate false
        $respUnique = $this->actingAs($user)->postJson(route('crm.leads.check-duplicate'), [
            'email' => 'someoneelse@example.com',
            'mobile' => '9876500000',
        ]);
        $respUnique->assertOk();
        $respUnique->assertJson(['duplicate' => false]);

        // Check duplicate email -> duplicate true
        $respEmail = $this->actingAs($user)->postJson(route('crm.leads.check-duplicate'), [
            'email' => 'AJAX@example.com',
        ]);
        $respEmail->assertOk();
        $respEmail->assertJson([
            'duplicate' => true,
            'errors' => [
                'email' => 'This email address is already registered with another lead.',
            ],
        ]);

        // Check duplicate mobile -> duplicate true
        $respMobile = $this->actingAs($user)->postJson(route('crm.leads.check-duplicate'), [
            'mobile' => '+91 98765 88888',
        ]);
        $respMobile->assertOk();
        $respMobile->assertJson([
            'duplicate' => true,
            'errors' => [
                'mobile' => 'This mobile/contact number is already registered with another lead.',
            ],
        ]);
    }
}
