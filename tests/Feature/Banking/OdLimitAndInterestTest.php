<?php

namespace Tests\Feature\Banking;

use App\Domains\Banking\Models\BankAccountTransaction;
use App\Domains\Banking\Models\OdAccount;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OdLimitAndInterestTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::first() ?? User::factory()->create();
        if (! $this->user->hasRole('super-admin')) {
            $this->user->assignRole($role);
        }

        // Setup Company A with bank accounts in Company Profile
        $this->companyA = Company::create([
            'name' => 'Apex Global Traders',
            'code' => 'AGT-01',
            'bank_name' => 'HDFC Bank',
            'bank_account_no' => '1234567890',
            'bank_ifsc' => 'HDFC0001234',
            'upi_id' => 'apex@okhdfcbank',
            'additional_details' => [
                'bank_accounts' => [
                    [
                        'bank_name' => 'ICICI Bank',
                        'bank_account_no' => '9876543210',
                        'bank_ifsc' => 'ICIC0005678',
                        'upi_id' => 'apex@okicici',
                    ],
                ],
            ],
            'is_active' => true,
        ]);

        $this->companyB = Company::create([
            'name' => 'Beta Logistics Ltd',
            'code' => 'BLL-02',
            'bank_name' => 'SBI Bank',
            'bank_account_no' => '5555666677',
            'bank_ifsc' => 'SBIN0001111',
            'is_active' => true,
        ]);

        $this->user->update(['company_id' => $this->companyA->id]);

        $this->branch = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Corporate Branch',
            'code' => 'CB-01',
        ]);
    }

    public function test_od_configuration_screen_loads_with_company_bank_accounts(): void
    {
        $response = $this->actingAs($this->user)->get(route('od.index'));
        $response->assertStatus(200);
        $response->assertSee('OD Limit &amp; Interest Calculation', false);
        $response->assertSee('HDFC Bank - 1234567890');
        $response->assertSee('ICICI Bank - 9876543210');
        $response->assertSee('CURRENT OD POSITION');
    }

    public function test_can_save_and_update_od_configuration(): void
    {
        $payload = [
            'account_number' => '1234567890',
            'bank_name' => 'HDFC Bank',
            'ifsc_code' => 'HDFC0001234',
            'od_limit' => 1000000,
            'interest_rate' => 12.00,
            'interest_calculation_method' => 'daily_simple',
            'effective_from' => '2026-10-01',
            'effective_to' => null,
            'status' => 'active',
            'notes' => 'Primary OD facility approved by credit board',
        ];

        $response = $this->actingAs($this->user)->post(route('od.store'), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('od.index', ['account_number' => '1234567890']));

        $this->assertDatabaseHas('od_accounts', [
            'company_id' => $this->companyA->id,
            'account_number' => '1234567890',
            'bank_name' => 'HDFC Bank',
            'od_limit' => 1000000,
            'interest_rate' => 12.00,
            'status' => 'active',
        ]);
    }

    public function test_od_configuration_validations(): void
    {
        // Negative limit and interest rate
        $payload = [
            'account_number' => '1234567890',
            'bank_name' => 'HDFC Bank',
            'od_limit' => -500,
            'interest_rate' => -2,
            'interest_calculation_method' => 'daily_simple',
            'effective_from' => '2026-10-01',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)->post(route('od.store'), $payload);
        $response->assertSessionHasErrors(['od_limit', 'interest_rate']);

        // Effective To before Effective From
        $payload['od_limit'] = 100000;
        $payload['interest_rate'] = 10;
        $payload['effective_to'] = '2026-09-01'; // Before 2026-10-01

        $response2 = $this->actingAs($this->user)->post(route('od.store'), $payload);
        $response2->assertSessionHasErrors(['effective_to']);
    }

    public function test_exact_client_example_utilization_and_daily_interest_calculation(): void
    {
        /**
         * Client requirement specification (Section 22):
         * Bank Account: HDFC - 1234567890
         * OD Limit: ₹10,00,000
         * Interest: 12% annually
         *
         * Transactions:
         * 01/10/2026: OD utilization = ₹5,00,000
         * 02/10/2026: Additional utilization = ₹1,00,000
         * 03/10/2026: Payment/deposit = ₹2,00,000
         *
         * Expected:
         * 01/10 -> ₹5,00,000 utilized (Daily Int: 500,000 * 12 / 365 / 100 = 164.38)
         * 02/10 -> ₹6,00,000 utilized (Daily Int: 600,000 * 12 / 365 / 100 = 197.26)
         * 03/10 -> ₹4,00,000 utilized (Daily Int: 400,000 * 12 / 365 / 100 = 131.51)
         */
        $odAccount = OdAccount::create([
            'company_id' => $this->companyA->id,
            'account_number' => '1234567890',
            'bank_name' => 'HDFC Bank',
            'od_limit' => 1000000.00,
            'interest_rate' => 12.00,
            'interest_calculation_method' => 'daily_simple',
            'effective_from' => '2026-10-01',
            'status' => 'active',
        ]);

        // 1. Transaction 1: 01/10/2026 -> OD utilization ₹5,00,000 (Withdrawal / Debit)
        $this->actingAs($this->user)->post(route('od.transactions.store'), [
            'account_number' => '1234567890',
            'transaction_date' => '2026-10-01',
            'transaction_no' => 'TXN-001',
            'transaction_type' => 'withdrawal',
            'amount' => 500000,
            'description' => 'Working capital draw',
        ]);

        // 2. Transaction 2: 02/10/2026 -> Additional ₹1,00,000 (Debit)
        $this->actingAs($this->user)->post(route('od.transactions.store'), [
            'account_number' => '1234567890',
            'transaction_date' => '2026-10-02',
            'transaction_no' => 'TXN-002',
            'transaction_type' => 'payment',
            'amount' => 100000,
            'description' => 'Supplier RTGS payout',
        ]);

        // 3. Transaction 3: 03/10/2026 -> Payment/deposit ₹2,00,000 (Credit)
        $this->actingAs($this->user)->post(route('od.transactions.store'), [
            'account_number' => '1234567890',
            'transaction_date' => '2026-10-03',
            'transaction_no' => 'TXN-003',
            'transaction_type' => 'deposit',
            'amount' => 200000,
            'description' => 'Customer collection credit',
        ]);

        // Verify model helper calculations
        $odAccount->refresh();
        $this->assertEquals(400000.00, $odAccount->getCurrentUtilization());
        $this->assertEquals(600000.00, $odAccount->getAvailableLimit());
        $this->assertEquals(40.0, $odAccount->getUtilizationPercentage());
        $this->assertFalse($odAccount->isExceeded());

        // Verify report calculation over the period 2026-10-01 to 2026-10-03
        $calculationService = app(\App\Domains\Banking\Services\OdInterestCalculationService::class);
        $result = $calculationService->calculate(
            odAccount: $odAccount,
            fromDate: '2026-10-01',
            toDate: '2026-10-03'
        );

        $rows = $result['rows'];
        $this->assertCount(3, $rows);

        // Row 1 (01/10/2026): Utilized = 5,00,000, Days = 1, Daily = 164.38
        $this->assertEquals('2026-10-01', $rows[0]['date']->format('Y-m-d'));
        $this->assertEquals(500000.00, (float) $rows[0]['od_utilized']);
        $this->assertEquals(1, $rows[0]['days']);
        $this->assertEquals(164.3836, round($rows[0]['daily_interest'], 4));
        $this->assertEquals(164.38, $rows[0]['row_interest']);

        // Row 2 (02/10/2026): Utilized = 6,00,000, Days = 1, Daily = 197.26
        $this->assertEquals('2026-10-02', $rows[1]['date']->format('Y-m-d'));
        $this->assertEquals(600000.00, (float) $rows[1]['od_utilized']);
        $this->assertEquals(1, $rows[1]['days']);
        $this->assertEquals(197.2603, round($rows[1]['daily_interest'], 4));
        $this->assertEquals(197.26, $rows[1]['row_interest']);

        // Row 3 (03/10/2026): Utilized = 4,00,000, Days = 1, Daily = 131.51
        $this->assertEquals('2026-10-03', $rows[2]['date']->format('Y-m-d'));
        $this->assertEquals(400000.00, (float) $rows[2]['od_utilized']);
        $this->assertEquals(1, $rows[2]['days']);
        $this->assertEquals(131.5068, round($rows[2]['daily_interest'], 4));
        $this->assertEquals(131.51, $rows[2]['row_interest']);

        // Total period interest = 164.38 + 197.26 + 131.51 = 493.15
        $this->assertEquals(493.15, $result['totalPeriodInterest']);
    }

    public function test_od_limit_exceeded_warning_displayed_when_utilized_exceeds_limit(): void
    {
        $odAccount = OdAccount::create([
            'company_id' => $this->companyA->id,
            'account_number' => '1234567890',
            'bank_name' => 'HDFC Bank',
            'od_limit' => 1000000.00,
            'interest_rate' => 12.00,
            'effective_from' => '2026-10-01',
            'status' => 'active',
        ]);

        // Draw ₹11,50,000 (exceeds ₹10,00,000 limit by ₹1,50,000)
        BankAccountTransaction::create([
            'company_id' => $this->companyA->id,
            'account_number' => '1234567890',
            'od_account_id' => $odAccount->id,
            'transaction_date' => '2026-10-01',
            'transaction_no' => 'TXN-OVERDRAW',
            'description' => 'Heavy raw material procurement',
            'transaction_type' => 'withdrawal',
            'debit' => 1150000.00,
            'credit' => 0.00,
        ]);

        $odAccount->refresh();
        $this->assertTrue($odAccount->isExceeded());
        $this->assertEquals(150000.00, $odAccount->getExceededAmount());
        $this->assertEquals(0.00, $odAccount->getAvailableLimit()); // Not negative!
        $this->assertEquals(115.0, $odAccount->getUtilizationPercentage());

        // Report page check
        $response = $this->actingAs($this->user)->get(route('od.report', ['account_number' => '1234567890']));
        $response->assertStatus(200);
        $response->assertSee('OD LIMIT EXCEEDED');
        $response->assertSee('OD LIMIT EXCEEDED BY ₹' . number_format(150000, 2));
    }

    public function test_multi_company_data_isolation(): void
    {
        // Company A has OD setup
        OdAccount::create([
            'company_id' => $this->companyA->id,
            'account_number' => '1234567890',
            'bank_name' => 'HDFC Bank',
            'od_limit' => 1000000.00,
            'interest_rate' => 12.00,
            'effective_from' => '2026-10-01',
            'status' => 'active',
        ]);

        // Create user belonging to Company B
        $userB = User::factory()->create([
            'company_id' => $this->companyB->id,
        ]);
        $userB->assignRole('super-admin');

        // Company B user visits OD module
        $response = $this->actingAs($userB)->get(route('od.index'));
        $response->assertStatus(200);

        // User B must NOT see Company A's bank account or OD limit
        $response->assertDontSee('1234567890');
        $response->assertDontSee('HDFC Bank');
        $response->assertSee('5555666677'); // Company B's bank
        $response->assertSee('SBI Bank');
    }

    public function test_od_report_page_and_export_functionality(): void
    {
        OdAccount::create([
            'company_id' => $this->companyA->id,
            'account_number' => '1234567890',
            'bank_name' => 'HDFC Bank',
            'od_limit' => 1000000.00,
            'interest_rate' => 12.00,
            'effective_from' => '2026-10-01',
            'status' => 'active',
        ]);

        BankAccountTransaction::create([
            'company_id' => $this->companyA->id,
            'account_number' => '1234567890',
            'transaction_date' => '2026-10-01',
            'transaction_no' => 'TXN-EXPORT-TEST',
            'description' => 'Test transaction for CSV export',
            'transaction_type' => 'withdrawal',
            'debit' => 50000.00,
            'credit' => 0.00,
        ]);

        // Normal HTML view
        $response = $this->actingAs($this->user)->get(route('od.report', ['account_number' => '1234567890']));
        $response->assertStatus(200);
        $response->assertSee('OD Report');
        $response->assertSee('TXN-EXPORT-TEST');

        // Reports alias
        $aliasResponse = $this->actingAs($this->user)->get(route('reports.od', ['account_number' => '1234567890']));
        $aliasResponse->assertStatus(200);

        // CSV export
        $csvResponse = $this->actingAs($this->user)->get(route('od.report', [
            'account_number' => '1234567890',
            'export' => 'csv',
        ]));
        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_bank_details_ajax_lookup(): void
    {
        OdAccount::create([
            'company_id' => $this->companyA->id,
            'account_number' => '1234567890',
            'bank_name' => 'HDFC Bank',
            'od_limit' => 1000000.00,
            'interest_rate' => 12.00,
            'effective_from' => '2026-10-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('od.bank-details', ['account_number' => '1234567890']));
        $response->assertStatus(200);
        $response->assertJson([
            'account_number' => '1234567890',
            'bank_name' => 'HDFC Bank',
            'has_od' => true,
            'od_limit' => 1000000,
            'interest_rate' => 12,
        ]);
    }

    public function test_od_report_shows_account_dropdown_with_account_numbers_and_summary_metrics(): void
    {
        OdAccount::create([
            'company_id' => $this->companyA->id,
            'account_number' => '1234567890',
            'bank_name' => 'HDFC Bank',
            'od_limit' => 1000000.00,
            'interest_rate' => 12.00,
            'effective_from' => '2026-10-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.od', ['account_number' => '1234567890']));
        $response->assertStatus(200);

        // Exactly one dropdown for Bank Account No.
        $response->assertSee('Bank Account No.');
        $response->assertSee('1234567890 (HDFC Bank)');
        $response->assertSee('9876543210 (ICICI Bank)');

        // Extra filter controls must NOT be present
        $response->assertDontSee('From Date');
        $response->assertDontSee('To Date');
        $response->assertDontSee('Search / Apply Filters');
        $response->assertDontSee('Quick Periods:');

        // Summary panel shows configured OD limit, rate, and estimated interest
        $response->assertSee('Configured OD Limit');
        $response->assertSee('1,000,000.00');
        $response->assertSee('12.00% p.a.');
        $response->assertSee('Estimated Annual Interest');
        $response->assertSee('120,000.00');
        $response->assertSee('Estimated Monthly Interest');
        $response->assertSee('10,000.00');
    }

    public function test_od_report_shows_empty_state_when_no_account_selected(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.od'));
        $response->assertStatus(200);

        $response->assertSee('Please Select a Bank Account');
        $response->assertDontSee('Total Transactions:');
    }

    public function test_od_report_shows_no_transactions_message_for_empty_account(): void
    {
        OdAccount::create([
            'company_id' => $this->companyA->id,
            'account_number' => '1234567890',
            'bank_name' => 'HDFC Bank',
            'od_limit' => 1000000.00,
            'interest_rate' => 12.00,
            'effective_from' => '2026-10-01',
            'status' => 'active',
        ]);

        // Account has no transactions
        $response = $this->actingAs($this->user)->get(route('reports.od', ['account_number' => '1234567890']));
        $response->assertStatus(200);

        // Account summary is still displayed
        $response->assertSee('Configured OD Limit');
        $response->assertSee('1,000,000.00');

        // Helpful empty transactions message
        $response->assertSee('No transactions found for this account');
    }

    public function test_od_report_shows_clear_message_when_account_has_no_od_configuration(): void
    {
        // Account 9876543210 has no OD configured
        $response = $this->actingAs($this->user)->get(route('reports.od', ['account_number' => '9876543210']));
        $response->assertStatus(200);

        $response->assertSee('No OD Configuration Found for this Bank Account');
        $response->assertSee('NO OD CONFIGURED');
        $response->assertDontSee('Estimated Annual Interest');
    }

    public function test_od_report_orders_transactions_newest_first_with_required_columns(): void
    {
        $odAccount = OdAccount::create([
            'company_id' => $this->companyA->id,
            'account_number' => '1234567890',
            'bank_name' => 'HDFC Bank',
            'od_limit' => 1000000.00,
            'interest_rate' => 12.00,
            'effective_from' => '2026-10-01',
            'status' => 'active',
        ]);

        BankAccountTransaction::create([
            'company_id' => $this->companyA->id,
            'account_number' => '1234567890',
            'transaction_date' => '2026-10-01',
            'transaction_no' => 'TXN-OCT-01',
            'description' => 'Supplier payment 1',
            'transaction_type' => 'withdrawal',
            'debit' => 500000.00,
            'credit' => 0.00,
        ]);

        BankAccountTransaction::create([
            'company_id' => $this->companyA->id,
            'account_number' => '1234567890',
            'transaction_date' => '2026-10-03',
            'transaction_no' => 'TXN-OCT-03',
            'description' => 'Client deposit',
            'transaction_type' => 'deposit',
            'debit' => 0.00,
            'credit' => 200000.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.od', ['account_number' => '1234567890']));
        $response->assertStatus(200);

        // Required table headers
        $response->assertSee('Sr. No.');
        $response->assertSee('Transaction Date');
        $response->assertSee('Voucher / Reference Number');
        $response->assertSee('Transaction Type');
        $response->assertSee('Particulars / Description');
        $response->assertSee('Debit (₹)');
        $response->assertSee('Credit (₹)');
        $response->assertSee('Running Balance (₹)');
        $response->assertSee('OD Limit (₹)');
        $response->assertSee('Available OD Limit (₹)');

        // Newest transaction (TXN-OCT-03) appears before older transaction (TXN-OCT-01) in HTML
        $content = $response->getContent();
        $posOct03 = strpos($content, 'TXN-OCT-03');
        $posOct01 = strpos($content, 'TXN-OCT-01');
        $this->assertNotFalse($posOct03);
        $this->assertNotFalse($posOct01);
        $this->assertTrue($posOct03 < $posOct01, 'TXN-OCT-03 must appear before TXN-OCT-01 (newest first)');
    }

    public function test_od_report_tenant_isolation_prevents_viewing_other_company_account(): void
    {
        // Company B account 5555666677 belongs to Company B
        // User belongs to Company A
        $response = $this->actingAs($this->user)->get(route('reports.od', [
            'account_number' => '5555666677',
        ]));
        $response->assertStatus(200);

        // Must NOT expose Company B's bank account or name
        $response->assertDontSee('5555666677');
        $response->assertDontSee('SBI Bank');
    }
}
