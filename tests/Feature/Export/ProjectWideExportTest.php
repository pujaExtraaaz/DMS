<?php

namespace Tests\Feature\Export;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Crm\Models\Lead;
use App\Domains\Hrms\Models\Department;
use App\Domains\Hrms\Models\Designation;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockAdjustmentItem;
use App\Domains\Inventory\Models\StockTransfer;
use App\Domains\Inventory\Models\StockTransferItem;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\PartyBankAccount;
use App\Domains\Master\Models\PartyContact;
use App\Domains\Master\Models\PartyGemAuthorization;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Domains\Purchasing\Models\ReceiptNote;
use App\Domains\Purchasing\Models\ReceiptNoteItem;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Sales\Models\QuotationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectWideExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected Warehouse $warehouse;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Apex Enterprises Pvt Ltd',
            'code' => 'APEX',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Central Warehouse',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-2027',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->user->assignRole($role);
    }

    public function test_parties_multi_sheet_excel_export_includes_gem_category_and_date_of_issue(): void
    {
        $party = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Omni Solutions Corp',
            'code' => 'CUST-001',
            'party_type' => Customer::PARTY_TYPE_BOTH,
            'tax_number' => '27AABCS1429B1Z',
            'pan_number' => 'AABCS1429B',
            'is_active' => true,
        ]);

        PartyContact::create([
            'customer_id' => $party->id,
            'name' => 'Rajesh Sharma',
            'designation' => 'Procurement Head',
            'phone' => '9876543210',
            'email' => 'rajesh@omni.example',
            'is_primary' => true,
        ]);

        PartyAddress::create([
            'customer_id' => $party->id,
            'address_type' => 'billing',
            'address_line_1' => 'Plot 45, MIDC Industrial Area',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411018',
        ]);

        PartyBankAccount::create([
            'customer_id' => $party->id,
            'bank_name' => 'State Bank of India',
            'account_number' => '123456789012',
            'ifsc_code' => 'SBIN0001234',
            'branch_name' => 'Pune Main',
        ]);

        PartyGemAuthorization::create([
            'customer_id' => $party->id,
            'oem_name' => 'Dell Technologies India',
            'category' => 'Computers & Laptops',
            'date_of_issue' => '2026-02-15',
            'gem_product_registered' => true,
            'authorization_status' => 'authorized',
            'gem_authorization_number' => 'GEM-AUTH-2026-889',
        ]);

        $response = $this->actingAs($this->user)->get(route('masters.customers.index', ['export' => 'xlsx']));
        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_party_');
        file_put_contents($tmpFile, $response->streamedContent());

        $spreadsheet = IOFactory::load($tmpFile);
        $sheetNames = $spreadsheet->getSheetNames();

        $this->assertContains('Parties Master', $sheetNames);
        $this->assertContains('Contacts', $sheetNames);
        $this->assertContains('Addresses', $sheetNames);
        $this->assertContains('Bank Accounts', $sheetNames);
        $this->assertContains('GEM Authorizations', $sheetNames);

        $gemSheet = $spreadsheet->getSheetByName('GEM Authorizations');
        $allCells = [];
        foreach ($gemSheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $val = $cell->getValue();
                if ($val !== null && $val !== '') {
                    $allCells[] = (string) $val;
                }
            }
        }
        $this->assertContains('Category', $allCells);
        $this->assertContains('Date of Issue', $allCells);
        $this->assertContains('Computers & Laptops', $allCells);

        unlink($tmpFile);
    }

    public function test_sales_orders_multi_sheet_excel_export(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Apex Retailers',
            'code' => 'AR-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Laser Printer Pro',
            'sku' => 'LPR-100',
            'serial_no' => 'SN-LPR-100',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 15000,
            'purchase_price' => 12000,
            'is_active' => true,
        ]);

        $order = Order::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'order_no' => 'SO-2026-001',
            'order_date' => '2026-03-01',
            'status' => 'approved',
            'subtotal' => 30000,
            'tax_amount' => 5400,
            'grand_total' => 35400,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 2,
            'unit_price' => 15000,
            'tax_rate' => 18,
            'line_total' => 35400,
        ]);

        $response = $this->actingAs($this->user)->get(route('orders.export', ['format' => 'xlsx']));
        $response->assertOk();

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_order_');
        file_put_contents($tmpFile, $response->streamedContent());

        $spreadsheet = IOFactory::load($tmpFile);
        $this->assertContains('Orders', $spreadsheet->getSheetNames());
        $this->assertContains('Order Items', $spreadsheet->getSheetNames());

        unlink($tmpFile);
    }

    public function test_quotations_multi_sheet_excel_export(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Skyline Infra',
            'code' => 'SKY-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Industrial Router X1',
            'sku' => 'IRX-1',
            'serial_no' => 'SN-IRX-1',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 25000,
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'quotation_no' => 'QTN-2026-001',
            'quotation_date' => '2026-03-05',
            'status' => 'draft',
            'subtotal' => 25000,
            'tax_amount' => 4500,
            'grand_total' => 29500,
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_id' => $product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 1,
            'unit_price' => 25000,
            'tax_rate' => 18,
            'line_total' => 29500,
        ]);

        $response = $this->actingAs($this->user)->get(route('quotations.index', ['export' => 'xlsx']));
        $response->assertOk();

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_qtn_');
        file_put_contents($tmpFile, $response->streamedContent());

        $spreadsheet = IOFactory::load($tmpFile);
        $this->assertContains('Quotations', $spreadsheet->getSheetNames());
        $this->assertContains('Quotation Items', $spreadsheet->getSheetNames());

        unlink($tmpFile);
    }

    public function test_sales_invoices_multi_sheet_excel_export(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Metro Corporation',
            'code' => 'METRO-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Enterprise Switch 24P',
            'sku' => 'SW-24P',
            'serial_no' => 'SN-SW-24P',
            'base_uom_id' => $this->uom->id,
            'selling_price' => 35000,
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-2026-001',
            'invoice_date' => '2026-03-10',
            'status' => 'posted',
            'subtotal' => 35000,
            'taxable_amount' => 35000,
            'tax_amount' => 6300,
            'grand_total' => 41300,
            'paid_amount' => 0,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 1,
            'unit_price' => 35000,
            'tax_rate' => 18,
            'line_total' => 41300,
        ]);

        $response = $this->actingAs($this->user)->get(route('invoices.index', ['export' => 'xlsx']));
        $response->assertOk();

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_inv_');
        file_put_contents($tmpFile, $response->streamedContent());

        $spreadsheet = IOFactory::load($tmpFile);
        $this->assertContains('Sales Invoices', $spreadsheet->getSheetNames());
        $this->assertContains('Invoice Items', $spreadsheet->getSheetNames());

        unlink($tmpFile);
    }

    public function test_purchase_orders_multi_sheet_excel_export(): void
    {
        $supplier = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Prime Hardware Supplies',
            'code' => 'PHS-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'RAM DDR5 32GB',
            'sku' => 'RAM-DDR5-32',
            'serial_no' => 'SN-RAM-32',
            'base_uom_id' => $this->uom->id,
            'purchase_price' => 8000,
            'is_active' => true,
        ]);

        $po = PurchaseOrder::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $supplier->id,
            'po_no' => 'PO-2026-001',
            'po_date' => '2026-03-02',
            'status' => 'approved',
            'subtotal' => 40000,
            'tax_amount' => 7200,
            'grand_total' => 47200,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 5,
            'unit_cost' => 8000,
            'tax_percent' => 18,
            'line_total' => 47200,
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.orders.index', ['export' => 'xlsx']));
        $response->assertOk();

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_po_');
        file_put_contents($tmpFile, $response->streamedContent());

        $spreadsheet = IOFactory::load($tmpFile);
        $this->assertContains('Purchase Orders', $spreadsheet->getSheetNames());
        $this->assertContains('PO Items', $spreadsheet->getSheetNames());

        unlink($tmpFile);
    }

    public function test_receipt_notes_multi_sheet_excel_export(): void
    {
        $supplier = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Apex Parts Supply',
            'code' => 'APS-01',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'SSD NVMe 1TB',
            'sku' => 'SSD-1TB',
            'serial_no' => 'SN-SSD-1TB',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        $rn = ReceiptNote::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $supplier->id,
            'receipt_note_no' => 'RN-2026-001',
            'receipt_date' => '2026-03-08',
            'status' => 'received',
        ]);

        ReceiptNoteItem::create([
            'receipt_note_id' => $rn->id,
            'product_id' => $product->id,
            'uom_id' => $this->uom->id,
            'ordered_qty' => 10,
            'challan_qty' => 10,
            'received_qty' => 10,
        ]);

        $response = $this->actingAs($this->user)->get(route('purchasing.receipt-notes.index', ['export' => 'xlsx']));
        $response->assertOk();

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_rn_');
        file_put_contents($tmpFile, $response->streamedContent());

        $spreadsheet = IOFactory::load($tmpFile);
        $this->assertContains('Receipt Notes', $spreadsheet->getSheetNames());
        $this->assertContains('Receipt Note Items', $spreadsheet->getSheetNames());

        unlink($tmpFile);
    }

    public function test_employees_master_export(): void
    {
        $dept = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Information Technology',
            'code' => 'IT',
            'is_active' => true,
        ]);

        $desig = Designation::create([
            'company_id' => $this->company->id,
            'name' => 'Senior Developer',
            'code' => 'SR-DEV',
            'is_active' => true,
        ]);

        Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'employee_code' => 'EMP-001',
            'name' => 'Arun Kumar',
            'email' => 'arun@example.com',
            'phone' => '9876543211',
            'joining_date' => '2024-01-15',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('hrms.employees.index', ['export' => 'xlsx']));
        $response->assertOk();
    }

    public function test_crm_leads_export(): void
    {
        Lead::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Sunil Verma',
            'company_name' => 'Verma Technologies',
            'email' => 'sunil@verma.example',
            'mobile' => '9876500000',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'status' => 'new',
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($this->user)->get(route('crm.leads.index', ['export' => 'xlsx']));
        $response->assertOk();
    }

    public function test_stock_adjustments_multi_sheet_excel_export(): void
    {
        $product = Product::create([
            'name' => 'Server Chassis 2U',
            'sku' => 'SC-2U',
            'serial_no' => 'SN-SC-2U',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        $adj = StockAdjustment::create([
            'adjustment_no' => 'ADJ-2026-001',
            'adjustment_date' => '2026-03-12',
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'opening',
            'status' => 'posted',
            'notes' => 'Initial opening inventory setup',
            'created_by' => $this->user->id,
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adj->id,
            'product_id' => $product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 15,
            'notes' => 'Rack A1',
        ]);

        $response = $this->actingAs($this->user)->get(route('inventory.adjustments.index', ['export' => 'xlsx']));
        $response->assertOk();

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_adj_');
        file_put_contents($tmpFile, $response->streamedContent());

        $spreadsheet = IOFactory::load($tmpFile);
        $this->assertContains('Stock Adjustments', $spreadsheet->getSheetNames());
        $this->assertContains('Adjustment Items', $spreadsheet->getSheetNames());

        unlink($tmpFile);
    }

    public function test_stock_transfers_multi_sheet_excel_export(): void
    {
        $toWarehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Secondary Warehouse',
            'code' => 'WH-02',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'CAT6 Cable Reel 305m',
            'sku' => 'CAT6-305',
            'serial_no' => 'SN-CAT6-305',
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        $transfer = StockTransfer::create([
            'transfer_no' => 'TRF-2026-001',
            'transfer_date' => '2026-03-14',
            'from_warehouse_id' => $this->warehouse->id,
            'to_warehouse_id' => $toWarehouse->id,
            'status' => 'draft',
            'notes' => 'Inter-branch replenishment',
            'created_by' => $this->user->id,
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $product->id,
            'uom_id' => $this->uom->id,
            'quantity' => 8,
        ]);

        $response = $this->actingAs($this->user)->get(route('inventory.transfers.index', ['tab' => 'location', 'export' => 'xlsx']));
        $response->assertOk();

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_trf_');
        file_put_contents($tmpFile, $response->streamedContent());

        $spreadsheet = IOFactory::load($tmpFile);
        $this->assertContains('Stock Transfers', $spreadsheet->getSheetNames());
        $this->assertContains('Transfer Items', $spreadsheet->getSheetNames());

        unlink($tmpFile);
    }

    public function test_warehouses_and_branches_export(): void
    {
        $responseWh = $this->actingAs($this->user)->get(route('organization.warehouses.index', ['export' => 'xlsx']));
        $responseWh->assertOk();

        $responseBr = $this->actingAs($this->user)->get(route('organization.branches.index', ['export' => 'xlsx']));
        $responseBr->assertOk();
    }
}
