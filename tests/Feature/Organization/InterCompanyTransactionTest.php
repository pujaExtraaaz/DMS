<?php

namespace Tests\Feature\Organization;

use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\InterCompanyTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InterCompanyTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected BusinessGroup $group;
    protected Company $companyA;
    protected Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.delete', 'guard_name' => 'web']);

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $roleRegular = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->group = BusinessGroup::firstOrCreate(
            ['code' => 'NEXUS-CORP'],
            [
                'name' => 'Nexus Corporation',
                'description' => 'Parent conglomerate',
                'is_active' => true,
            ]
        );

        $this->companyA = Company::create([
            'business_group_id' => $this->group->id,
            'name' => 'Nexus Electronics Pvt Ltd',
            'code' => 'NEX-ELEC',
            'company_type' => 'Private Limited',
            'gstin' => '27AAACN0123M1Z5',
            'pan' => 'AAACN0123M',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->companyB = Company::create([
            'business_group_id' => $this->group->id,
            'name' => 'Nexus Logistics Pvt Ltd',
            'code' => 'NEX-LOG',
            'company_type' => 'Private Limited',
            'gstin' => '27AAACN9876M1Z1',
            'pan' => 'AAACN9876M',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'Corporate Controller',
            'email' => 'controller@nexus.example.com',
        ]);
        $this->adminUser->assignRole($roleAdmin);

        $this->regularUser = User::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'Branch Viewer',
            'email' => 'viewer@nexus.example.com',
        ]);
        $this->regularUser->assignRole($roleRegular);
    }

    public function test_transactions_page_renders_with_summary_cards_and_modal(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.transactions'));

        $response->assertStatus(200);
        $response->assertViewIs('organization.sister-concerns.transactions');
        $response->assertSee('Sister Concern Management');
        $response->assertSee('Total Transactions');
        $response->assertSee('Total Transaction Value');
        $response->assertSee('Pending Review');
        $response->assertSee('Settled Transactions');
        $response->assertSee('New Inter-Company Transaction');
        $response->assertSee('Transaction Details');
        $response->assertSee('Amount Details');
        $response->assertSee('References &amp; Settlement', false);
        $response->assertSee('Additional Information');
    }

    public function test_new_transaction_modal_has_sidebar_safe_layout_and_responsive_dimensions(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.transactions'));

        $response->assertStatus(200);

        // Modal teleported to body and centered in full viewport above sidebar
        $response->assertSee('x-teleport="body"', false);
        $response->assertSee('fixed inset-0 z-[100]', false);
        $response->assertSee('min-h-screen items-center justify-center', false);
        $response->assertDontSee('left-0 lg:left-64', false);

        // Modal dimension and scrolling rules
        $response->assertSee('max-h-[88vh]', false);
        $response->assertSee('sticky top-0 z-10', false);
        $response->assertSee('sticky bottom-0 z-10', false);

        // All 4 sections' form controls are present
        $response->assertSee('x-model="form.transaction_date"', false);
        $response->assertSee('x-model="form.transaction_type"', false);
        $response->assertSee('x-model="form.transaction_no"', false);
        $response->assertSee('x-model="form.from_company_id"', false);
        $response->assertSee('x-model="form.to_company_id"', false);
        $response->assertSee('x-model="form.amount"', false);
        $response->assertSee('x-model="form.currency"', false);
        $response->assertSee('x-model="form.exchange_rate"', false);
        $response->assertSee('x-model="form.is_gst_applicable"', false);
        $response->assertSee('x-model="form.igst_amount"', false);
        $response->assertSee('x-model="form.is_tds_applicable"', false);
        $response->assertSee('x-model="form.tds_section"', false);
        $response->assertSee('x-model="form.tds_rate"', false);
        $response->assertSee('x-model="form.tds_amount"', false);
        $response->assertSee('x-model="form.reference_invoice"', false);
        $response->assertSee('x-model="form.reference_po"', false);
        $response->assertSee('x-model="form.due_date"', false);
        $response->assertSee('x-model="form.status"', false);
        $response->assertSee('x-model="form.approved_by"', false);
        $response->assertSee('x-model="form.narration"', false);
        $response->assertSee('x-model="form.notes"', false);
    }

    public function test_view_and_delete_transaction_modals_use_sidebar_safe_boundary(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.transactions'));

        $response->assertStatus(200);

        // All 3 modals (Add/Edit, View, Delete) teleport to body with full viewport stacking
        $content = $response->getContent();
        $needle = 'x-teleport="body"';
        $occurrences = substr_count($content, $needle);
        $this->assertGreaterThanOrEqual(3, $occurrences, 'Add/Edit, View, and Delete modals should all teleport to body.');
        $response->assertDontSee('left-0 lg:left-64', false);
    }

    public function test_can_create_valid_transaction_between_two_eligible_companies(): void
    {
        $payload = [
            'transaction_date' => '2026-10-19',
            'transaction_type' => 'Sale',
            'from_company_id' => $this->companyA->id,
            'to_company_id' => $this->companyB->id,
            'amount' => 100000.00,
            'currency' => 'INR',
            'exchange_rate' => 1.0000,
            'is_gst_applicable' => true,
            'igst_amount' => 18000.00,
            'is_tds_applicable' => true,
            'tds_section' => '194C',
            'tds_rate' => 2.00,
            'tds_amount' => 2000.00,
            'reference_invoice' => 'INV-ICT-2026-001',
            'reference_po' => 'PO-ICT-2026-001',
            'due_date' => '2026-11-19',
            'status' => 'Pending',
            'narration' => 'Supply of electronic component parts between sister concerns',
            'approved_by' => $this->adminUser->id,
            'notes' => 'Audited inter-company billing at arm length price',
        ];

        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('organization.sister-concerns.transactions.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Net Amount = 100000 (amount) + 18000 (IGST) - 2000 (TDS) = 116000
        $this->assertDatabaseHas('inter_company_transactions', [
            'transaction_type' => 'Sale',
            'from_company_id' => $this->companyA->id,
            'to_company_id' => $this->companyB->id,
            'amount' => 100000.00,
            'currency' => 'INR',
            'is_gst_applicable' => 1,
            'igst_amount' => 18000.00,
            'is_tds_applicable' => 1,
            'tds_section' => '194C',
            'tds_rate' => 2.00,
            'tds_amount' => 2000.00,
            'net_amount' => 116000.00,
            'reference_invoice' => 'INV-ICT-2026-001',
            'reference_po' => 'PO-ICT-2026-001',
            'status' => 'Pending',
        ]);
    }

    public function test_rejects_missing_required_fields_and_invalid_amounts(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('organization.sister-concerns.transactions.store'), [
                // Empty payload missing date, type, companies, amount, status
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'transaction_date',
            'transaction_type',
            'from_company_id',
            'to_company_id',
            'amount',
            'status',
        ]);

        // Reject zero or negative amount
        $responseZeroAmt = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('organization.sister-concerns.transactions.store'), [
                'transaction_date' => '2026-10-19',
                'transaction_type' => 'Sale',
                'from_company_id' => $this->companyA->id,
                'to_company_id' => $this->companyB->id,
                'amount' => 0.00,
                'status' => 'Pending',
            ]);

        $responseZeroAmt->assertStatus(422);
        $responseZeroAmt->assertJsonValidationErrors(['amount']);
    }

    public function test_rejects_selecting_the_same_company_as_both_from_and_to(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('organization.sister-concerns.transactions.store'), [
                'transaction_date' => '2026-10-19',
                'transaction_type' => 'Sale',
                'from_company_id' => $this->companyA->id,
                'to_company_id' => $this->companyA->id, // Same company!
                'amount' => 50000.00,
                'currency' => 'INR',
                'exchange_rate' => 1.0,
                'status' => 'Pending',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['to_company_id']);
    }

    public function test_gst_and_tds_unchecked_clears_taxes_correctly(): void
    {
        $payload = [
            'transaction_date' => '2026-10-19',
            'transaction_type' => 'Loan Given',
            'from_company_id' => $this->companyA->id,
            'to_company_id' => $this->companyB->id,
            'amount' => 500000.00,
            'currency' => 'INR',
            'exchange_rate' => 1.0,
            'is_gst_applicable' => false,
            'is_tds_applicable' => false,
            'status' => 'Approved',
        ];

        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('organization.sister-concerns.transactions.store'), $payload);

        $response->assertStatus(200);

        // When GST and TDS are unchecked, net amount equals base amount (500000.00)
        $this->assertDatabaseHas('inter_company_transactions', [
            'transaction_type' => 'Loan Given',
            'from_company_id' => $this->companyA->id,
            'to_company_id' => $this->companyB->id,
            'amount' => 500000.00,
            'is_gst_applicable' => 0,
            'igst_amount' => 0.00,
            'is_tds_applicable' => 0,
            'tds_rate' => 0.00,
            'tds_amount' => 0.00,
            'net_amount' => 500000.00,
            'status' => 'Approved',
        ]);
    }

    public function test_can_view_transaction_details_via_show_endpoint(): void
    {
        $tx = InterCompanyTransaction::create([
            'transaction_no' => 'ICT-TEST-0001',
            'transaction_date' => '2026-10-15',
            'transaction_type' => 'Service',
            'from_company_id' => $this->companyA->id,
            'to_company_id' => $this->companyB->id,
            'amount' => 75000.00,
            'currency' => 'INR',
            'exchange_rate' => 1.0,
            'is_gst_applicable' => true,
            'igst_amount' => 13500.00,
            'is_tds_applicable' => true,
            'tds_section' => '194J',
            'tds_rate' => 10.00,
            'tds_amount' => 7500.00,
            'net_amount' => 81000.00,
            'reference_invoice' => 'SRV-001',
            'status' => 'Settled',
            'approved_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->get(route('organization.sister-concerns.transactions.show', $tx));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'transaction' => [
                'id' => $tx->id,
                'transaction_no' => 'ICT-TEST-0001',
                'transaction_type' => 'Service',
                'amount' => '75000.00',
                'net_amount' => '81000.00',
                'status' => 'Settled',
                'from_company' => [
                    'id' => $this->companyA->id,
                    'name' => 'Nexus Electronics Pvt Ltd',
                ],
                'to_company' => [
                    'id' => $this->companyB->id,
                    'name' => 'Nexus Logistics Pvt Ltd',
                ],
            ],
        ]);
    }

    public function test_can_edit_and_update_existing_transaction(): void
    {
        $tx = InterCompanyTransaction::create([
            'transaction_no' => 'ICT-TEST-0002',
            'transaction_date' => '2026-10-10',
            'transaction_type' => 'Purchase',
            'from_company_id' => $this->companyB->id,
            'to_company_id' => $this->companyA->id,
            'amount' => 20000.00,
            'currency' => 'INR',
            'exchange_rate' => 1.0,
            'status' => 'Pending',
        ]);

        $updatePayload = [
            'transaction_date' => '2026-10-12',
            'transaction_type' => 'Purchase',
            'from_company_id' => $this->companyB->id,
            'to_company_id' => $this->companyA->id,
            'amount' => 25000.00,
            'currency' => 'INR',
            'exchange_rate' => 1.0,
            'is_gst_applicable' => true,
            'igst_amount' => 4500.00,
            'is_tds_applicable' => false,
            'status' => 'Settled',
            'reference_invoice' => 'PUR-UPDATED-001',
        ];

        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->put(route('organization.sister-concerns.transactions.update', $tx), $updatePayload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('inter_company_transactions', [
            'id' => $tx->id,
            'amount' => 25000.00,
            'igst_amount' => 4500.00,
            'net_amount' => 29500.00,
            'status' => 'Settled',
            'reference_invoice' => 'PUR-UPDATED-001',
        ]);
    }

    public function test_summary_cards_and_tab_counts_reflect_real_database_transactions(): void
    {
        InterCompanyTransaction::create([
            'transaction_no' => 'ICT-SUM-001',
            'transaction_date' => '2026-10-18',
            'transaction_type' => 'Sale',
            'from_company_id' => $this->companyA->id,
            'to_company_id' => $this->companyB->id,
            'amount' => 100000.00,
            'currency' => 'INR',
            'exchange_rate' => 1.0,
            'status' => 'Pending',
        ]);

        InterCompanyTransaction::create([
            'transaction_no' => 'ICT-SUM-002',
            'transaction_date' => '2026-10-19',
            'transaction_type' => 'Loan Given',
            'from_company_id' => $this->companyB->id,
            'to_company_id' => $this->companyA->id,
            'amount' => 200000.00,
            'currency' => 'INR',
            'exchange_rate' => 1.0,
            'status' => 'Settled',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.sister-concerns.transactions'));

        $response->assertStatus(200);
        $response->assertViewHas('summary', function ($summary) {
            return $summary['total_transactions'] === 2
                && $summary['pending_count'] === 1
                && $summary['settled_count'] === 1
                && $summary['total_value'] == 300000.00;
        });

        $response->assertViewHas('tabCounts', function ($tabCounts) {
            return $tabCounts['transactions'] === 2;
        });
    }

    public function test_can_delete_transaction(): void
    {
        $tx = InterCompanyTransaction::create([
            'transaction_no' => 'ICT-DEL-0001',
            'transaction_date' => '2026-10-18',
            'transaction_type' => 'Sale',
            'from_company_id' => $this->companyA->id,
            'to_company_id' => $this->companyB->id,
            'amount' => 10000.00,
            'currency' => 'INR',
            'exchange_rate' => 1.0,
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->delete(route('organization.sister-concerns.transactions.destroy', $tx));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('inter_company_transactions', [
            'id' => $tx->id,
        ]);
    }

    public function test_unauthorized_user_cannot_delete_transaction(): void
    {
        $tx = InterCompanyTransaction::create([
            'transaction_no' => 'ICT-UNAUTH-0001',
            'transaction_date' => '2026-10-18',
            'transaction_type' => 'Sale',
            'from_company_id' => $this->companyA->id,
            'to_company_id' => $this->companyB->id,
            'amount' => 10000.00,
            'currency' => 'INR',
            'exchange_rate' => 1.0,
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($this->regularUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->delete(route('organization.sister-concerns.transactions.destroy', $tx));

        $response->assertStatus(403);
        $this->assertDatabaseHas('inter_company_transactions', [
            'id' => $tx->id,
        ]);
    }
}

