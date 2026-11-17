<?php

namespace Tests\Feature\Inventory;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InTransitStockTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Branch $branch;
    protected Warehouse $warehouseA;
    protected Warehouse $warehouseB;
    protected Uom $uom;
    protected Brand $brand;
    protected Category $category;
    protected Customer $supplier;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::first() ?? User::factory()->create();
        if (! $this->user->hasRole('super-admin')) {
            $this->user->assignRole($role);
        }

        $this->company = Company::create([
            'name' => 'Apex Enterprises',
            'code' => 'APX-01',
            'is_active' => true,
        ]);

        $this->user->update(['company_id' => $this->company->id]);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
        ]);

        $this->warehouseA = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Central Warehouse',
            'code' => 'WH-CEN',
            'is_active' => true,
        ]);

        $this->warehouseB = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Regional Depot',
            'code' => 'WH-REG',
            'is_active' => true,
        ]);

        $this->uom = Uom::create([
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'name' => 'Schneider Electric',
            'code' => 'BR-SCH',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Switchgear',
            'code' => 'CAT-SWG',
            'is_active' => true,
        ]);

        $supplierType = CustomerType::firstOrCreate(
            ['name' => 'Supplier'],
            ['code' => 'SUP']
        );

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $supplierType->id,
            'name' => 'Global Electricals Ltd',
            'code' => 'SUP-GEL',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->uom->id,
            'name' => 'MCB 32A C-Curve 3P',
            'sku' => 'SCH-MCB-32A',
            'serial_no' => 'SN-MCB-001',
            'tax_rate' => 18,
            'is_active' => true,
            'is_hidden' => false,
        ]);
    }

    /**
     * Create a purchase invoice directly for testing.
     */
    protected function createPurchaseInvoice(
        Warehouse $warehouse,
        float $quantity,
        ?Carbon $expectedDeliveryDate = null,
        string $materialReceiptStatus = PurchaseInvoice::MATERIAL_IN_TRANSIT,
        string $status = 'approved',
        float $receivedQty = 0.0
    ): PurchaseInvoice {
        $invoice = PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PI-' . strtoupper(uniqid()),
            'invoice_date' => now()->toDateString(),
            'expected_delivery_date' => $expectedDeliveryDate?->toDateString(),
            'status' => $status,
            'material_receipt_status' => $materialReceiptStatus,
            'subtotal' => $quantity * 100,
            'tax_amount' => ($quantity * 100) * 0.18,
            'total_amount' => ($quantity * 100) * 1.18,
            'created_by' => $this->user->id,
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->uom->id,
            'quantity' => $quantity,
            'unit_cost' => 100,
            'tax_percent' => 18,
            'line_total' => $quantity * 100,
            'received_qty' => $receivedQty,
        ]);

        return $invoice;
    }

    public function test_saved_purchase_invoice_creates_in_transit_stock_across_stock_register_stock_and_products(): void
    {
        $deliveryDate = now()->addDays(5);
        $this->createPurchaseInvoice(
            warehouse: $this->warehouseA,
            quantity: 25.0,
            expectedDeliveryDate: $deliveryDate,
            materialReceiptStatus: PurchaseInvoice::MATERIAL_IN_TRANSIT
        );

        // 1. Stock Register Screen
        $stockRegisterResponse = $this->actingAs($this->user)->get(route('inventory.stock-register.index'));
        $stockRegisterResponse->assertOk();
        $stockRegisterResponse->assertSee('Stock Register');
        $stockRegisterResponse->assertSee('MCB 32A C-Curve 3P');
        $stockRegisterResponse->assertSee('25.00'); // in-transit & total expected
        $stockRegisterResponse->assertSee($deliveryDate->format('d M Y'));

        // 2. Current Stock Screen
        $stockResponse = $this->actingAs($this->user)->get(route('inventory.stock.index'));
        $stockResponse->assertOk();
        $stockResponse->assertSee('MCB 32A C-Curve 3P');
        $stockResponse->assertSee('25.00');
        $stockResponse->assertSee($deliveryDate->format('d M Y'));

        // 3. Products Master Screen
        $productResponse = $this->actingAs($this->user)->get(route('masters.products.index'));
        $productResponse->assertOk();
        $productResponse->assertSee('MCB 32A C-Curve 3P');
        $productResponse->assertSee('25.00');
        $productResponse->assertSee($deliveryDate->format('d M Y'));

        // 4. In-Transit Details AJAX API for Stock Register
        $detailsResponse = $this->actingAs($this->user)->getJson(
            route('inventory.stock-register.in-transit-details', [
                'product' => $this->product->id,
                'warehouse' => $this->warehouseA->id,
            ])
        );
        $detailsResponse->assertOk();
        $detailsResponse->assertJsonStructure([
            'product' => ['id', 'name', 'sku'],
            'warehouse' => ['id', 'name'],
            'invoices' => [
                '*' => [
                    'item_id', 'invoice_id', 'invoice_no', 'supplier_name',
                    'ordered_qty', 'received_qty', 'in_transit_qty',
                    'expected_delivery_date', 'material_receipt_status',
                ],
            ],
            'total_in_transit',
        ]);
        $details = $detailsResponse->json();
        $this->assertEquals(25.0, $details['total_in_transit']);
        $this->assertEquals('Global Electricals Ltd', $details['invoices'][0]['supplier_name']);
        $this->assertEquals(25.0, $details['invoices'][0]['in_transit_qty']);

        // 5. In-Transit Details AJAX API for Product Master
        $prodDetailsResponse = $this->actingAs($this->user)->getJson(
            route('masters.products.in-transit-details', [
                'product' => $this->product->id,
            ])
        );
        $prodDetailsResponse->assertOk();
        $prodDetails = $prodDetailsResponse->json();
        $this->assertEquals(25.0, $prodDetails['total_in_transit']);
    }

    public function test_full_material_receipt_moves_in_transit_stock_to_physical_stock(): void
    {
        $deliveryDate = now()->addDays(3);
        $invoice = $this->createPurchaseInvoice(
            warehouse: $this->warehouseA,
            quantity: 15.0,
            expectedDeliveryDate: $deliveryDate,
            materialReceiptStatus: PurchaseInvoice::MATERIAL_IN_TRANSIT
        );

        // Before receipt: In-transit = 15, Physical = 0
        $responseBefore = $this->actingAs($this->user)->get(route('inventory.stock-register.index'));
        $responseBefore->assertOk();

        // Perform full receipt via domain service
        /** @var PurchaseOrderService $service */
        $service = app(PurchaseOrderService::class);
        $service->receiveMaterial($invoice, [
            'actual_material_receipt_date' => now()->toDateString(),
            'mark_all_received' => true,
        ], $this->user);

        $invoice->refresh();
        $this->assertEquals(PurchaseInvoice::MATERIAL_RECEIVED, $invoice->material_receipt_status);

        // Verify physical stock level
        $stockLevel = StockLevel::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseA->id)
            ->first();
        $this->assertNotNull($stockLevel);
        $this->assertEquals(15.0, (float) $stockLevel->quantity);

        // In-transit stock should now be 0, physical should be 15, total expected should be 15
        $stockRegisterResponse = $this->actingAs($this->user)->get(route('inventory.stock-register.index'));
        $stockRegisterResponse->assertOk();
        $stockRegisterResponse->assertSee('15.00'); // physical
        $stockRegisterResponse->assertSee('0.00');  // in-transit

        // In-transit details API should now return 0 items
        $detailsResponse = $this->actingAs($this->user)->getJson(
            route('inventory.stock-register.in-transit-details', [
                'product' => $this->product->id,
                'warehouse' => $this->warehouseA->id,
            ])
        );
        $detailsResponse->assertOk();
        $this->assertEquals(0, $detailsResponse->json('total_in_transit'));
        $this->assertCount(0, $detailsResponse->json('invoices'));
    }

    public function test_partial_material_receipt_splits_stock_between_physical_and_in_transit(): void
    {
        $deliveryDate = now()->addDays(7);
        $invoice = $this->createPurchaseInvoice(
            warehouse: $this->warehouseA,
            quantity: 20.0,
            expectedDeliveryDate: $deliveryDate,
            materialReceiptStatus: PurchaseInvoice::MATERIAL_IN_TRANSIT
        );

        // Receive 6 units out of 20
        /** @var PurchaseOrderService $service */
        $service = app(PurchaseOrderService::class);
        $service->receiveMaterial($invoice, [
            'actual_material_receipt_date' => now()->toDateString(),
            'items' => [
                [
                    'id' => $invoice->items->first()->id,
                    'receive_qty' => 6.0,
                ],
            ],
        ], $this->user);

        $invoice->refresh();
        $this->assertEquals(PurchaseInvoice::MATERIAL_PARTIALLY_RECEIVED, $invoice->material_receipt_status);

        // Check stock level has physical 6
        $stockLevel = StockLevel::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseA->id)
            ->first();
        $this->assertEquals(6.0, (float) $stockLevel->quantity);

        // Check In-Transit API reflects remaining 14
        $detailsResponse = $this->actingAs($this->user)->getJson(
            route('inventory.stock-register.in-transit-details', [
                'product' => $this->product->id,
                'warehouse' => $this->warehouseA->id,
            ])
        );
        $detailsResponse->assertOk();
        $this->assertEquals(14.0, $detailsResponse->json('total_in_transit'));
        $invoiceData = $detailsResponse->json('invoices')[0];
        $this->assertEquals(20.0, $invoiceData['ordered_qty']);
        $this->assertEquals(6.0, $invoiceData['received_qty']);
        $this->assertEquals(14.0, $invoiceData['in_transit_qty']);
        $this->assertEquals(PurchaseInvoice::MATERIAL_PARTIALLY_RECEIVED, $invoiceData['material_receipt_status']);

        // Check Stock Register renders both physical 6.00 and in-transit 14.00
        $stockRegisterResponse = $this->actingAs($this->user)->get(route('inventory.stock-register.index'));
        $stockRegisterResponse->assertOk();
        $stockRegisterResponse->assertSee('6.00');
        $stockRegisterResponse->assertSee('14.00');
        $stockRegisterResponse->assertSee('20.00'); // total expected
    }

    public function test_cancelled_purchase_invoice_clears_in_transit_stock(): void
    {
        $invoice = $this->createPurchaseInvoice(
            warehouse: $this->warehouseA,
            quantity: 30.0,
            expectedDeliveryDate: now()->addDays(4),
            materialReceiptStatus: PurchaseInvoice::MATERIAL_IN_TRANSIT
        );

        // Cancel the purchase invoice
        $invoice->update(['status' => 'cancelled']);

        $detailsResponse = $this->actingAs($this->user)->getJson(
            route('inventory.stock-register.in-transit-details', [
                'product' => $this->product->id,
                'warehouse' => $this->warehouseA->id,
            ])
        );
        $detailsResponse->assertOk();
        $this->assertEquals(0, $detailsResponse->json('total_in_transit'));
        $this->assertCount(0, $detailsResponse->json('invoices'));
    }

    public function test_multiple_purchase_invoices_and_warehouses_with_different_expected_dates(): void
    {
        // Invoice 1: WH-A, 10 units, arrives in 10 days
        $this->createPurchaseInvoice(
            warehouse: $this->warehouseA,
            quantity: 10.0,
            expectedDeliveryDate: now()->addDays(10),
            materialReceiptStatus: PurchaseInvoice::MATERIAL_IN_TRANSIT
        );

        // Invoice 2: WH-A, 5 units, arrives in 2 days (earliest for WH-A)
        $earliestWhA = now()->addDays(2);
        $this->createPurchaseInvoice(
            warehouse: $this->warehouseA,
            quantity: 5.0,
            expectedDeliveryDate: $earliestWhA,
            materialReceiptStatus: PurchaseInvoice::MATERIAL_IN_TRANSIT
        );

        // Invoice 3: WH-B, 8 units, arrives in 15 days
        $this->createPurchaseInvoice(
            warehouse: $this->warehouseB,
            quantity: 8.0,
            expectedDeliveryDate: now()->addDays(15),
            materialReceiptStatus: PurchaseInvoice::MATERIAL_IN_TRANSIT
        );

        // 1. Warehouse A details: total 15 units across 2 invoices
        $whADetails = $this->actingAs($this->user)->getJson(
            route('inventory.stock-register.in-transit-details', [
                'product' => $this->product->id,
                'warehouse' => $this->warehouseA->id,
            ])
        );
        $whADetails->assertOk();
        $this->assertEquals(15.0, $whADetails->json('total_in_transit'));
        $this->assertCount(2, $whADetails->json('invoices'));
        // Earliest invoice should be first
        $this->assertEquals($earliestWhA->format('d M Y'), $whADetails->json('invoices')[0]['expected_delivery_date']);

        // 2. Warehouse B details: total 8 units in 1 invoice
        $whBDetails = $this->actingAs($this->user)->getJson(
            route('inventory.stock-register.in-transit-details', [
                'product' => $this->product->id,
                'warehouse' => $this->warehouseB->id,
            ])
        );
        $whBDetails->assertOk();
        $this->assertEquals(8.0, $whBDetails->json('total_in_transit'));
        $this->assertCount(1, $whBDetails->json('invoices'));

        // 3. Product Master aggregated details: total 23 units (15 WH-A + 8 WH-B), earliest date 2 days
        $productIndexResponse = $this->actingAs($this->user)->get(route('masters.products.index'));
        $productIndexResponse->assertOk();
        $productIndexResponse->assertSee('23.00'); // total in-transit across all warehouses
        $productIndexResponse->assertSee($earliestWhA->format('d M Y'));
    }

    public function test_stock_register_filters_work_correctly(): void
    {
        // WH-A has 10 units in transit
        $this->createPurchaseInvoice(
            warehouse: $this->warehouseA,
            quantity: 10.0,
            expectedDeliveryDate: now()->addDays(5),
            materialReceiptStatus: PurchaseInvoice::MATERIAL_IN_TRANSIT
        );

        // Filter by warehouse WH-A => visible
        $responseWhA = $this->actingAs($this->user)->get(
            route('inventory.stock-register.index', ['warehouse_id' => $this->warehouseA->id])
        );
        $responseWhA->assertOk();
        $this->assertCount(1, $responseWhA->viewData('stockRecords'));

        // Filter by stock_status=in_transit => visible
        $responseTransit = $this->actingAs($this->user)->get(
            route('inventory.stock-register.index', ['stock_status' => 'in_transit'])
        );
        $responseTransit->assertOk();
        $this->assertCount(1, $responseTransit->viewData('stockRecords'));

        // Filter by stock_status=physical => physical stock is 0, so 0 records
        $responsePhysical = $this->actingAs($this->user)->get(
            route('inventory.stock-register.index', ['stock_status' => 'physical'])
        );
        $responsePhysical->assertOk();
        $this->assertCount(0, $responsePhysical->viewData('stockRecords'));
    }
}
