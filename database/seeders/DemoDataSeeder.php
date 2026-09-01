<?php

namespace Database\Seeders;

use App\Domains\Delivery\Models\Delivery;
use App\Domains\Delivery\Models\DeliveryItem;
use App\Domains\Inventory\Models\Purchase;
use App\Domains\Inventory\Models\PurchaseItem;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Logistics\Models\LoadSheet;
use App\Domains\Logistics\Models\LoadSheetItem;
use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\DeliveryPerson;
use App\Domains\Master\Models\Driver;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Route;
use App\Domains\Master\Models\TaxRate;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Models\Vehicle;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    protected User $owner;

    protected User $superAdmin;

    protected User $salesManager;

    protected User $salesperson;

    protected User $warehouseUser;

    protected User $financeUser;

    protected User $driverUser;

    protected User $deliveryPersonUser;

    /** @var array<string, CustomerType> */
    protected array $customerTypes = [];

    /** @var array<string, Uom> */
    protected array $uoms = [];

    /** @var array<string, Product> */
    protected array $products = [];

    /** @var list<Customer> */
    protected array $customers = [];

    protected Area $northArea;

    protected Route $routeA;

    protected Vehicle $vehicle;

    protected Driver $driver;

    protected DeliveryPerson $deliveryPerson;

    public function run(): void
    {
        $this->seedUsers();
        $this->seedCustomerTypes();
        $this->seedAreasAndRoutes();
        $this->seedUoms();
        $this->seedProducts();
        $this->seedPriceMasters();
        $this->seedCustomers();
        $this->seedLogisticsMasters();
        $this->seedTaxRates();
        $this->seedPurchaseWithStock();
        $this->seedOrders();
        $this->seedInvoicePaymentAndLoadSheet();
        $this->seedLowStockAlert();
    }

    protected function seedUsers(): void
    {
        $users = [
            ['name' => 'Owner User', 'email' => 'owner@dms.test', 'role' => 'owner'],
            ['name' => 'Super Admin', 'email' => 'superadmin@dms.test', 'role' => 'super-admin'],
            ['name' => 'Sales Manager', 'email' => 'salesmanager@dms.test', 'role' => 'sales-manager'],
            ['name' => 'Sales Person', 'email' => 'salesperson@dms.test', 'role' => 'salesperson'],
            ['name' => 'Warehouse Manager', 'email' => 'warehouse@dms.test', 'role' => 'warehouse'],
            ['name' => 'Finance Manager', 'email' => 'finance@dms.test', 'role' => 'finance'],
            ['name' => 'Driver One', 'email' => 'driver@dms.test', 'role' => 'driver'],
            ['name' => 'Delivery Person', 'email' => 'delivery@dms.test', 'role' => 'delivery-person'],
        ];

        foreach ($users as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$data['role']]);

            match ($data['role']) {
                'owner' => $this->owner = $user,
                'super-admin' => $this->superAdmin = $user,
                'sales-manager' => $this->salesManager = $user,
                'salesperson' => $this->salesperson = $user,
                'warehouse' => $this->warehouseUser = $user,
                'finance' => $this->financeUser = $user,
                'driver' => $this->driverUser = $user,
                'delivery-person' => $this->deliveryPersonUser = $user,
                default => null,
            };
        }
    }

    protected function seedCustomerTypes(): void
    {
        $types = [
            ['name' => 'Retailer', 'code' => 'RET', 'description' => 'Small retail outlets'],
            ['name' => 'Wholesaler', 'code' => 'WHO', 'description' => 'Wholesale distributors'],
            ['name' => 'Company', 'code' => 'COM', 'description' => 'Corporate accounts'],
            ['name' => 'Bulk', 'code' => 'BLK', 'description' => 'Bulk buyers with tier pricing'],
        ];

        foreach ($types as $type) {
            $this->customerTypes[$type['code']] = CustomerType::query()->updateOrCreate(
                ['code' => $type['code']],
                $type,
            );
        }
    }

    protected function seedAreasAndRoutes(): void
    {
        $this->northArea = Area::query()->updateOrCreate(
            ['code' => 'NORTH'],
            ['name' => 'North Zone', 'is_active' => true],
        );

        $southArea = Area::query()->updateOrCreate(
            ['code' => 'SOUTH'],
            ['name' => 'South Zone', 'is_active' => true],
        );

        $this->routeA = Route::query()->updateOrCreate(
            ['code' => 'RT-A'],
            ['name' => 'Route A - North', 'area_id' => $this->northArea->id, 'is_active' => true],
        );

        Route::query()->updateOrCreate(
            ['code' => 'RT-B'],
            ['name' => 'Route B - South', 'area_id' => $southArea->id, 'is_active' => true],
        );
    }

    protected function seedUoms(): void
    {
        $uoms = [
            ['name' => 'Box', 'code' => 'BOX'],
            ['name' => 'Piece', 'code' => 'PCS'],
            ['name' => 'Case', 'code' => 'CASE'],
            ['name' => 'Kg', 'code' => 'KG'],
            ['name' => 'Litre', 'code' => 'LTR'],
            ['name' => 'Quintal', 'code' => 'QTL'],
            ['name' => 'Gram', 'code' => 'GM'],
        ];

        foreach ($uoms as $uom) {
            $this->uoms[$uom['code']] = Uom::query()->updateOrCreate(
                ['code' => $uom['code']],
                ['name' => $uom['name'], 'is_active' => true],
            );
        }
    }

    protected function seedProducts(): void
    {
        $definitions = [
            [
                'key' => 'rice',
                'name' => 'Premium Basmati Rice 25kg',
                'sku' => 'RICE-25KG',
                'base_uom' => 'KG',
                'tax_rate' => 5.00,
                'uoms' => [
                    ['code' => 'KG', 'factor' => 1, 'is_base' => true],
                    ['code' => 'CASE', 'factor' => 25, 'is_base' => false],
                ],
            ],
            [
                'key' => 'oil',
                'name' => 'Sunflower Cooking Oil 1L',
                'sku' => 'OIL-1L',
                'base_uom' => 'LTR',
                'tax_rate' => 12.00,
                'uoms' => [
                    ['code' => 'LTR', 'factor' => 1, 'is_base' => true],
                    ['code' => 'CASE', 'factor' => 12, 'is_base' => false],
                ],
            ],
            [
                'key' => 'biscuits',
                'name' => 'Assorted Biscuits',
                'sku' => 'BISC-MIX',
                'base_uom' => 'BOX',
                'tax_rate' => 18.00,
                'uoms' => [
                    ['code' => 'BOX', 'factor' => 1, 'is_base' => true],
                    ['code' => 'PCS', 'factor' => 0.1, 'is_base' => false],
                    ['code' => 'CASE', 'factor' => 10, 'is_base' => false],
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            $product = Product::query()->updateOrCreate(
                ['sku' => $definition['sku']],
                [
                    'name' => $definition['name'],
                    'description' => 'Demo product for DMS walkthrough',
                    'base_uom_id' => $this->uoms[$definition['base_uom']]->id,
                    'tax_rate' => $definition['tax_rate'],
                    'is_active' => true,
                ],
            );

            foreach ($definition['uoms'] as $uomRow) {
                ProductUom::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'uom_id' => $this->uoms[$uomRow['code']]->id,
                    ],
                    [
                        'conversion_factor' => $uomRow['factor'],
                        'is_base' => $uomRow['is_base'],
                    ],
                );
            }

            $this->products[$definition['key']] = $product;
        }
    }

    protected function seedPriceMasters(): void
    {
        $rates = [
            'RET' => ['rice' => 52.00, 'oil' => 145.00, 'biscuits' => 120.00],
            'WHO' => ['rice' => 48.50, 'oil' => 132.00, 'biscuits' => 105.00],
            'COM' => ['rice' => 47.00, 'oil' => 128.00, 'biscuits' => 100.00],
            'BLK' => ['rice' => 45.00, 'oil' => 122.00, 'biscuits' => 95.00],
        ];

        $baseUoms = [
            'rice' => 'KG',
            'oil' => 'LTR',
            'biscuits' => 'BOX',
        ];

        foreach ($rates as $typeCode => $productRates) {
            foreach ($productRates as $productKey => $rate) {
                PriceMaster::query()->updateOrCreate(
                    [
                        'customer_type_id' => $this->customerTypes[$typeCode]->id,
                        'product_id' => $this->products[$productKey]->id,
                        'uom_id' => $this->uoms[$baseUoms[$productKey]]->id,
                        'min_qty' => null,
                    ],
                    ['rate' => $rate],
                );
            }

            if ($typeCode === 'BLK') {
                PriceMaster::query()->updateOrCreate(
                    [
                        'customer_type_id' => $this->customerTypes['BLK']->id,
                        'product_id' => $this->products['rice']->id,
                        'uom_id' => $this->uoms['KG']->id,
                        'min_qty' => 500,
                    ],
                    ['rate' => 42.00],
                );
            }
        }
    }

    protected function seedCustomers(): void
    {
        $rows = [
            ['name' => 'Metro Retail Mart', 'code' => 'CUST-001', 'type' => 'RET', 'route' => 'RT-A'],
            ['name' => 'Green Valley Wholesalers', 'code' => 'CUST-002', 'type' => 'WHO', 'route' => 'RT-A'],
            ['name' => 'City Stores Pvt Ltd', 'code' => 'CUST-003', 'type' => 'COM', 'route' => 'RT-B'],
            ['name' => 'Bulk Foods Depot', 'code' => 'CUST-004', 'type' => 'BLK', 'route' => 'RT-A'],
            ['name' => 'Sunrise Kirana', 'code' => 'CUST-005', 'type' => 'RET', 'route' => 'RT-B'],
            ['name' => 'Prime Distributors', 'code' => 'CUST-006', 'type' => 'WHO', 'route' => 'RT-A'],
        ];

        foreach ($rows as $row) {
            $this->customers[] = Customer::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'customer_type_id' => $this->customerTypes[$row['type']]->id,
                    'area_id' => $this->northArea->id,
                    'route_id' => Route::query()->where('code', $row['route'])->value('id'),
                    'salesperson_id' => $this->salesperson->id,
                    'phone' => '98765'.random_int(10000, 99999),
                    'email' => strtolower(str_replace(' ', '.', $row['name'])).'@example.com',
                    'address' => 'Demo address, Distribution city',
                    'gstin' => '29ABCDE1234F1Z5',
                    'is_active' => true,
                ],
            );
        }
    }

    protected function seedLogisticsMasters(): void
    {
        $this->vehicle = Vehicle::query()->updateOrCreate(
            ['registration_no' => 'KA01AB1234'],
            ['name' => 'Tata Ace', 'type' => 'Mini Truck', 'capacity' => 750, 'is_active' => true],
        );

        $this->driver = Driver::query()->updateOrCreate(
            ['license_no' => 'DL-IND-001'],
            ['name' => 'Ravi Kumar', 'phone' => '9876500001', 'is_active' => true],
        );

        $this->deliveryPerson = DeliveryPerson::query()->updateOrCreate(
            ['phone' => '9876500002'],
            ['name' => 'Suresh Nair', 'is_active' => true],
        );
    }

    protected function seedTaxRates(): void
    {
        $rates = [
            ['name' => 'GST 5%', 'rate' => 5.00],
            ['name' => 'GST 12%', 'rate' => 12.00],
            ['name' => 'GST 18%', 'rate' => 18.00],
        ];

        foreach ($rates as $rate) {
            TaxRate::query()->updateOrCreate(
                ['name' => $rate['name']],
                ['rate' => $rate['rate'], 'is_active' => true],
            );
        }
    }

    protected function seedPurchaseWithStock(): void
    {
        $stockService = app(StockMovementService::class);

        $purchase = Purchase::query()->updateOrCreate(
            ['purchase_no' => 'PUR-0001'],
            [
                'purchase_date' => now()->subDays(7)->toDateString(),
                'supplier_name' => 'National Foods Supplier',
                'status' => 'posted',
                'grand_total' => 0,
                'created_by' => $this->warehouseUser->id,
            ],
        );

        $lines = [
            ['product' => 'rice', 'uom' => 'KG', 'qty' => 500, 'cost' => 38.00],
            ['product' => 'oil', 'uom' => 'LTR', 'qty' => 240, 'cost' => 98.00],
            ['product' => 'biscuits', 'uom' => 'BOX', 'qty' => 120, 'cost' => 78.00],
        ];

        $grandTotal = 0;

        foreach ($lines as $line) {
            $product = $this->products[$line['product']];
            $uom = $this->uoms[$line['uom']];
            $lineTotal = $line['qty'] * $line['cost'];
            $grandTotal += $lineTotal;

            PurchaseItem::query()->updateOrCreate(
                [
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'uom_id' => $uom->id,
                ],
                [
                    'quantity' => $line['qty'],
                    'unit_cost' => $line['cost'],
                    'line_total' => $lineTotal,
                ],
            );

            if (! StockLevel::query()->where('product_id', $product->id)->where('uom_id', $uom->id)->exists()) {
                $stockService->recordIn(
                    $product,
                    $uom,
                    (float) $line['qty'],
                    'purchase',
                    $purchase,
                    'Initial demo stock purchase',
                    $this->warehouseUser,
                );
            }
        }

        $purchase->update(['grand_total' => $grandTotal]);
    }

    protected function seedOrders(): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $definitions = [
            [
                'order_no' => 'ORD-1001',
                'customer' => 0,
                'date' => $today,
                'status' => 'pending',
                'items' => [['product' => 'rice', 'uom' => 'KG', 'qty' => 50, 'price' => 52.00]],
            ],
            [
                'order_no' => 'ORD-1002',
                'customer' => 1,
                'date' => $today,
                'status' => 'approved',
                'items' => [['product' => 'oil', 'uom' => 'LTR', 'qty' => 24, 'price' => 132.00]],
                'approved_by' => true,
            ],
            [
                'order_no' => 'ORD-1003',
                'customer' => 2,
                'date' => $yesterday,
                'status' => 'converted',
                'items' => [['product' => 'biscuits', 'uom' => 'BOX', 'qty' => 10, 'price' => 100.00]],
                'approved_by' => true,
            ],
            [
                'order_no' => 'ORD-1004',
                'customer' => 3,
                'date' => $today,
                'status' => 'draft',
                'items' => [['product' => 'rice', 'uom' => 'KG', 'qty' => 200, 'price' => 45.00]],
            ],
            [
                'order_no' => 'ORD-1005',
                'customer' => 4,
                'date' => $yesterday,
                'status' => 'cancelled',
                'items' => [['product' => 'oil', 'uom' => 'LTR', 'qty' => 12, 'price' => 145.00]],
            ],
        ];

        foreach ($definitions as $definition) {
            $customer = $this->customers[$definition['customer']];
            $subtotal = collect($definition['items'])->sum(fn (array $item) => $item['qty'] * $item['price']);
            $taxAmount = round($subtotal * 0.05, 2);

            $order = Order::query()->updateOrCreate(
                ['order_no' => $definition['order_no']],
                [
                    'customer_id' => $customer->id,
                    'salesperson_id' => $this->salesperson->id,
                    'order_date' => $definition['date'],
                    'status' => $definition['status'],
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'tax_amount' => $taxAmount,
                    'grand_total' => $subtotal + $taxAmount,
                    'notes' => 'Demo order',
                    'approved_by' => ($definition['approved_by'] ?? false) ? $this->salesManager->id : null,
                    'approved_at' => ($definition['approved_by'] ?? false) ? now()->subHours(2) : null,
                ],
            );

            foreach ($definition['items'] as $item) {
                $product = $this->products[$item['product']];
                $uom = $this->uoms[$item['uom']];
                $lineTotal = $item['qty'] * $item['price'];

                OrderItem::query()->updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'uom_id' => $uom->id,
                    ],
                    [
                        'quantity' => $item['qty'],
                        'unit_price' => $item['price'],
                        'line_total' => $lineTotal,
                    ],
                );
            }
        }
    }

    protected function seedInvoicePaymentAndLoadSheet(): void
    {
        $ledgerService = app(OutstandingLedgerService::class);
        $convertedOrder = Order::query()->where('order_no', 'ORD-1003')->firstOrFail();
        $customer = $convertedOrder->customer;

        $invoice = Invoice::query()->updateOrCreate(
            ['invoice_no' => 'INV-0001'],
            [
                'customer_id' => $customer->id,
                'order_id' => $convertedOrder->id,
                'salesperson_id' => $this->salesperson->id,
                'invoice_date' => now()->toDateString(),
                'status' => 'partial',
                'subtotal' => $convertedOrder->subtotal,
                'discount_amount' => $convertedOrder->discount_amount,
                'tax_amount' => $convertedOrder->tax_amount,
                'grand_total' => $convertedOrder->grand_total,
                'paid_amount' => 500.00,
                'notes' => 'Demo invoice from converted order',
            ],
        );

        foreach ($convertedOrder->items as $item) {
            InvoiceItem::query()->updateOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'uom_id' => $item->uom_id,
                ],
                [
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => 0,
                    'tax_amount' => round((float) $item->line_total * 0.05, 2),
                    'line_total' => $item->line_total,
                ],
            );
        }

        $ledgerService->recordInvoice($invoice);

        $payment = Payment::query()->updateOrCreate(
            ['payment_no' => 'PAY-0001'],
            [
                'invoice_id' => $invoice->id,
                'customer_id' => $customer->id,
                'amount' => 500.00,
                'method' => 'upi',
                'status' => 'completed',
                'paid_at' => now()->subHours(3),
                'recorded_by' => $this->financeUser->id,
                'notes' => 'Partial UPI collection',
            ],
        );

        $ledgerService->recordPayment($payment);

        $approvedOrder = Order::query()->where('order_no', 'ORD-1002')->firstOrFail();
        $approvedCustomer = $approvedOrder->customer;

        $invoiceTwo = Invoice::query()->updateOrCreate(
            ['invoice_no' => 'INV-0002'],
            [
                'customer_id' => $approvedCustomer->id,
                'order_id' => null,
                'salesperson_id' => $this->salesperson->id,
                'invoice_date' => now()->toDateString(),
                'status' => 'issued',
                'subtotal' => 3168.00,
                'discount_amount' => 0,
                'tax_amount' => 380.16,
                'grand_total' => 3548.16,
                'paid_amount' => 0,
                'notes' => 'Direct billing demo invoice',
            ],
        );

        InvoiceItem::query()->updateOrCreate(
            [
                'invoice_id' => $invoiceTwo->id,
                'product_id' => $this->products['oil']->id,
                'uom_id' => $this->uoms['LTR']->id,
            ],
            [
                'quantity' => 24,
                'unit_price' => 132.00,
                'discount_amount' => 0,
                'tax_amount' => 380.16,
                'line_total' => 3168.00,
            ],
        );

        $ledgerService->recordInvoice($invoiceTwo);

        $loadSheet = LoadSheet::query()->updateOrCreate(
            ['load_sheet_no' => 'LS-0001'],
            [
                'load_date' => now()->toDateString(),
                'route_id' => $this->routeA->id,
                'vehicle_id' => $this->vehicle->id,
                'driver_id' => $this->driver->id,
                'delivery_person_id' => $this->deliveryPerson->id,
                'status' => 'in_transit',
                'total_value' => $invoice->grand_total + $invoiceTwo->grand_total,
                'total_quantity' => 34,
                'created_by' => $this->warehouseUser->id,
            ],
        );

        foreach ([$invoice, $invoiceTwo] as $loadedInvoice) {
            LoadSheetItem::query()->updateOrCreate(
                [
                    'load_sheet_id' => $loadSheet->id,
                    'invoice_id' => $loadedInvoice->id,
                ],
                [
                    'loaded_quantity' => 10,
                    'loaded_value' => $loadedInvoice->grand_total,
                ],
            );

            $delivery = Delivery::query()->updateOrCreate(
                [
                    'load_sheet_id' => $loadSheet->id,
                    'invoice_id' => $loadedInvoice->id,
                ],
                [
                    'customer_id' => $loadedInvoice->customer_id,
                    'status' => $loadedInvoice->is($invoice) ? 'out_for_delivery' : 'pending',
                ],
            );

            foreach ($loadedInvoice->items as $item) {
                DeliveryItem::query()->updateOrCreate(
                    [
                        'delivery_id' => $delivery->id,
                        'product_id' => $item->product_id,
                        'uom_id' => $item->uom_id,
                    ],
                    [
                        'loaded_qty' => $item->quantity,
                        'delivered_qty' => $loadedInvoice->is($invoice) ? $item->quantity : 0,
                        'short_qty' => 0,
                        'returned_qty' => 0,
                    ],
                );
            }
        }
    }

    protected function seedLowStockAlert(): void
    {
        StockLevel::query()->updateOrCreate(
            [
                'product_id' => $this->products['biscuits']->id,
                'uom_id' => $this->uoms['BOX']->id,
            ],
            ['quantity' => 8],
        );
    }
}
