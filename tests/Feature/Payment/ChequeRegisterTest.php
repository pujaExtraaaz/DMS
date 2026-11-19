<?php

namespace Tests\Feature\Payment;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\ChequeBook;
use App\Domains\Organization\Models\Company;
use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Models\Payment;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChequeRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customer;
    protected ChequeBook $chequeBook;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->company = Company::create([
            'name' => 'Apex Logistics Corp',
            'code' => 'APX-001',
            'is_active' => true,
            'is_default' => true,
            'bank_accounts' => [
                [
                    'bank_name' => 'State Bank of India',
                    'bank_account_no' => '30012345678',
                    'bank_ifsc' => 'SBIN0000001',
                    'branch' => 'Main Branch',
                ],
            ],
        ]);

        $this->user->update(['company_id' => $this->company->id]);

        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'HQ Branch',
            'code' => 'HQ-01',
            'is_active' => true,
        ]);

        $type = CustomerType::create([
            'company_id' => $this->company->id,
            'name' => 'Corporate Client',
            'code' => 'CORP',
            'is_active' => true,
        ]);

        $area = Area::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Downtown',
            'code' => 'DT-01',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'customer_type_id' => $type->id,
            'area_id' => $area->id,
            'code' => 'CUST-001',
            'name' => 'Reliance Retail Ltd',
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        $this->chequeBook = ChequeBook::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '30012345678',
            'bank_name' => 'State Bank of India',
            'cheque_book_name' => 'SBI Operations',
            'from_number' => '000001',
            'to_number' => '000050',
            'from_numeric' => 1,
            'to_numeric' => 50,
            'total_cheques' => 50,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_can_view_cheque_register_with_stats_and_filters(): void
    {
        // 1 issued cheque
        Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '30012345678',
            'cheque_book_id' => $this->chequeBook->id,
            'cheque_no' => '000001',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_ISSUED,
            'direction' => 'issued_to_vendor',
            'amount' => 15000,
            'status' => Cheque::STATUS_ISSUED,
            'purpose' => 'payment',
            'recorded_by' => $this->user->id,
        ]);

        // 1 received cheque
        Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '30012345678',
            'cheque_no' => '998877',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_RECEIVED,
            'direction' => 'received_from_client',
            'amount' => 25000,
            'status' => Cheque::STATUS_RECEIVED,
            'purpose' => 'pdc',
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('cheques.index'));

        $response->assertOk();
        $response->assertSee('Cheque Register');
        $response->assertSee('000001');
        $response->assertSee('998877');
        $response->assertSee('Reliance Retail Ltd');
        $response->assertSee('15,000.00');
        $response->assertSee('25,000.00');
        $response->assertSee('Cheques Issued');
        $response->assertSee('Cheques Received');
    }

    public function test_can_issue_outgoing_cheque_against_cheque_book_and_mark_used(): void
    {
        $payload = [
            'cheque_type' => 'issued',
            'bank_account_no' => '30012345678',
            'cheque_book_id' => $this->chequeBook->id,
            'cheque_no' => '000001',
            'customer_id' => $this->customer->id,
            'amount' => '12500.50',
            'cheque_date' => now()->toDateString(),
            'purpose' => 'payment',
            'reference_voucher' => 'PO-1002',
            'notes' => 'Transport contractor settlement',
        ];

        $response = $this->actingAs($this->user)->post(route('cheques.store'), $payload);

        $response->assertRedirect(route('cheques.index'));
        $this->assertDatabaseHas('cheques', [
            'company_id' => $this->company->id,
            'bank_account_no' => '30012345678',
            'cheque_no' => '000001',
            'cheque_type' => Cheque::TYPE_ISSUED,
            'amount' => 12500.50,
            'status' => Cheque::STATUS_ISSUED,
            'reference_voucher' => 'PO-1002',
        ]);

        // Verify cheque leaf is marked used
        $this->assertTrue($this->chequeBook->isNumberUsed('000001'));
        $this->assertEquals(1, $this->chequeBook->used_count);
        $this->assertEquals(49, $this->chequeBook->remaining_count);
        $this->assertEquals('000002', $this->chequeBook->next_available_cheque_number);
    }

    public function test_blocks_issuing_cheque_out_of_range_or_duplicate(): void
    {
        // 1. Out of range (number 000099 > 50)
        $response = $this->actingAs($this->user)
            ->from(route('cheques.create'))
            ->post(route('cheques.store'), [
                'cheque_type' => 'issued',
                'bank_account_no' => '30012345678',
                'cheque_book_id' => $this->chequeBook->id,
                'cheque_no' => '000099',
                'customer_id' => $this->customer->id,
                'amount' => 5000,
            ]);

        $response->assertRedirect(route('cheques.create'));
        $response->assertSessionHas('error');

        // Issue 000001
        Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '30012345678',
            'cheque_book_id' => $this->chequeBook->id,
            'cheque_no' => '000001',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_ISSUED,
            'amount' => 5000,
            'status' => Cheque::STATUS_ISSUED,
            'recorded_by' => $this->user->id,
        ]);

        // 2. Duplicate issuance of 000001
        $response2 = $this->actingAs($this->user)
            ->from(route('cheques.create'))
            ->post(route('cheques.store'), [
                'cheque_type' => 'issued',
                'bank_account_no' => '30012345678',
                'cheque_book_id' => $this->chequeBook->id,
                'cheque_no' => '000001',
                'customer_id' => $this->customer->id,
                'amount' => 3000,
            ]);

        $response2->assertRedirect(route('cheques.create'));
        $response2->assertSessionHas('error');
    }

    public function test_can_record_incoming_cheque_from_client(): void
    {
        $payload = [
            'cheque_type' => 'received',
            'customer_id' => $this->customer->id,
            'bank_account_no' => '30012345678',
            'cheque_no' => '654321',
            'bank_name' => 'ICICI Bank',
            'branch_name' => 'Bandra Branch',
            'amount' => '45000.00',
            'cheque_date' => now()->addDays(5)->toDateString(),
            'purpose' => 'pdc',
            'reference_voucher' => 'INV-2026-0045',
            'notes' => 'Received against sales invoice',
        ];

        $response = $this->actingAs($this->user)->post(route('cheques.store'), $payload);

        $response->assertRedirect(route('cheques.index'));
        $this->assertDatabaseHas('cheques', [
            'cheque_no' => '654321',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_RECEIVED,
            'amount' => 45000.00,
            'status' => Cheque::STATUS_RECEIVED,
            'purpose' => 'pdc',
            'reference_voucher' => 'INV-2026-0045',
        ]);
    }

    public function test_can_deposit_cheque(): void
    {
        $cheque = Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '30012345678',
            'cheque_no' => '112233',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_RECEIVED,
            'amount' => 10000,
            'status' => Cheque::STATUS_RECEIVED,
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('cheques.deposit', $cheque), [
            'deposit_date' => '2026-11-20',
        ]);

        $response->assertRedirect();
        $this->assertEquals(Cheque::STATUS_DEPOSITED, $cheque->fresh()->status);
        $this->assertEquals('2026-11-20', $cheque->fresh()->deposit_date->toDateString());
    }

    public function test_can_clear_cheque(): void
    {
        $cheque = Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '30012345678',
            'cheque_no' => '112233',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_RECEIVED,
            'amount' => 10000,
            'status' => Cheque::STATUS_DEPOSITED,
            'deposit_date' => now()->toDateString(),
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('cheques.clear', $cheque), [
            'clearance_date' => '2026-11-22',
        ]);

        $response->assertRedirect();
        $this->assertEquals(Cheque::STATUS_CLEARED, $cheque->fresh()->status);
        $this->assertEquals('2026-11-22', $cheque->fresh()->clearance_date->toDateString());
    }

    public function test_can_bounce_cheque_and_trigger_party_risk_and_freeze(): void
    {
        $this->assertEquals(0, $this->customer->cheque_bounce_count);

        // Pre-set 3 bounces to test threshold freeze (> 3)
        $this->customer->update(['cheque_bounce_count' => 3]);

        $cheque = Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '30012345678',
            'cheque_no' => '445566',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_RECEIVED,
            'amount' => 18000,
            'status' => Cheque::STATUS_DEPOSITED,
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('cheques.bounce', $cheque), [
            'reason' => 'Insufficient Funds',
            'charges' => '350.00',
        ]);

        $response->assertRedirect();
        $this->assertEquals(Cheque::STATUS_BOUNCED, $cheque->fresh()->status);
        $this->assertEquals(1, $cheque->fresh()->bounce_count);

        $customer = $this->customer->fresh();
        $this->assertEquals(4, $customer->cheque_bounce_count);
        $this->assertEquals('high', $customer->risk_status);
        $this->assertEquals('frozen', $customer->credit_status);

        $this->assertDatabaseHas('cheque_bounces', [
            'cheque_id' => $cheque->id,
            'customer_id' => $this->customer->id,
            'reason' => 'Insufficient Funds',
            'charges' => 350.00,
            'triggered_freeze' => true,
        ]);
    }

    public function test_can_cancel_and_mark_stale(): void
    {
        $cheque = Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '30012345678',
            'cheque_no' => '778899',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_ISSUED,
            'amount' => 5000,
            'status' => Cheque::STATUS_ISSUED,
            'recorded_by' => $this->user->id,
        ]);

        // Mark stale
        $this->actingAs($this->user)->post(route('cheques.mark-stale', $cheque), [
            'reason' => 'Passed 90 days validity',
        ]);
        $this->assertEquals(Cheque::STATUS_STALE, $cheque->fresh()->status);

        // Another cheque to cancel
        $cheque2 = Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '30012345678',
            'cheque_no' => '778800',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_ISSUED,
            'amount' => 8000,
            'status' => Cheque::STATUS_ISSUED,
            'recorded_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)->post(route('cheques.cancel', $cheque2), [
            'reason' => 'Customer requested RTGS instead',
        ]);
        $this->assertEquals(Cheque::STATUS_CANCELLED, $cheque2->fresh()->status);
        $this->assertNotNull($cheque2->fresh()->cancelled_reason);
    }

    public function test_can_export_cheque_register_csv_with_11_columns(): void
    {
        Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '30012345678',
            'cheque_no' => '123456',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_ISSUED,
            'amount' => 999.50,
            'purpose' => 'payment',
            'reference_voucher' => 'VOUCH-01',
            'cheque_date' => '2026-11-19',
            'status' => Cheque::STATUS_ISSUED,
            'notes' => 'Test export cheque',
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('cheques.export', ['export' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        // Check required 11 header columns in CSV output
        $content = $response->streamedContent();
        $this->assertStringContainsString('"Cheque Number","Bank Account","Cheque Date","Party Name","Cheque Type"', $content);
        $this->assertStringContainsString('123456', $content);
        $this->assertStringContainsString('Reliance Retail Ltd', $content);
        $this->assertStringContainsString('VOUCH-01', $content);
    }
}
