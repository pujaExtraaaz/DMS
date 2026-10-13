<?php

namespace Tests\Unit\Tally\Purchasing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Parties\PartyService;
use Tally\Purchasing\GoodsReceiptService;
use Tally\Purchasing\RfqService;

class ConvertFlowsTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_rfq_selected_quote_converts_to_purchase_order(): void
    {
        $company = $this->makeCompany();
        $year = $company->financialYears()->first();
        $branch = $company->branches()->first();
        $user = User::factory()->create();

        $party = app(PartyService::class)->save($company, [
            'type' => 'supplier',
            'name' => 'Quote Vendor',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        $rfqService = app(RfqService::class);
        $rfq = $rfqService->save($company, $branch, $year, $user, [
            'rfq_date' => $year->start_date->toDateString(),
            'status' => 'open',
            'lines' => [
                ['item_name' => 'Steel rod', 'quantity' => '10', 'unit' => 'Kg'],
            ],
        ]);

        $quote = $rfqService->saveVendorQuote($company, $rfq, [
            'supplier_ledger_id' => $party->ledger_id,
            'quote_date' => $year->start_date->toDateString(),
            'lines' => [
                ['item_name' => 'Steel rod', 'quantity' => '10', 'rate' => '50', 'tax_rate' => '0'],
            ],
        ]);
        $rfqService->selectWinner($company, $rfq, $quote);

        $order = $rfqService->convertToPurchaseOrder($company, $branch, $year, $user, $rfq->fresh());

        $this->assertSame($party->ledger_id, $order->supplier_ledger_id);
        $this->assertSame($rfq->number, $order->reference_number);
        $this->assertSame($quote->id, $order->vendor_quotation_id);
        $this->assertCount(1, $order->lines);
        $this->assertSame('Steel rod', $order->lines->first()->item_name);
        $this->assertSame('10.0000', (string) $order->lines->first()->quantity);
    }

    public function test_goods_receipt_created_from_purchase_order(): void
    {
        $company = $this->makeCompany();
        $year = $company->financialYears()->first();
        $branch = $company->branches()->first();
        $user = User::factory()->create();

        $party = app(PartyService::class)->save($company, [
            'type' => 'supplier',
            'name' => 'Receive Vendor',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        $rfqService = app(RfqService::class);
        $rfq = $rfqService->save($company, $branch, $year, $user, [
            'rfq_date' => $year->start_date->toDateString(),
            'lines' => [
                ['item_name' => 'Bolts', 'quantity' => '5', 'unit' => 'Nos'],
            ],
        ]);
        $quote = $rfqService->saveVendorQuote($company, $rfq, [
            'supplier_ledger_id' => $party->ledger_id,
            'quote_date' => $year->start_date->toDateString(),
            'lines' => [
                ['item_name' => 'Bolts', 'quantity' => '5', 'rate' => '20', 'tax_rate' => '0'],
            ],
        ]);
        $rfqService->selectWinner($company, $rfq, $quote);
        $order = $rfqService->convertToPurchaseOrder($company, $branch, $year, $user, $rfq->fresh());

        $receipt = app(GoodsReceiptService::class)->createFromPurchaseOrder(
            $company,
            $branch,
            $year,
            $user,
            $order,
        );

        $this->assertSame('draft', $receipt->status);
        $this->assertSame($order->id, $receipt->purchase_order_id);
        $this->assertSame($party->ledger_id, $receipt->supplier_ledger_id);
        $this->assertCount(1, $receipt->lines);
        $this->assertSame('Bolts', $receipt->lines->first()->item_name);
        $this->assertEquals(5.0, (float) $receipt->lines->first()->received_qty);
        $this->assertStringStartsWith('GRN-', $receipt->number);
    }
}
