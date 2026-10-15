<?php

namespace Database\Seeders;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
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
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\CreditNoteItem;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Single demo dataset for Avit Digital (DMS company_id scoped).
 * Does not create/reset users or passwords. Skips external users (e.g. rohit@rgddental.com).
 */
class AvitDigitalDemoSeeder extends Seeder
{
    protected Company $company;

    protected Branch $branch;

    protected Warehouse $mainWarehouse;

    protected Warehouse $secondaryWarehouse;

    protected User $salesperson;

    protected User $salesManager;

    protected User $warehouseUser;

    protected User $financeUser;

    /** @var array<string, CustomerType> */
    protected array $customerTypes = [];

    /** @var array<string, Uom> */
    protected array $uoms = [];

    /** @var array<string, Brand> */
    protected array $brands = [];

    /** @var array<string, Category> */
    protected array $categories = [];

    /** @var array<string, Product> */
    protected array $products = [];

    /** @var list<Customer> */
    protected array $customers = [];

    /** @var list<Customer> */
    protected array $suppliers = [];

    protected Area $northArea;

    protected Route $routeA;

    protected Vehicle $vehicle;

    protected Driver $driver;

    protected DeliveryPerson $deliveryPerson;

    public function run(): void
    {
        $this->resolveTenant();
        $this->seedWarehouses();
        $this->seedCustomerTypes();
        $this->seedAreasAndRoutes();
        $this->seedUoms();
        $this->seedTaxRates();
        $this->seedBrandsCategories();
        $this->seedProducts();
        $this->seedPriceMasters();
        $this->seedCustomersAndSuppliers();
        $this->seedLogisticsMasters();
        $this->seedPurchaseWithStock();
        $this->seedOrders();
        $this->seedInvoicesPaymentsLoadSheet();
        $this->seedCreditNotes();
        $this->seedCheques();

        $this->command?->info('Avit Digital demo data seeded (products, brands, warehouses, stock, orders, invoices, credit notes, cheques).');
    }

    protected function resolveTenant(): void
    {
        $company = Company::query()->where('name', 'Avit Digital')->first()
            ?? Company::query()->where('is_default', true)->orderBy('id')->first();
        if (! $company) {
            throw new \RuntimeException('Avit Digital company missing. Run AvitDigitalCompanySeeder first.');
        }

        $this->company = $company;
        $branch = Branch::query()->where('company_id', $company->id)->orderBy('id')->first();
        if (! $branch) {
            throw new \RuntimeException('No branch found for Avit Digital.');
        }
        $this->branch = $branch;

        $this->salesperson = User::query()->where('email', 'salesperson@dms.test')->firstOrFail();
        $this->salesManager = User::query()->where('email', 'salesmanager@dms.test')->firstOrFail();
        $this->warehouseUser = User::query()->where('email', 'warehouse@dms.test')->firstOrFail();
        $this->financeUser = User::query()->where('email', 'finance@dms.test')->firstOrFail();
    }

    protected function seedWarehouses(): void
    {
        $this->mainWarehouse = Warehouse::query()->updateOrCreate(
            ['company_id' => $this->company->id, 'code' => 'WH-LUD'],
            [
                'branch_id' => $this->branch->id,
                'name' => 'Ludhiana Main Godown',
                'address' => 'B-19/463 Sangat Road Civil Lines Ludhiana -141001',
                'is_default' => true,
                'is_active' => true,
            ],
        );

        $this->secondaryWarehouse = Warehouse::query()->updateOrCreate(
            ['company_id' => $this->company->id, 'code' => 'WH-SEC'],
            [
                'branch_id' => $this->branch->id,
                'name' => 'Ludhiana Secondary Store',
                'address' => 'Industrial Area-B, Ludhiana, Punjab',
                'is_default' => false,
                'is_active' => true,
            ],
        );

        Warehouse::query()
            ->where('company_id', $this->company->id)
            ->where('id', '!=', $this->mainWarehouse->id)
            ->update(['is_default' => false]);

        // Keep exactly 2 warehouses for Avit demo (drop legacy WH1 etc.).
        $keep = [$this->mainWarehouse->id, $this->secondaryWarehouse->id];
        Warehouse::query()
            ->where('company_id', $this->company->id)
            ->whereNotIn('id', $keep)
            ->get()
            ->each(function (Warehouse $extra) use ($keep): void {
                // Move any residual stock onto main, then delete the extra row.
                StockLevel::query()->where('warehouse_id', $extra->id)->update(['warehouse_id' => $keep[0]]);
                DB::table('stock_movements')->where('warehouse_id', $extra->id)->update(['warehouse_id' => $keep[0]]);
                try {
                    $extra->delete();
                } catch (\Throwable $e) {
                    $this->command?->warn("Could not delete warehouse {$extra->code}: ".$e->getMessage());
                }
            });
    }

    protected function seedCustomerTypes(): void
    {
        foreach ([
            ['name' => 'Retailer', 'code' => 'RET', 'description' => 'Retail outlets'],
            ['name' => 'Wholesaler', 'code' => 'WHO', 'description' => 'Wholesale distributors'],
            ['name' => 'Company', 'code' => 'COM', 'description' => 'Corporate accounts'],
            ['name' => 'Bulk', 'code' => 'BLK', 'description' => 'Bulk buyers'],
        ] as $type) {
            $this->customerTypes[$type['code']] = CustomerType::query()->updateOrCreate(
                ['code' => $type['code']],
                $type,
            );
        }
    }

    protected function seedAreasAndRoutes(): void
    {
        $this->northArea = Area::query()->updateOrCreate(
            ['code' => 'PB-NORTH'],
            ['name' => 'Punjab North', 'is_active' => true],
        );

        $south = Area::query()->updateOrCreate(
            ['code' => 'PB-SOUTH'],
            ['name' => 'Punjab South', 'is_active' => true],
        );

        $this->routeA = Route::query()->updateOrCreate(
            ['code' => 'LDH-A'],
            ['name' => 'Ludhiana Route A', 'area_id' => $this->northArea->id, 'is_active' => true],
        );

        Route::query()->updateOrCreate(
            ['code' => 'LDH-B'],
            ['name' => 'Ludhiana Route B', 'area_id' => $south->id, 'is_active' => true],
        );
    }

    protected function seedUoms(): void
    {
        foreach ([
            ['name' => 'Piece', 'code' => 'PCS'],
            ['name' => 'Box', 'code' => 'BOX'],
            ['name' => 'Case', 'code' => 'CASE'],
            ['name' => 'Kg', 'code' => 'KG'],
            ['name' => 'Litre', 'code' => 'LTR'],
        ] as $uom) {
            $this->uoms[$uom['code']] = Uom::query()->updateOrCreate(
                ['code' => $uom['code']],
                ['name' => $uom['name'], 'is_active' => true],
            );
        }
    }

    protected function seedTaxRates(): void
    {
        foreach ([
            ['name' => 'GST 5%', 'rate' => 5.00],
            ['name' => 'GST 12%', 'rate' => 12.00],
            ['name' => 'GST 18%', 'rate' => 18.00],
        ] as $rate) {
            TaxRate::query()->updateOrCreate(
                ['name' => $rate['name']],
                ['rate' => $rate['rate'], 'is_active' => true],
            );
        }
    }

    protected function seedBrandsCategories(): void
    {
        $this->brands['avit'] = Brand::query()->updateOrCreate(
            ['company_id' => $this->company->id, 'code' => 'AVIT'],
            ['name' => 'Avit', 'detail' => 'House brand', 'is_active' => true],
        );
        $this->brands['partner'] = Brand::query()->updateOrCreate(
            ['company_id' => $this->company->id, 'code' => 'PRTNR'],
            ['name' => 'Partner Brands', 'detail' => 'Third-party brands', 'is_active' => true],
        );

        $this->categories['it'] = Category::query()->updateOrCreate(
            ['brand_id' => $this->brands['avit']->id, 'code' => 'IT'],
            ['name' => 'IT Peripherals', 'is_active' => true],
        );
        $this->categories['office'] = Category::query()->updateOrCreate(
            ['brand_id' => $this->brands['partner']->id, 'code' => 'OFF'],
            ['name' => 'Office Supplies', 'is_active' => true],
        );

        SubCategory::query()->updateOrCreate(
            ['category_id' => $this->categories['it']->id, 'code' => 'KB'],
            ['name' => 'Keyboards & Mice', 'is_active' => true],
        );
        SubCategory::query()->updateOrCreate(
            ['category_id' => $this->categories['office']->id, 'code' => 'PAPER'],
            ['name' => 'Paper & Stationery', 'is_active' => true],
        );
    }

    protected function seedProducts(): void
    {
        $kbSub = SubCategory::query()->where('code', 'KB')->value('id');
        $paperSub = SubCategory::query()->where('code', 'PAPER')->value('id');

        $definitions = [
            [
                'key' => 'laptop',
                'sku' => 'AV-LAP-15',
                'name' => 'Laptop 15" Business',
                'brand' => 'avit',
                'category' => 'it',
                'sub' => $kbSub,
                'base' => 'PCS',
                'tax' => 18.00,
                'sell' => 48500.00,
                'purchase' => 42000.00,
                'hsn' => '8471',
            ],
            [
                'key' => 'mouse',
                'sku' => 'AV-MSE-01',
                'name' => 'Wireless Mouse',
                'brand' => 'avit',
                'category' => 'it',
                'sub' => $kbSub,
                'base' => 'PCS',
                'tax' => 18.00,
                'sell' => 650.00,
                'purchase' => 380.00,
                'hsn' => '8471',
            ],
            [
                'key' => 'keyboard',
                'sku' => 'AV-KB-01',
                'name' => 'USB Keyboard',
                'brand' => 'avit',
                'category' => 'it',
                'sub' => $kbSub,
                'base' => 'PCS',
                'tax' => 18.00,
                'sell' => 890.00,
                'purchase' => 520.00,
                'hsn' => '8471',
            ],
            [
                'key' => 'a4',
                'sku' => 'AV-A4-500',
                'name' => 'A4 Copier Paper 500 sheets',
                'brand' => 'partner',
                'category' => 'office',
                'sub' => $paperSub,
                'base' => 'BOX',
                'tax' => 12.00,
                'sell' => 320.00,
                'purchase' => 240.00,
                'hsn' => '4802',
            ],
            [
                'key' => 'toner',
                'sku' => 'AV-TON-BK',
                'name' => 'Black Toner Cartridge',
                'brand' => 'partner',
                'category' => 'office',
                'sub' => $paperSub,
                'base' => 'PCS',
                'tax' => 18.00,
                'sell' => 2850.00,
                'purchase' => 2100.00,
                'hsn' => '8443',
            ],
        ];

        foreach ($definitions as $def) {
            $product = Product::query()->updateOrCreate(
                ['company_id' => $this->company->id, 'sku' => $def['sku']],
                [
                    'brand_id' => $this->brands[$def['brand']]->id,
                    'category_id' => $this->categories[$def['category']]->id,
                    'sub_category_id' => $def['sub'],
                    'name' => $def['name'],
                    'serial_no' => 'SN-'.$def['sku'],
                    'description' => 'Avit Digital demo product',
                    'hsn_code' => $def['hsn'],
                    'base_uom_id' => $this->uoms[$def['base']]->id,
                    'tax_rate' => $def['tax'],
                    'selling_price' => $def['sell'],
                    'purchase_price' => $def['purchase'],
                    'trade_price' => round($def['sell'] * 0.92, 2),
                    'min_stock' => 5,
                    'reorder_level' => 10,
                    'is_active' => true,
                ],
            );

            ProductUom::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'uom_id' => $this->uoms[$def['base']]->id,
                ],
                [
                    'conversion_factor' => 1,
                    'is_base' => true,
                ],
            );

            $this->products[$def['key']] = $product;
        }
    }

    protected function seedPriceMasters(): void
    {
        $map = [
            'RET' => ['laptop' => 48500, 'mouse' => 650, 'keyboard' => 890, 'a4' => 320, 'toner' => 2850],
            'WHO' => ['laptop' => 46500, 'mouse' => 590, 'keyboard' => 820, 'a4' => 295, 'toner' => 2650],
            'COM' => ['laptop' => 45500, 'mouse' => 560, 'keyboard' => 790, 'a4' => 285, 'toner' => 2550],
            'BLK' => ['laptop' => 44500, 'mouse' => 520, 'keyboard' => 750, 'a4' => 270, 'toner' => 2450],
        ];

        foreach ($map as $type => $rates) {
            foreach ($rates as $key => $rate) {
                $product = $this->products[$key];
                PriceMaster::query()->updateOrCreate(
                    [
                        'customer_type_id' => $this->customerTypes[$type]->id,
                        'product_id' => $product->id,
                        'uom_id' => $product->base_uom_id,
                        'min_qty' => null,
                    ],
                    ['rate' => $rate],
                );
            }
        }
    }

    protected function seedCustomersAndSuppliers(): void
    {
        $rows = [
            ['name' => 'Ludhiana Tech Retail', 'code' => 'AV-C001', 'type' => 'RET', 'party' => Customer::PARTY_TYPE_SUNDRY_DEBTORS],
            ['name' => 'Punjab IT Wholesalers', 'code' => 'AV-C002', 'type' => 'WHO', 'party' => Customer::PARTY_TYPE_SUNDRY_DEBTORS],
            ['name' => 'City Corporate Solutions', 'code' => 'AV-C003', 'type' => 'COM', 'party' => Customer::PARTY_TYPE_SUNDRY_DEBTORS],
            ['name' => 'Bulk Office Mart', 'code' => 'AV-C004', 'type' => 'BLK', 'party' => Customer::PARTY_TYPE_SUNDRY_DEBTORS],
            ['name' => 'HDFC Vendor Supplies', 'code' => 'AV-S001', 'type' => 'WHO', 'party' => Customer::PARTY_TYPE_SUNDRY_CREDITORS],
            ['name' => 'National Hardware Traders', 'code' => 'AV-S002', 'type' => 'COM', 'party' => Customer::PARTY_TYPE_SUNDRY_CREDITORS],
        ];

        foreach ($rows as $row) {
            $customer = Customer::query()->updateOrCreate(
                ['company_id' => $this->company->id, 'code' => $row['code']],
                [
                    'branch_id' => $this->branch->id,
                    'name' => $row['name'],
                    'customer_type_id' => $this->customerTypes[$row['type']]->id,
                    'area_id' => $this->northArea->id,
                    'route_id' => $this->routeA->id,
                    'salesperson_id' => $this->salesperson->id,
                    'party_type' => $row['party'],
                    'phone' => '98'.random_int(10000000, 99999999),
                    'email' => strtolower(str_replace(' ', '.', $row['code'])).'@demo.avitdigital.in',
                    'address' => 'Ludhiana, Punjab',
                    'state' => 'Punjab',
                    'pincode' => '141001',
                    'gstin' => '03ABCDE'.random_int(1000, 9999).'F1Z5',
                    'credit_limit' => 500000,
                    'credit_days' => 30,
                    'is_active' => true,
                ],
            );

            if ($row['party'] === Customer::PARTY_TYPE_SUNDRY_CREDITORS) {
                $this->suppliers[] = $customer;
            } else {
                $this->customers[] = $customer;
            }
        }
    }

    protected function seedLogisticsMasters(): void
    {
        $this->vehicle = Vehicle::query()->updateOrCreate(
            ['registration_no' => 'PB10AV1234'],
            ['name' => 'Avit Delivery Van', 'type' => 'Van', 'capacity' => 1000, 'is_active' => true],
        );

        $this->driver = Driver::query()->updateOrCreate(
            ['license_no' => 'PB-DL-AVIT-01'],
            ['name' => 'Gurpreet Singh', 'phone' => '9876501101', 'is_active' => true],
        );

        $this->deliveryPerson = DeliveryPerson::query()->updateOrCreate(
            ['phone' => '9876501102'],
            ['name' => 'Harpreet Kaur', 'is_active' => true],
        );
    }

    protected function seedPurchaseWithStock(): void
    {
        $stock = app(StockMovementService::class);

        $purchase = Purchase::query()->updateOrCreate(
            ['purchase_no' => 'AV-PUR-0001'],
            [
                'purchase_date' => now()->subDays(10)->toDateString(),
                'supplier_name' => $this->suppliers[0]->name ?? 'HDFC Vendor Supplies',
                'supplier_party_id' => $this->suppliers[0]->id ?? null,
                'warehouse_id' => $this->mainWarehouse->id,
                'status' => 'posted',
                'grand_total' => 0,
                'created_by' => $this->warehouseUser->id,
            ],
        );

        $lines = [
            ['product' => 'laptop', 'qty' => 20, 'cost' => 42000],
            ['product' => 'mouse', 'qty' => 200, 'cost' => 380],
            ['product' => 'keyboard', 'qty' => 150, 'cost' => 520],
            ['product' => 'a4', 'qty' => 300, 'cost' => 240],
            ['product' => 'toner', 'qty' => 40, 'cost' => 2100],
        ];

        $grand = 0;
        foreach ($lines as $line) {
            $product = $this->products[$line['product']];
            $uom = Uom::query()->findOrFail($product->base_uom_id);
            $total = $line['qty'] * $line['cost'];
            $grand += $total;

            PurchaseItem::query()->updateOrCreate(
                [
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'uom_id' => $uom->id,
                ],
                [
                    'quantity' => $line['qty'],
                    'unit_cost' => $line['cost'],
                    'line_total' => $total,
                ],
            );

            $exists = StockLevel::query()
                ->where('warehouse_id', $this->mainWarehouse->id)
                ->where('product_id', $product->id)
                ->where('uom_id', $uom->id)
                ->exists();

            if (! $exists) {
                $stock->recordIn(
                    $product,
                    $uom,
                    (float) $line['qty'],
                    'purchase',
                    $purchase,
                    'Avit demo opening stock',
                    $this->warehouseUser,
                    $this->mainWarehouse->id,
                    (float) $line['cost'],
                );
            }
        }

        $mouse = $this->products['mouse'];
        $mouseUom = Uom::query()->findOrFail($mouse->base_uom_id);
        if (! StockLevel::query()->where('warehouse_id', $this->secondaryWarehouse->id)->where('product_id', $mouse->id)->exists()) {
            $stock->recordIn(
                $mouse,
                $mouseUom,
                25,
                'purchase',
                $purchase,
                'Secondary store buffer',
                $this->warehouseUser,
                $this->secondaryWarehouse->id,
                380,
            );
        }

        $purchase->update(['grand_total' => $grand]);
    }

    protected function seedOrders(): void
    {
        $defs = [
            [
                'order_no' => 'AV-ORD-1001',
                'customer' => 0,
                'status' => 'pending',
                'items' => [['product' => 'mouse', 'qty' => 10, 'price' => 650]],
            ],
            [
                'order_no' => 'AV-ORD-1002',
                'customer' => 1,
                'status' => 'approved',
                'approved' => true,
                'items' => [['product' => 'keyboard', 'qty' => 20, 'price' => 820]],
            ],
            [
                'order_no' => 'AV-ORD-1003',
                'customer' => 2,
                'status' => 'converted',
                'approved' => true,
                'items' => [
                    ['product' => 'laptop', 'qty' => 2, 'price' => 45500],
                    ['product' => 'mouse', 'qty' => 2, 'price' => 560],
                ],
            ],
            [
                'order_no' => 'AV-ORD-1004',
                'customer' => 3,
                'status' => 'draft',
                'items' => [['product' => 'a4', 'qty' => 50, 'price' => 270]],
            ],
        ];

        foreach ($defs as $def) {
            $customer = $this->customers[$def['customer']];
            $subtotal = collect($def['items'])->sum(fn ($i) => $i['qty'] * $i['price']);
            $tax = round($subtotal * 0.18, 2);

            $order = Order::query()->updateOrCreate(
                ['order_no' => $def['order_no']],
                [
                    'customer_id' => $customer->id,
                    'salesperson_id' => $this->salesperson->id,
                    'order_date' => now()->toDateString(),
                    'status' => $def['status'],
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'tax_amount' => $tax,
                    'grand_total' => $subtotal + $tax,
                    'notes' => 'Avit demo order',
                    'approved_by' => ($def['approved'] ?? false) ? $this->salesManager->id : null,
                    'approved_at' => ($def['approved'] ?? false) ? now()->subHours(2) : null,
                ],
            );

            foreach ($def['items'] as $item) {
                $product = $this->products[$item['product']];
                OrderItem::query()->updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'uom_id' => $product->base_uom_id,
                    ],
                    [
                        'quantity' => $item['qty'],
                        'unit_price' => $item['price'],
                        'line_total' => $item['qty'] * $item['price'],
                    ],
                );
            }
        }
    }

    protected function seedInvoicesPaymentsLoadSheet(): void
    {
        $ledger = app(OutstandingLedgerService::class);
        $converted = Order::query()->where('order_no', 'AV-ORD-1003')->firstOrFail();

        $invoice = Invoice::query()->updateOrCreate(
            ['invoice_no' => 'AV-INV-0001'],
            [
                'customer_id' => $converted->customer_id,
                'order_id' => $converted->id,
                'salesperson_id' => $this->salesperson->id,
                'invoice_date' => now()->toDateString(),
                'status' => 'partial',
                'subtotal' => $converted->subtotal,
                'discount_amount' => 0,
                'tax_amount' => $converted->tax_amount,
                'grand_total' => $converted->grand_total,
                'paid_amount' => 25000,
                'notes' => 'Avit demo tax invoice',
            ],
        );

        foreach ($converted->items as $item) {
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
                    'tax_amount' => round((float) $item->line_total * 0.18, 2),
                    'line_total' => $item->line_total,
                ],
            );
        }

        $ledger->recordInvoice($invoice);

        $payment = Payment::query()->updateOrCreate(
            ['payment_no' => 'AV-PAY-0001'],
            [
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => 25000,
                'method' => 'upi',
                'status' => 'completed',
                'paid_at' => now()->subHours(4),
                'recorded_by' => $this->financeUser->id,
                'notes' => 'Partial UPI to Avit Digital UPI',
            ],
        );
        $ledger->recordPayment($payment);

        $invoiceTwo = Invoice::query()->updateOrCreate(
            ['invoice_no' => 'AV-INV-0002'],
            [
                'customer_id' => $this->customers[0]->id,
                'order_id' => null,
                'salesperson_id' => $this->salesperson->id,
                'invoice_date' => now()->toDateString(),
                'status' => 'issued',
                'subtotal' => 1540,
                'discount_amount' => 0,
                'tax_amount' => 277.20,
                'grand_total' => 1817.20,
                'paid_amount' => 0,
                'notes' => 'Direct bill - mouse & keyboard',
            ],
        );

        InvoiceItem::query()->updateOrCreate(
            ['invoice_id' => $invoiceTwo->id, 'product_id' => $this->products['mouse']->id, 'uom_id' => $this->products['mouse']->base_uom_id],
            ['quantity' => 1, 'unit_price' => 650, 'discount_amount' => 0, 'tax_amount' => 117, 'line_total' => 650],
        );
        InvoiceItem::query()->updateOrCreate(
            ['invoice_id' => $invoiceTwo->id, 'product_id' => $this->products['keyboard']->id, 'uom_id' => $this->products['keyboard']->base_uom_id],
            ['quantity' => 1, 'unit_price' => 890, 'discount_amount' => 0, 'tax_amount' => 160.20, 'line_total' => 890],
        );
        $ledger->recordInvoice($invoiceTwo);

        $loadSheet = LoadSheet::query()->updateOrCreate(
            ['load_sheet_no' => 'AV-LS-0001'],
            [
                'load_date' => now()->toDateString(),
                'route_id' => $this->routeA->id,
                'vehicle_id' => $this->vehicle->id,
                'driver_id' => $this->driver->id,
                'delivery_person_id' => $this->deliveryPerson->id,
                'status' => 'in_transit',
                'total_value' => $invoice->grand_total + $invoiceTwo->grand_total,
                'total_quantity' => 6,
                'created_by' => $this->warehouseUser->id,
            ],
        );

        foreach ([$invoice, $invoiceTwo] as $loaded) {
            LoadSheetItem::query()->updateOrCreate(
                ['load_sheet_id' => $loadSheet->id, 'invoice_id' => $loaded->id],
                ['loaded_quantity' => 2, 'loaded_value' => $loaded->grand_total],
            );

            $delivery = Delivery::query()->updateOrCreate(
                ['load_sheet_id' => $loadSheet->id, 'invoice_id' => $loaded->id],
                [
                    'customer_id' => $loaded->customer_id,
                    'status' => $loaded->is($invoice) ? 'out_for_delivery' : 'pending',
                ],
            );

            foreach ($loaded->items as $item) {
                DeliveryItem::query()->updateOrCreate(
                    [
                        'delivery_id' => $delivery->id,
                        'product_id' => $item->product_id,
                        'uom_id' => $item->uom_id,
                    ],
                    [
                        'loaded_qty' => $item->quantity,
                        'delivered_qty' => $loaded->is($invoice) ? $item->quantity : 0,
                        'short_qty' => 0,
                        'returned_qty' => 0,
                    ],
                );
            }
        }
    }

    protected function seedCreditNotes(): void
    {
        $invoice = Invoice::query()->where('invoice_no', 'AV-INV-0002')->firstOrFail();
        $product = $this->products['mouse'];

        $cn = CreditNote::query()->updateOrCreate(
            ['credit_note_no' => 'AV-CN-0001'],
            [
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'credit_note_date' => now()->toDateString(),
                'reason' => 'return',
                'status' => 'posted',
                'subtotal' => 650,
                'tax_amount' => 117,
                'grand_total' => 767,
                'affects_stock' => true,
                'notes' => 'Avit demo credit note — defective mouse return',
                'created_by_name' => $this->financeUser->name,
                'approved_by' => $this->financeUser->id,
                'approved_by_name' => $this->financeUser->name,
                'approved_at' => now(),
                'posted_at' => now(),
            ],
        );

        CreditNoteItem::query()->updateOrCreate(
            [
                'credit_note_id' => $cn->id,
                'product_id' => $product->id,
                'uom_id' => $product->base_uom_id,
            ],
            [
                'quantity' => 1,
                'unit_price' => 650,
                'tax_amount' => 117,
                'line_total' => 650,
                'description' => 'Returned wireless mouse',
            ],
        );

        app(OutstandingLedgerService::class)->recordCreditNote($cn);
    }

    protected function seedCheques(): void
    {
        $customer = $this->customers[1];
        $invoice = Invoice::query()->where('invoice_no', 'AV-INV-0001')->first();

        $paymentCheque = Payment::query()->updateOrCreate(
            ['payment_no' => 'AV-PAY-CHQ-0001'],
            [
                'invoice_id' => $invoice?->id,
                'customer_id' => $customer->id,
                'amount' => 15000,
                'method' => 'cheque',
                'status' => 'completed',
                'paid_at' => now()->subDay(),
                'recorded_by' => $this->financeUser->id,
                'notes' => 'Cheque collection',
            ],
        );

        Cheque::query()->updateOrCreate(
            ['cheque_no' => 'HDFC-CHQ-77881'],
            [
                'customer_id' => $customer->id,
                'purpose' => 'pdc',
                'direction' => 'received_from_client',
                'amount' => 15000,
                'bank_name' => 'HDFC BANK LTD.',
                'branch_name' => 'Ludhiana Civil Lines',
                'cheque_date' => now()->subDays(2)->toDateString(),
                'deposit_date' => now()->subDay()->toDateString(),
                'clearance_date' => now()->toDateString(),
                'status' => 'cleared',
                'payment_id' => $paymentCheque->id,
                'bounce_count' => 0,
                'notes' => 'Avit demo cleared cheque',
                'recorded_by' => $this->financeUser->id,
            ],
        );

        Cheque::query()->updateOrCreate(
            ['cheque_no' => 'SBI-CHQ-99012'],
            [
                'customer_id' => $this->customers[0]->id,
                'purpose' => 'security',
                'direction' => 'received_from_client',
                'amount' => 5000,
                'bank_name' => 'STATE BANK OF INDIA',
                'branch_name' => 'Ludhiana',
                'cheque_date' => now()->toDateString(),
                'deposit_date' => null,
                'clearance_date' => null,
                'status' => 'pending',
                'payment_id' => null,
                'bounce_count' => 0,
                'notes' => 'Avit demo pending cheque',
                'recorded_by' => $this->financeUser->id,
            ],
        );

        // Outgoing vendor cheque (debit / payable side)
        Cheque::query()->updateOrCreate(
            ['cheque_no' => 'AVIT-PAY-4411'],
            [
                'customer_id' => $this->suppliers[0]->id ?? $this->customers[0]->id,
                'purpose' => 'pdc',
                'direction' => 'issued_to_vendor',
                'amount' => 22000,
                'bank_name' => 'HDFC BANK LTD.',
                'branch_name' => 'Ludhiana Civil Lines',
                'cheque_date' => now()->toDateString(),
                'deposit_date' => null,
                'clearance_date' => null,
                'status' => 'pending',
                'payment_id' => null,
                'bounce_count' => 0,
                'notes' => 'Avit demo outgoing cheque (vendor debit/payment)',
                'recorded_by' => $this->financeUser->id,
            ],
        );
    }
}
