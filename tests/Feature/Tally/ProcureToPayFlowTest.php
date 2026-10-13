<?php

namespace Tests\Feature\Tally;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Accounting\VoucherStatus;
use Tally\Invoicing\InvoiceService;
use Tally\Models\ElectronicPayment;
use Tally\Models\Ledger;
use Tally\Parties\PartyService;
use Tally\Payables\PaymentBatchService;
use Tally\Purchasing\GoodsReceiptService;
use Tally\Purchasing\PurchaseRequisitionService;
use Tally\Purchasing\RfqService;

class ProcureToPayFlowTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_pr_to_rfq_to_po_to_grn_to_bill_to_payment_batch(): void
    {
        $company = $this->makeCompany();
        $year = $company->financialYears()->first();
        $branch = $company->branches()->first();
        $user = User::factory()->create();

        $party = app(PartyService::class)->save($company, [
            'type' => 'supplier',
            'name' => 'P2P Vendor',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        $purchaseLedger = $company->ledgers()
            ->whereHas('accountGroup', fn ($q) => $q->where('code', 'PURCHASE'))
            ->first();
        if (! $purchaseLedger) {
            $group = $company->accountGroups()->where('code', 'PURCHASE')->first();
            $purchaseLedger = Ledger::query()->create([
                'company_id' => $company->id,
                'account_group_id' => $group->id,
                'name' => 'Purchase A/c',
                'opening_balance' => '0.00',
                'opening_balance_type' => 'debit',
                'is_active' => true,
            ]);
        }

        $bankGroup = $company->accountGroups()->where('code', 'BANK')->first();
        $bankLedger = $company->ledgers()->where('account_group_id', $bankGroup->id)->first()
            ?? Ledger::query()->create([
                'company_id' => $company->id,
                'account_group_id' => $bankGroup->id,
                'name' => 'HDFC Bank',
                'opening_balance' => '0.00',
                'opening_balance_type' => 'debit',
                'is_active' => true,
            ]);

        $pr = app(PurchaseRequisitionService::class)->save($company, $branch, $year, $user, [
            'requisition_date' => $year->start_date->toDateString(),
            'status' => 'approved',
            'lines' => [
                ['item_name' => 'Steel rod', 'quantity' => '10', 'unit' => 'Kg'],
            ],
        ]);

        $rfq = app(PurchaseRequisitionService::class)->convertToRfq($company, $branch, $year, $user, $pr);
        $this->assertSame($pr->id, $rfq->purchase_requisition_id);
        $this->assertCount(1, $rfq->lines);

        $rfqService = app(RfqService::class);
        $quote = $rfqService->saveVendorQuote($company, $rfq, [
            'supplier_ledger_id' => $party->ledger_id,
            'quote_date' => $year->start_date->toDateString(),
            'lines' => [
                ['item_name' => 'Steel rod', 'quantity' => '10', 'rate' => '50', 'tax_rate' => '0'],
            ],
        ]);
        $rfqService->selectWinner($company, $rfq, $quote);
        $order = $rfqService->convertToPurchaseOrder($company, $branch, $year, $user, $rfq->fresh());
        $this->assertSame($pr->id, $order->purchase_requisition_id ?? $rfq->purchase_requisition_id);

        $grn = app(GoodsReceiptService::class)->createFromPurchaseOrder($company, $branch, $year, $user, $order);
        $grn = app(GoodsReceiptService::class)->post($grn, $user);
        $this->assertSame('posted', $grn->status);

        $bill = app(GoodsReceiptService::class)->createPurchaseBill($grn, $user, $purchaseLedger->id);
        $this->assertSame($grn->id, $bill->goods_receipt_id);
        $bill = app(InvoiceService::class)->post($bill, $user);
        $this->assertTrue($bill->isPosted());
        $this->assertSame(VoucherStatus::Posted, $bill->status);

        $batch = app(PaymentBatchService::class)->createFromBills(
            $company,
            [$bill->id],
            $branch->id,
            $bankLedger->id,
        );
        $this->assertSame('draft', $batch->status);
        $this->assertSame($bill->id, $batch->lines->first()->bill_id);

        $batch = app(PaymentBatchService::class)->submit($company, $batch);
        $batch = app(PaymentBatchService::class)->approve($company, $batch, $user);

        $this->assertSame('paid', $batch->status);
        $this->assertGreaterThanOrEqual(1, ElectronicPayment::query()
            ->where('payment_batch_id', $batch->id)
            ->count());
    }
}
