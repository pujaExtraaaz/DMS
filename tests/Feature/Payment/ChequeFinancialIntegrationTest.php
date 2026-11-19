<?php

namespace Tests\Feature\Payment;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\ChequeBook;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\PaymentAllocation;
use App\Domains\Payment\Services\ChequeService;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChequeFinancialIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected ChequeBook $chequeBook;
    protected ChequeService $chequeService;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->company = Company::create([
            'name' => 'Apex Corporation',
            'code' => 'APX-01',
            'is_active' => true,
            'is_default' => true,
            'bank_accounts' => [
                [
                    'bank_name' => 'HDFC Bank',
                    'bank_account_no' => '50100200300400',
                    'bank_ifsc' => 'HDFC0001234',
                    'branch' => 'Commercial Branch',
                ],
            ],
        ]);

        $this->user->update(['company_id' => $this->company->id]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'is_active' => true,
        ]);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => '2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
        ]);

        $type = CustomerType::create([
            'company_id' => $this->company->id,
            'name' => 'Standard Client',
            'code' => 'STD',
            'is_active' => true,
        ]);

        $area = Area::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Business Park',
            'code' => 'BP-01',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_type_id' => $type->id,
            'area_id' => $area->id,
            'code' => 'CUST-APX-001',
            'name' => 'BlueStar Enterprises',
            'phone' => '9822012345',
            'is_active' => true,
        ]);

        $this->chequeBook = ChequeBook::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50100200300400',
            'bank_name' => 'HDFC Bank',
            'cheque_book_name' => 'HDFC Corporate Book 1',
            'from_number' => '100001',
            'to_number' => '100050',
            'from_numeric' => 100001,
            'to_numeric' => 100050,
            'total_cheques' => 50,
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);

        $this->chequeService = app(ChequeService::class);
    }

    public function test_1_receive_customer_cheque_updates_payment_invoice_and_outstanding(): void
    {
        // 1. Create Invoice of ₹25,000
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-TEST-001',
            'invoice_date' => '2026-05-10',
            'grand_total' => 25000.00,
            'paid_amount' => 0.00,
            'status' => 'issued',
        ]);

        app(OutstandingLedgerService::class)->recordInvoice($invoice);
        $this->assertEquals(25000.00, app(OutstandingLedgerService::class)->getCurrentBalance($this->customer->id));

        // 2. Receive Payment via Cheque of ₹25,000
        $response = $this->actingAs($this->user)->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => 25000.00,
            'method' => 'cheque',
            'cheque_no' => 'CHQ-889901',
            'cheque_date' => '2026-05-12',
            'cheque_bank_name' => 'Axis Bank',
            'notes' => 'Received full payment by cheque',
        ]);

        $response->assertRedirect();

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(25000.00, (float) $invoice->paid_amount);

        // Outstanding balance should now be 0
        $this->assertEquals(0.00, app(OutstandingLedgerService::class)->getCurrentBalance($this->customer->id));

        // Check Cheque created and linked
        $cheque = Cheque::where('cheque_no', 'CHQ-889901')->first();
        $this->assertNotNull($cheque);
        $this->assertEquals(Cheque::STATUS_RECEIVED, $cheque->status);
        $this->assertNotNull($cheque->payment_id);

        $payment = Payment::find($cheque->payment_id);
        $this->assertEquals('completed', $payment->status);
        $this->assertEquals(25000.00, (float) $payment->amount);
    }

    public function test_2_bounce_received_cheque_reverses_payment_invoice_and_restores_ledger(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-TEST-002',
            'invoice_date' => '2026-05-10',
            'grand_total' => 30000.00,
            'paid_amount' => 30000.00,
            'status' => 'paid',
        ]);

        app(OutstandingLedgerService::class)->recordInvoice($invoice);

        $payment = Payment::create([
            'payment_no' => 'PAY-2026-0002',
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount' => 30000.00,
            'method' => 'cheque',
            'status' => 'completed',
            'paid_at' => now(),
            'recorded_by' => $this->user->id,
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 30000.00,
        ]);

        app(OutstandingLedgerService::class)->recordPayment($payment);
        $this->assertEquals(0.00, app(OutstandingLedgerService::class)->getCurrentBalance($this->customer->id));

        $cheque = Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50100200300400',
            'cheque_no' => 'CHQ-771122',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_RECEIVED,
            'amount' => 30000.00,
            'status' => Cheque::STATUS_DEPOSITED,
            'payment_id' => $payment->id,
            'recorded_by' => $this->user->id,
        ]);

        // Bounce the cheque via ChequeService
        $bounce = $this->chequeService->bounce($cheque, 'Signature Mismatch', 250.00);

        $this->assertNotNull($bounce);
        $cheque->refresh();
        $this->assertEquals(Cheque::STATUS_BOUNCED, $cheque->status);
        $this->assertEquals(1, $cheque->bounce_count);
        $this->assertEquals('Signature Mismatch', $cheque->bounce_reason);

        // 1. Payment status must be 'bounced'
        $payment->refresh();
        $this->assertEquals('bounced', $payment->status);

        // 2. Invoice paid_amount reversed and status recalculated to 'issued'
        $invoice->refresh();
        $this->assertEquals(0.00, (float) $invoice->paid_amount);
        $this->assertEquals('issued', $invoice->status);

        // 3. OutstandingLedger restored back to 30,000 via adjustment debit
        $currentBalance = app(OutstandingLedgerService::class)->getCurrentBalance($this->customer->id);
        $this->assertEquals(30000.00, $currentBalance);

        $adjustment = OutstandingLedger::where('customer_id', $this->customer->id)
            ->where('type', 'adjustment')
            ->latest('id')
            ->first();
        $this->assertNotNull($adjustment);
        $this->assertEquals(30000.00, (float) $adjustment->debit);
        $this->assertEquals(0.00, (float) $adjustment->credit);
        $this->assertStringContainsString('Cheque bounce reversal', $adjustment->notes);

        // 4. Customer bounce count incremented
        $this->customer->refresh();
        $this->assertEquals(1, $this->customer->cheque_bounce_count);
    }

    public function test_3_cancel_received_cheque_reverses_payment_and_restores_invoice(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-TEST-003',
            'invoice_date' => '2026-06-01',
            'grand_total' => 15000.00,
            'paid_amount' => 15000.00,
            'status' => 'paid',
        ]);

        app(OutstandingLedgerService::class)->recordInvoice($invoice);

        $payment = Payment::create([
            'payment_no' => 'PAY-2026-0003',
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount' => 15000.00,
            'method' => 'cheque',
            'status' => 'completed',
            'paid_at' => now(),
            'recorded_by' => $this->user->id,
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 15000.00,
        ]);

        app(OutstandingLedgerService::class)->recordPayment($payment);
        $this->assertEquals(0.00, app(OutstandingLedgerService::class)->getCurrentBalance($this->customer->id));

        $cheque = Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50100200300400',
            'cheque_no' => 'CHQ-554433',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_RECEIVED,
            'amount' => 15000.00,
            'status' => Cheque::STATUS_RECEIVED,
            'payment_id' => $payment->id,
            'recorded_by' => $this->user->id,
        ]);

        // Cancel the cheque
        $this->chequeService->cancel($cheque, 'Client requested replacement cheque');

        $cheque->refresh();
        $this->assertEquals(Cheque::STATUS_CANCELLED, $cheque->status);
        $this->assertEquals('Client Requested Replacement Cheque', $cheque->cancelled_reason);

        // Payment marked cancelled
        $payment->refresh();
        $this->assertEquals('cancelled', $payment->status);

        // Invoice restored
        $invoice->refresh();
        $this->assertEquals(0.00, (float) $invoice->paid_amount);
        $this->assertEquals('issued', $invoice->status);

        // Outstanding ledger restored
        $this->assertEquals(15000.00, app(OutstandingLedgerService::class)->getCurrentBalance($this->customer->id));
    }

    public function test_4_cancel_outgoing_cheque_does_not_modify_customer_ledger(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-OUT-001',
            'invoice_date' => '2026-06-01',
            'grand_total' => 50000.00,
            'paid_amount' => 0.00,
            'status' => 'issued',
        ]);

        $payment = Payment::create([
            'payment_no' => 'PAY-OUT-0001',
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount' => 50000.00,
            'method' => 'cheque',
            'status' => 'completed',
            'paid_at' => now(),
            'recorded_by' => $this->user->id,
        ]);

        $cheque = Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50100200300400',
            'cheque_book_id' => $this->chequeBook->id,
            'cheque_no' => '100001',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_ISSUED,
            'direction' => 'issued_to_vendor',
            'amount' => 50000.00,
            'status' => Cheque::STATUS_ISSUED,
            'payment_id' => $payment->id,
            'recorded_by' => $this->user->id,
        ]);

        $initialAdjustmentCount = OutstandingLedger::where('type', 'adjustment')->count();

        // Cancel the outgoing cheque
        $this->chequeService->cancel($cheque, 'Wrong vendor payee name');

        $cheque->refresh();
        $this->assertEquals(Cheque::STATUS_CANCELLED, $cheque->status);

        // Payment status marked cancelled
        $payment->refresh();
        $this->assertEquals('cancelled', $payment->status);

        // Must NOT create customer debtor adjustment
        $this->assertEquals($initialAdjustmentCount, OutstandingLedger::where('type', 'adjustment')->count());
    }

    public function test_5_clear_cheque_synchronizes_payment_metadata_and_reconciliation(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-CLEAR-001',
            'invoice_date' => '2026-05-15',
            'grand_total' => 12000.00,
            'paid_amount' => 12000.00,
            'status' => 'paid',
        ]);

        $payment = Payment::create([
            'payment_no' => 'PAY-2026-0005',
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount' => 12000.00,
            'method' => 'cheque',
            'status' => 'completed',
            'paid_at' => now(),
            'recorded_by' => $this->user->id,
        ]);

        $cheque = Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50100200300400',
            'cheque_no' => 'CHQ-990011',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_RECEIVED,
            'amount' => 12000.00,
            'status' => Cheque::STATUS_DEPOSITED,
            'deposit_date' => '2026-05-15',
            'payment_id' => $payment->id,
            'recorded_by' => $this->user->id,
        ]);

        $this->chequeService->clear($cheque, '2026-05-18');

        $cheque->refresh();
        $this->assertEquals(Cheque::STATUS_CLEARED, $cheque->status);
        $this->assertEquals('2026-05-18', $cheque->clearance_date->toDateString());

        // Payment notes synchronized
        $payment->refresh();
        $this->assertEquals('completed', $payment->status);
        $this->assertStringContainsString('CHQ-990011 cleared on 2026-05-18', (string) $payment->notes);
    }

    public function test_6_partial_payments_and_bouncing_maintains_accurate_partial_status(): void
    {
        // Invoice of ₹40,000
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-TEST-PARTIAL',
            'invoice_date' => '2026-06-01',
            'grand_total' => 40000.00,
            'paid_amount' => 0.00,
            'status' => 'issued',
        ]);

        app(OutstandingLedgerService::class)->recordInvoice($invoice);

        // Payment 1: ₹15,000 Cash
        $cashPayment = Payment::create([
            'payment_no' => 'PAY-CASH-001',
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount' => 15000.00,
            'method' => 'cash',
            'status' => 'completed',
            'paid_at' => now(),
            'recorded_by' => $this->user->id,
        ]);
        PaymentAllocation::create([
            'payment_id' => $cashPayment->id,
            'invoice_id' => $invoice->id,
            'amount' => 15000.00,
        ]);
        $invoice->update(['paid_amount' => 15000.00, 'status' => 'partial']);
        app(OutstandingLedgerService::class)->recordPayment($cashPayment);

        // Payment 2: ₹25,000 Cheque (making it fully paid)
        $chequePayment = Payment::create([
            'payment_no' => 'PAY-CHQ-002',
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount' => 25000.00,
            'method' => 'cheque',
            'status' => 'completed',
            'paid_at' => now(),
            'recorded_by' => $this->user->id,
        ]);
        PaymentAllocation::create([
            'payment_id' => $chequePayment->id,
            'invoice_id' => $invoice->id,
            'amount' => 25000.00,
        ]);
        $invoice->update(['paid_amount' => 40000.00, 'status' => 'paid']);
        app(OutstandingLedgerService::class)->recordPayment($chequePayment);

        $this->assertEquals(0.00, app(OutstandingLedgerService::class)->getCurrentBalance($this->customer->id));

        $cheque = Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50100200300400',
            'cheque_no' => 'CHQ-PARTIAL-25K',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_RECEIVED,
            'amount' => 25000.00,
            'status' => Cheque::STATUS_DEPOSITED,
            'payment_id' => $chequePayment->id,
            'recorded_by' => $this->user->id,
        ]);

        // Bounce the ₹25,000 cheque
        $this->chequeService->bounce($cheque, 'Refer to Drawer', 100.00);

        // Verify:
        // Cash payment remains completed
        $cashPayment->refresh();
        $this->assertEquals('completed', $cashPayment->status);

        // Cheque payment bounced
        $chequePayment->refresh();
        $this->assertEquals('bounced', $chequePayment->status);

        // Invoice paid_amount = 40,000 - 25,000 = 15,000 and status = 'partial'!
        $invoice->refresh();
        $this->assertEquals(15000.00, (float) $invoice->paid_amount);
        $this->assertEquals('partial', $invoice->status);

        // Customer outstanding balance = 25,000 (40,000 - 15,000 cash)
        $this->assertEquals(25000.00, app(OutstandingLedgerService::class)->getCurrentBalance($this->customer->id));
    }

    public function test_7_duplicate_bounce_and_cancellation_operations_are_idempotent(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-IDEMPOTENT',
            'invoice_date' => '2026-06-15',
            'grand_total' => 20000.00,
            'paid_amount' => 20000.00,
            'status' => 'paid',
        ]);

        app(OutstandingLedgerService::class)->recordInvoice($invoice);

        $payment = Payment::create([
            'payment_no' => 'PAY-IDEM-001',
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount' => 20000.00,
            'method' => 'cheque',
            'status' => 'completed',
            'paid_at' => now(),
            'recorded_by' => $this->user->id,
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 20000.00,
        ]);

        app(OutstandingLedgerService::class)->recordPayment($payment);

        $cheque = Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50100200300400',
            'cheque_no' => 'CHQ-IDEM-001',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_RECEIVED,
            'amount' => 20000.00,
            'status' => Cheque::STATUS_DEPOSITED,
            'payment_id' => $payment->id,
            'recorded_by' => $this->user->id,
        ]);

        // First bounce
        $this->chequeService->bounce($cheque, 'Bounce 1', 100.00);

        $invoice->refresh();
        $this->assertEquals(0.00, (float) $invoice->paid_amount);
        $this->assertEquals('issued', $invoice->status);
        $this->assertEquals(20000.00, app(OutstandingLedgerService::class)->getCurrentBalance($this->customer->id));

        $adjustmentsCount1 = OutstandingLedger::where('customer_id', $this->customer->id)->where('type', 'adjustment')->count();
        $this->assertEquals(1, $adjustmentsCount1);

        // Second bounce attempt on the same cheque
        $this->chequeService->bounce($cheque, 'Bounce 2', 50.00);

        // Paid amount must NOT go negative or reduce twice!
        $invoice->refresh();
        $this->assertEquals(0.00, (float) $invoice->paid_amount);
        $this->assertEquals('issued', $invoice->status);

        // Adjustments count must still be 1 (no duplicate adjustment debit)
        $adjustmentsCount2 = OutstandingLedger::where('customer_id', $this->customer->id)->where('type', 'adjustment')->count();
        $this->assertEquals(1, $adjustmentsCount2);
        $this->assertEquals(20000.00, app(OutstandingLedgerService::class)->getCurrentBalance($this->customer->id));
    }

    public function test_8_party_statement_accurately_reflects_bounced_cheque_adjustment(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => 'INV-STATEMENT-01',
            'invoice_date' => '2026-05-01',
            'grand_total' => 18000.00,
            'paid_amount' => 18000.00,
            'status' => 'paid',
        ]);

        app(OutstandingLedgerService::class)->recordInvoice($invoice);

        $payment = Payment::create([
            'payment_no' => 'PAY-STMT-01',
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount' => 18000.00,
            'method' => 'cheque',
            'status' => 'completed',
            'paid_at' => '2026-05-02',
            'recorded_by' => $this->user->id,
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 18000.00,
        ]);

        app(OutstandingLedgerService::class)->recordPayment($payment);

        $cheque = Cheque::create([
            'company_id' => $this->company->id,
            'bank_account_no' => '50100200300400',
            'cheque_no' => 'CHQ-STMT-01',
            'customer_id' => $this->customer->id,
            'cheque_type' => Cheque::TYPE_RECEIVED,
            'amount' => 18000.00,
            'status' => Cheque::STATUS_DEPOSITED,
            'payment_id' => $payment->id,
            'recorded_by' => $this->user->id,
        ]);

        $this->chequeService->bounce($cheque, 'Account Closed', 300.00);

        // Fetch Party Statement report
        $response = $this->actingAs($this->user)->get(route('reports.party-statement', [
            'customer_id' => $this->customer->id,
            'date_from' => '2026-04-01',
            'date_to' => '2026-12-31',
        ]));

        $response->assertOk();
        // Should show invoice (18,000), payment (18,000), and adjustment (18,000)
        $response->assertSee('18,000.00');
        $response->assertSee('Adjustment');
        $response->assertSee('Cheque bounce reversal');
    }
}
