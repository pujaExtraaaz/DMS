<?php

namespace Tests\Feature\Organization;

use App\Domains\Banking\Models\OdAccount;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyProfileOdLimitTest extends TestCase
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
            'code' => 'ACM001',
            'bank_name' => 'HDFC Bank',
            'bank_account_no' => '50200012345678',
            'bank_ifsc' => 'HDFC0001234',
            'upi_id' => 'acme@hdfcbank',
            'od_limit' => 500000.00,
            'interest_rate' => 12.00,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);
    }

    public function test_company_profile_displays_od_limit_and_interest_section(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('organization.company-profile'));

        $response->assertOk();
        $response->assertSee('Banking &amp; Payments', false);
        $response->assertSee('OD Limit &amp; Interest', false);
        $response->assertSee('OD Limit (₹)', false);
        $response->assertSee('Interest Rate (% per annum)', false);
        $response->assertSee('Interest Calculation', false);
        $response->assertSee('Estimated Annual Interest', false);
        $response->assertSee('Estimated Monthly Interest', false);
        $response->assertSee('Invoice Terms &amp; Conditions', false);

        // Confirm "Select Bank Account" dropdown is REMOVED
        $response->assertDontSee('Select Bank Account');
        $response->assertDontSee('Select which bank account');

        // Verify ordering within the form: Banking & Payments -> OD Limit & Interest -> Invoice Terms & Conditions
        $content = $response->getContent();
        $posBanking = strpos($content, '<h3 class="text-sm font-semibold text-slate-700">Banking &amp; Payments</h3>');
        $posOd = strpos($content, '<h3 class="text-sm font-semibold text-slate-700">OD Limit &amp; Interest</h3>');
        $posTerms = strpos($content, 'Invoice Terms &amp; Conditions');

        $this->assertNotFalse($posBanking);
        $this->assertNotFalse($posOd);
        $this->assertNotFalse($posTerms);
        $this->assertTrue($posBanking < $posOd, 'Banking & Payments must be above OD Limit & Interest');
        $this->assertTrue($posOd < $posTerms, 'OD Limit & Interest must be above Invoice Terms & Conditions');
    }

    public function test_no_raw_javascript_code_is_displayed_as_visible_content_in_html(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('organization.company-profile'));

        $response->assertOk();
        $content = $response->getContent();

        // Ensure JavaScript function and x-data binding are properly defined
        $this->assertStringContainsString('function companyBankAndOdProfile', $content);
        $this->assertStringContainsString('x-data="companyBankAndOdProfile(', $content);

        // Strip out all <script>...</script> tags and check visible page content
        $contentWithoutScripts = preg_replace('/<script[\s\S]*?<\/script>/i', '', $content);

        $this->assertStringNotContainsString('this.bankAccounts.length', $contentWithoutScripts);
        $this->assertStringNotContainsString('this.selectedBankIndex', $contentWithoutScripts);
        $this->assertStringNotContainsString('get currentOdLimit()', $contentWithoutScripts);
        $this->assertStringNotContainsString('get annualInterest()', $contentWithoutScripts);
        $this->assertStringNotContainsString('formatInr(val)', $contentWithoutScripts);
    }

    public function test_can_save_and_update_od_limit_and_interest_rate(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation Updated',
                'bank_accounts' => [
                    [
                        'bank_name' => 'HDFC Bank',
                        'bank_account_no' => '50200012345678',
                        'bank_ifsc' => 'HDFC0001234',
                        'upi_id' => 'acme@hdfcbank',
                        'od_limit' => 750000.00,
                        'interest_rate' => 10.50,
                    ],
                ],
            ]);

        $response->assertRedirect(route('organization.company-profile'));
        $response->assertSessionHas('status', 'Company profile updated successfully.');

        $this->company->refresh();
        $this->assertEquals(750000.00, (float) $this->company->od_limit);
        $this->assertEquals(10.50, (float) $this->company->interest_rate);

        // Check synchronization with OdAccount model
        $odAccount = OdAccount::where('company_id', $this->company->id)
            ->where('account_number', '50200012345678')
            ->first();

        $this->assertNotNull($odAccount);
        $this->assertEquals(750000.00, (float) $odAccount->od_limit);
        $this->assertEquals(10.50, (float) $odAccount->interest_rate);
    }

    public function test_multiple_bank_accounts_retain_independent_od_configurations(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'bank_accounts' => [
                    [
                        'bank_name' => 'HDFC Bank',
                        'bank_account_no' => '50200012345678',
                        'bank_ifsc' => 'HDFC0001234',
                        'upi_id' => 'acme@hdfcbank',
                        'od_limit' => 1000000.00,
                        'interest_rate' => 11.25,
                    ],
                    [
                        'bank_name' => 'ICICI Bank',
                        'bank_account_no' => '001122334455',
                        'bank_ifsc' => 'ICIC0000011',
                        'upi_id' => 'acme@icici',
                        'od_limit' => 500000.00,
                        'interest_rate' => 9.75,
                    ],
                    [
                        'bank_name' => 'SBI Bank',
                        'bank_account_no' => '998877665544',
                        'bank_ifsc' => 'SBIN0001234',
                        'upi_id' => 'acme@sbi',
                        'od_limit' => 0.00,
                        'interest_rate' => 0.00,
                    ],
                ],
            ]);

        $response->assertRedirect(route('organization.company-profile'));

        $this->company->refresh();
        $accounts = $this->company->bank_accounts;
        $this->assertCount(3, $accounts);

        // HDFC
        $this->assertEquals('50200012345678', $accounts[0]['bank_account_no']);
        $this->assertEquals(1000000.00, (float) $accounts[0]['od_limit']);
        $this->assertEquals(11.25, (float) $accounts[0]['interest_rate']);

        // ICICI
        $this->assertEquals('001122334455', $accounts[1]['bank_account_no']);
        $this->assertEquals(500000.00, (float) $accounts[1]['od_limit']);
        $this->assertEquals(9.75, (float) $accounts[1]['interest_rate']);

        // SBI
        $this->assertEquals('998877665544', $accounts[2]['bank_account_no']);
        $this->assertEquals(0.00, (float) $accounts[2]['od_limit']);
        $this->assertEquals(0.00, (float) $accounts[2]['interest_rate']);

        // Check OdAccounts in DB
        $hdfcOd = OdAccount::where('company_id', $this->company->id)->where('account_number', '50200012345678')->first();
        $iciciOd = OdAccount::where('company_id', $this->company->id)->where('account_number', '001122334455')->first();

        $this->assertNotNull($hdfcOd);
        $this->assertEquals(1000000.00, (float) $hdfcOd->od_limit);
        $this->assertEquals(11.25, (float) $hdfcOd->interest_rate);

        $this->assertNotNull($iciciOd);
        $this->assertEquals(500000.00, (float) $iciciOd->od_limit);
        $this->assertEquals(9.75, (float) $iciciOd->interest_rate);

        // Render page and check saved values reload
        $renderResponse = $this->actingAs($this->user)->get(route('organization.company-profile'));
        $renderResponse->assertOk();
        $renderResponse->assertSee('50200012345678');
        $renderResponse->assertSee('001122334455');
    }

    public function test_rejects_negative_od_limit(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'bank_accounts' => [
                    [
                        'bank_name' => 'HDFC Bank',
                        'bank_account_no' => '50200012345678',
                        'od_limit' => -5000,
                        'interest_rate' => 12,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('bank_accounts.0.od_limit');
    }

    public function test_rejects_interest_rate_outside_zero_to_hundred(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'bank_accounts' => [
                    [
                        'bank_name' => 'HDFC Bank',
                        'bank_account_no' => '50200012345678',
                        'od_limit' => 500000,
                        'interest_rate' => 150, // Exceeds 100
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('bank_accounts.0.interest_rate');

        $responseNegative = $this->actingAs($this->user)
            ->put(route('organization.company-profile.update'), [
                'name' => 'Acme Corporation',
                'bank_accounts' => [
                    [
                        'bank_name' => 'HDFC Bank',
                        'bank_account_no' => '50200012345678',
                        'od_limit' => 500000,
                        'interest_rate' => -5, // Less than 0
                    ],
                ],
            ]);

        $responseNegative->assertSessionHasErrors('bank_accounts.0.interest_rate');
    }

    public function test_displays_helpful_message_when_no_bank_accounts_configured(): void
    {
        $companyWithoutBank = Company::create([
            'name' => 'Empty Bank Co',
            'code' => 'EBC001',
            'is_active' => true,
        ]);
        $userWithoutBank = User::factory()->create([
            'company_id' => $companyWithoutBank->id,
        ]);
        $userWithoutBank->assignRole('super-admin');

        $response = $this->actingAs($userWithoutBank)
            ->get(route('organization.company-profile'));

        $response->assertOk();
        $response->assertSee('No Bank Account Configured');
        $response->assertSee('Please add at least one bank account');
    }
}
