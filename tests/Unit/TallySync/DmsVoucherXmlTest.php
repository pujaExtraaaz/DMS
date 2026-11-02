<?php

namespace Tests\Unit\TallySync;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseInwardItem;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Sales\Models\QuotationItem;
use App\Domains\Tally\Services\TallyExportService;
use App\Domains\Tally\Services\TallySyncMappingService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DmsVoucherXmlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tally_sync.ledgers' => [
                'sales' => 'Sales',
                'purchase' => 'Purchase',
                'purchase_interstate' => 'Purchase Interstate',
                'cash' => 'Cash',
                'bank' => 'Bank',
                'round_off' => 'Round Off',
                'output_cgst' => 'Output CGST',
                'output_sgst' => 'Output SGST',
                'output_igst' => 'Output IGST',
                'input_cgst' => 'Input CGST',
                'input_sgst' => 'Input SGST',
                'input_igst' => 'Input IGST',
            ],
            'tally_sync.default_godown' => 'Main Location',
        ]);
    }

    public function test_sales_invoice_xml_includes_gst_party_and_buyer_fields(): void
    {
        [$company, $customer, $product, $uom] = $this->seedTradingBase();

        $invoice = Invoice::query()->create([
            'company_id' => $company->id,
            'invoice_no' => 'INV-W3-1',
            'customer_id' => $customer->id,
            'invoice_date' => '2026-04-01',
            'status' => 'issued',
            'subtotal' => 1000,
            'tax_amount' => 180,
            'grand_total' => 1180,
            'notes' => 'Wave 3 sales',
            'delivery_state' => 'Maharashtra',
            'billing_address' => '12 MG Road, Pune',
            'reference_no' => 'SO-9',
        ]);

        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'quantity' => 2,
            'unit_price' => 500,
            'discount_amount' => 0,
            'tax_amount' => 180,
            'line_total' => 1180,
        ]);

        $xml = (new TallyExportService(new TallySyncMappingService))->buildInvoiceXml($invoice->fresh());

        $this->assertStringContainsString('<VOUCHERTYPENAME>Sales</VOUCHERTYPENAME>', $xml);
        $this->assertStringContainsString('<PARTYGSTIN>27AAAAA0000A1Z5</PARTYGSTIN>', $xml);
        $this->assertStringContainsString('<PLACEOFSUPPLY>Maharashtra</PLACEOFSUPPLY>', $xml);
        $this->assertStringContainsString('<BASICBUYERNAME>Wave Buyer</BASICBUYERNAME>', $xml);
        $this->assertStringContainsString('<BASICBUYERADDRESS>12 MG Road, Pune</BASICBUYERADDRESS>', $xml);
        $this->assertStringContainsString('<NARRATION>Wave 3 sales</NARRATION>', $xml);
        $this->assertStringContainsString('<REFERENCE>SO-9</REFERENCE>', $xml);
        $this->assertStringContainsString('<STOCKITEMNAME>Wave Widget</STOCKITEMNAME>', $xml);
        $this->assertStringContainsString('<LEDGERNAME>Output CGST</LEDGERNAME>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public function test_payment_xml_is_tally_receipt_with_cash_or_bank(): void
    {
        [$company, $customer] = $this->seedTradingBase();

        $invoice = Invoice::query()->create([
            'company_id' => $company->id,
            'invoice_no' => 'INV-W3-PAY',
            'customer_id' => $customer->id,
            'invoice_date' => '2026-04-02',
            'status' => 'issued',
            'subtotal' => 500,
            'tax_amount' => 0,
            'grand_total' => 500,
        ]);

        $payment = Payment::query()->create([
            'payment_no' => 'PAY-W3-1',
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'amount' => 500,
            'method' => 'cash',
            'status' => 'completed',
            'paid_at' => '2026-04-03 10:00:00',
            'notes' => 'Cash collection',
            'reference_no' => 'CHQ-1',
        ]);

        $xml = (new TallyExportService(new TallySyncMappingService))->buildPaymentXml($payment->fresh());

        $this->assertStringContainsString('<VOUCHERTYPENAME>Receipt</VOUCHERTYPENAME>', $xml);
        $this->assertStringContainsString('<LEDGERNAME>Cash</LEDGERNAME>', $xml);
        $this->assertStringContainsString('<NARRATION>Cash collection</NARRATION>', $xml);
        $this->assertStringContainsString('<NAME>INV-W3-PAY</NAME>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public function test_credit_note_xml_includes_reason_and_gst(): void
    {
        [, $customer] = $this->seedTradingBase();

        $creditNote = CreditNote::query()->create([
            'credit_note_no' => 'CN-W3-1',
            'customer_id' => $customer->id,
            'credit_note_date' => '2026-04-04',
            'reason' => 'return',
            'status' => 'posted',
            'subtotal' => 100,
            'tax_amount' => 18,
            'grand_total' => 118,
            'notes' => 'Damaged carton',
        ]);

        $xml = (new TallyExportService(new TallySyncMappingService))->buildCreditNoteXml($creditNote->fresh());

        $this->assertStringContainsString('<VOUCHERTYPENAME>Credit Note</VOUCHERTYPENAME>', $xml);
        $this->assertStringContainsString('<NARRATION>return | Damaged carton</NARRATION>', $xml);
        $this->assertStringContainsString('<LEDGERNAME>Sales</LEDGERNAME>', $xml);
        $this->assertStringContainsString('<LEDGERNAME>Output CGST</LEDGERNAME>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public function test_purchase_order_xml_covers_freight_dates_batch_and_addresses(): void
    {
        [$company, $customer, $product, $uom] = $this->seedTradingBase(true);

        $branch = Branch::query()->create([
            'company_id' => $company->id,
            'name' => 'HO',
            'code' => 'HO',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Main Store',
            'code' => 'MS01',
            'is_active' => true,
        ]);

        $order = PurchaseOrder::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $customer->id,
            'po_no' => 'PO-W3-1',
            'po_date' => '2026-04-05',
            'expected_date' => '2026-04-20',
            'credit_days' => 30,
            'due_date' => '2026-05-05',
            'status' => 'approved',
            'is_freight_applicable' => true,
            'freight_mode' => 'amount',
            'freight_amount' => 50,
            'freight_tax_rate' => 0,
            'freight_tax_amount' => 0,
            'freight_total' => 50,
            'freight_notes' => 'Door delivery',
            'subtotal' => 1000,
            'tax_amount' => 180,
            'grand_total' => 1230,
            'notes' => 'Rush order',
            'billing_address' => 'Factory Gate A',
            'shipping_address' => 'Warehouse Dock B',
        ]);

        PurchaseOrderItem::query()->create([
            'purchase_order_id' => $order->id,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'quantity' => 10,
            'unit_cost' => 100,
            'tax_percent' => 18,
            'line_total' => 1180,
            'batch_no' => 'BATCH-77',
            'cgst_percent' => 9,
            'sgst_percent' => 9,
            'cgst_amount' => 90,
            'sgst_amount' => 90,
        ]);

        $xml = (new TallyExportService(new TallySyncMappingService))->buildPurchaseOrderXml($order->fresh());

        $this->assertStringContainsString('<VOUCHERTYPENAME>Purchase Order</VOUCHERTYPENAME>', $xml);
        $this->assertStringContainsString('<BATCHNAME>BATCH-77</BATCHNAME>', $xml);
        $this->assertStringContainsString('<GODOWNNAME>Main Store</GODOWNNAME>', $xml);
        $this->assertStringContainsString('Expected: 20-Apr-2026', $xml);
        $this->assertStringContainsString('Credit: 30 days', $xml);
        $this->assertStringContainsString('Freight: 50.00', $xml);
        $this->assertStringContainsString('Door delivery', $xml);
        $this->assertStringContainsString('<BASICBUYERADDRESS>Warehouse Dock B</BASICBUYERADDRESS>', $xml);
        $this->assertStringContainsString('<REFERENCEDATE>20260420</REFERENCEDATE>', $xml);
        $this->assertStringContainsString('<LEDGERNAME>Input CGST</LEDGERNAME>', $xml);
        $this->assertStringContainsString('<PARTYGSTIN>27AAAAA0000A1Z5</PARTYGSTIN>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public function test_sales_order_xml_uses_sales_order_voucher_type(): void
    {
        [$company, $customer, $product, $uom] = $this->seedTradingBase();
        $salesperson = User::factory()->create();

        $branch = Branch::query()->create([
            'company_id' => $company->id,
            'name' => 'SO Branch',
            'code' => 'SOB',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'SO Store',
            'code' => 'SOS',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'order_no' => 'SO-W5-1',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'salesperson_id' => $salesperson->id,
            'order_date' => '2026-04-10',
            'status' => 'approved',
            'subtotal' => 1000,
            'tax_amount' => 180,
            'grand_total' => 1180,
            'notes' => 'Approved sales order',
            'billing_address' => 'Buyer Road 1',
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'quantity' => 2,
            'unit_price' => 500,
            'line_total' => 1000,
        ]);

        $xml = (new TallyExportService(new TallySyncMappingService))->buildSalesOrderXml($order->fresh());

        $this->assertStringContainsString('<VOUCHERTYPENAME>Sales Order</VOUCHERTYPENAME>', $xml);
        $this->assertStringContainsString('<STOCKITEMNAME>Wave Widget</STOCKITEMNAME>', $xml);
        $this->assertStringContainsString('<GODOWNNAME>'.$warehouse->fresh()->name.'</GODOWNNAME>', $xml);
        $this->assertStringContainsString('<NARRATION>DMS Sales Order SO-W5-1 | Approved sales order</NARRATION>', $xml);
        $this->assertStringContainsString('<ISOPTIONAL>No</ISOPTIONAL>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public function test_quotation_xml_is_optional_quotation_voucher(): void
    {
        [, $customer, $product, $uom] = $this->seedTradingBase();

        $quote = Quotation::query()->create([
            'quotation_no' => 'QT-W5-1',
            'customer_id' => $customer->id,
            'quotation_date' => '2026-04-11',
            'valid_until' => '2026-04-30',
            'status' => 'sent',
            'subtotal' => 500,
            'tax_amount' => 90,
            'grand_total' => 590,
            'notes' => 'Offer open',
        ]);
        QuotationItem::query()->create([
            'quotation_id' => $quote->id,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'quantity' => 1,
            'unit_price' => 500,
            'discount_amount' => 0,
            'line_total' => 500,
        ]);

        $xml = (new TallyExportService(new TallySyncMappingService))->buildQuotationXml($quote->fresh());

        $this->assertStringContainsString('<VOUCHERTYPENAME>Quotation</VOUCHERTYPENAME>', $xml);
        $this->assertStringContainsString('<ISOPTIONAL>Yes</ISOPTIONAL>', $xml);
        $this->assertStringContainsString('Valid until: 30-Apr-2026', $xml);
        $this->assertStringContainsString('<STOCKITEMNAME>Wave Widget</STOCKITEMNAME>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public function test_goods_receipt_xml_is_receipt_note(): void
    {
        [$company, $customer, $product, $uom] = $this->seedTradingBase(true);

        $branch = Branch::query()->create([
            'company_id' => $company->id,
            'name' => 'GRN Branch',
            'code' => 'GRB',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'GRN Dock',
            'code' => 'GRD',
            'is_active' => true,
        ]);
        $po = PurchaseOrder::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $customer->id,
            'po_no' => 'PO-W5-GRN',
            'po_date' => '2026-04-01',
            'status' => 'approved',
            'subtotal' => 200,
            'tax_amount' => 0,
            'grand_total' => 200,
        ]);

        $inward = PurchaseInward::query()->create([
            'purchase_order_id' => $po->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $customer->id,
            'inward_no' => 'GRN-W5-1',
            'inward_date' => '2026-04-12',
            'supplier_challan_no' => 'CH-99',
            'status' => 'posted',
            'notes' => 'Dock received',
        ]);
        PurchaseInwardItem::query()->create([
            'purchase_inward_id' => $inward->id,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'ordered_qty' => 2,
            'received_qty' => 2,
            'accepted_qty' => 2,
            'unit_cost' => 100,
            'batch_no' => 'B-GRN-1',
        ]);

        $xml = (new TallyExportService(new TallySyncMappingService))->buildGoodsReceiptXml($inward->fresh());

        $this->assertStringContainsString('<VOUCHERTYPENAME>Receipt Note</VOUCHERTYPENAME>', $xml);
        $this->assertStringContainsString('<REFERENCE>PO-W5-GRN</REFERENCE>', $xml);
        $this->assertStringContainsString('<BATCHNAME>B-GRN-1</BATCHNAME>', $xml);
        $this->assertStringContainsString('<GODOWNNAME>'.$warehouse->fresh()->name.'</GODOWNNAME>', $xml);
        $this->assertStringContainsString('Challan: CH-99', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    /**
     * @return array{0: Company, 1: Customer, 2: Product, 3: Uom}
     */
    private function seedTradingBase(bool $asSupplier = false): array
    {
        $company = Company::query()->create([
            'name' => 'Wave3 Co',
            'code' => 'W3C',
            'is_active' => true,
        ]);

        $type = CustomerType::query()->create([
            'name' => 'Dealer',
            'code' => 'DLR'.($asSupplier ? 'S' : 'C'),
            'is_active' => true,
        ]);

        $customer = Customer::query()->create([
            'company_id' => $company->id,
            'customer_type_id' => $type->id,
            'name' => 'Wave Buyer',
            'code' => $asSupplier ? 'SUP-W3' : 'CUS-W3',
            'party_type' => $asSupplier
                ? Customer::PARTY_TYPE_SUNDRY_CREDITORS
                : Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'gstin' => '27AAAAA0000A1Z5',
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $uom = Uom::query()->create([
            'name' => 'Nos',
            'code' => 'NOS',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'company_id' => $company->id,
            'name' => 'Wave Widget',
            'sku' => 'W3-SKU',
            'serial_no' => 'SN-W3-1',
            'base_uom_id' => $uom->id,
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        return [$company, $customer, $product, $uom];
    }
}
