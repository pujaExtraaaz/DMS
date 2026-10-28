<?php

namespace Tests\Feature\Inventory;

use App\Domains\Inventory\Models\ProductSerial;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseInwardItem;
use App\Domains\Purchasing\Services\SerialBatchService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Models\User;
use App\Support\WarrantyPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SerialWarrantyLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_warranty_text_parses_into_months(): void
    {
        $this->assertSame(36, WarrantyPeriod::months('3 years'));
        $this->assertSame(36, WarrantyPeriod::months('3 Years'));
        $this->assertSame(12, WarrantyPeriod::months('1 year'));
        $this->assertSame(6, WarrantyPeriod::months('6 months'));
        $this->assertSame(36, WarrantyPeriod::months('3 Years On-site'));
        $this->assertNull(WarrantyPeriod::months('No Warranty'));
        $this->assertNull(WarrantyPeriod::months(''));
        $this->assertSame('2027-12-23', WarrantyPeriod::expiresOn('2024-12-23', 36)?->toDateString());
        $this->assertSame(['123456'], WarrantyPeriod::split('123456'));
    }

    public function test_sale_on_23_december_2024_shows_under_warranty_then_out_of_warranty_in_2028(): void
    {
        $company = Company::create([
            'name' => 'ElectroTech Industries',
            'code' => 'ETI-WRN',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $uom = Uom::firstOrCreate(['code' => 'PCS'], [
            'company_id' => $company->id,
            'name' => 'Pieces',
            'is_active' => true,
        ]);

        $product = Product::create([
            'company_id' => $company->id,
            'name' => 'Latitude 5440',
            'sku' => 'LAT-5440-WRN',
            'serial_no' => 'LAT-5440-WRN',
            'base_uom_id' => $uom->id,
            'warranty' => '3 Years',
            'warranty_months' => 36,
            'is_active' => true,
        ]);

        $inward = PurchaseInward::create([
            'inward_no' => 'INW-2024-001',
            'inward_date' => '2024-01-10',
            'status' => 'posted',
        ]);

        $inwardItem = PurchaseInwardItem::create([
            'purchase_inward_id' => $inward->id,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'received_qty' => 1,
            'accepted_qty' => 1,
            'unit_cost' => 1000,
        ]);

        $service = app(SerialBatchService::class);
        $received = $service->assignSerials($product, ['123456'], null, $inwardItem);
        $serial = $received[0]->fresh();

        $this->assertSame('2024-01-10', $serial->warranty_start_on?->toDateString());
        $this->assertSame('2027-01-10', $serial->warranty_expires_on?->toDateString());
        $this->assertNull($serial->customer_id);
        $this->assertNull($serial->sales_invoice_id);
        $this->assertSame(PurchaseInwardItem::class, $serial->source_type);

        $customerType = CustomerType::create([
            'name' => 'General',
            'code' => 'GEN-WRN',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => $company->id,
            'customer_type_id' => $customerType->id,
            'name' => 'ABC Company',
            'code' => 'ABC-CO',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'invoice_no' => 'INV-23122024',
            'customer_id' => $customer->id,
            'invoice_date' => '2024-12-23',
            'status' => 'issued',
            'subtotal' => 1000,
            'grand_total' => 1000,
        ]);

        $line = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'uom_id' => $uom->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'line_total' => 1000,
            'serial_numbers' => ['123456'],
        ]);

        $service->sellSerials(['123456'], $invoice, $product, $line);

        $serial = ProductSerial::query()->where('serial_number', '123456')->firstOrFail();
        $this->assertSame($customer->id, $serial->customer_id);
        $this->assertSame($invoice->id, $serial->sales_invoice_id);
        $this->assertSame('2024-12-23', $serial->warranty_start_on?->toDateString());
        $this->assertSame('2027-12-23', $serial->warranty_expires_on?->toDateString());
        $this->assertSame('3 Years', $serial->warranty_label);
        $this->assertSame(PurchaseInwardItem::class, $serial->source_type);
        $this->assertSame(['123456'], $line->fresh()->serial_numbers);

        Carbon::setTestNow('2026-06-01');
        $under = $this->actingAs($user)->get(route('inventory.serials.index', ['serial_number' => '123456']));
        $under->assertOk();
        $under->assertSee($product->name);
        $under->assertSee('123456');
        $under->assertSee($customer->fresh()->name);
        $under->assertSee('INV-23122024');
        $under->assertSee('23-12-2024');
        $under->assertSee('23-12-2027');
        $under->assertSee('3 Years');
        $under->assertSee('Under Warranty');
        $under->assertDontSee('10-01-2024');

        Carbon::setTestNow('2028-06-01');
        $expired = $this->actingAs($user)->get(route('inventory.serials.index', ['serial_number' => '123456']));
        $expired->assertOk();
        $expired->assertSee('INV-23122024');
        $expired->assertSee('23-12-2024');
        $expired->assertSee($customer->fresh()->name);
        $expired->assertSee('Out of Warranty');
        $expired->assertDontSee('Under Warranty');
    }

    public function test_serial_first_entered_on_the_sale_is_created_with_the_invoice_warranty(): void
    {
        $company = Company::create([
            'name' => 'ElectroTech Industries',
            'code' => 'ETI-WRN-2',
            'is_active' => true,
        ]);

        $uom = Uom::firstOrCreate(['code' => 'PCS'], [
            'company_id' => $company->id,
            'name' => 'Pieces',
            'is_active' => true,
        ]);

        $product = Product::create([
            'company_id' => $company->id,
            'name' => 'Docking Station',
            'sku' => 'DOCK-WRN',
            'serial_no' => 'DOCK-WRN',
            'base_uom_id' => $uom->id,
            'warranty' => '1 Year',
            'warranty_months' => 12,
            'is_active' => true,
        ]);

        $customerType = CustomerType::create([
            'name' => 'General',
            'code' => 'GEN-WRN-2',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => $company->id,
            'customer_type_id' => $customerType->id,
            'name' => 'ABC Company',
            'code' => 'ABC-CO-2',
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'invoice_no' => 'INV-SALE-ONLY',
            'customer_id' => $customer->id,
            'invoice_date' => '2024-12-23',
            'status' => 'issued',
        ]);

        app(SerialBatchService::class)->sellSerials(['998877'], $invoice, $product);

        $serial = ProductSerial::query()->where('serial_number', '998877')->firstOrFail();
        $this->assertSame('delivered', $serial->status);
        $this->assertSame($invoice->id, $serial->sales_invoice_id);
        $this->assertSame('2024-12-23', $serial->warranty_start_on?->toDateString());
        $this->assertSame('2025-12-23', $serial->warranty_expires_on?->toDateString());
        $this->assertSame(Invoice::class, $serial->source_type);
    }
}
