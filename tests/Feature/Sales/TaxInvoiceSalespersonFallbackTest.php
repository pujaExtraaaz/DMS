<?php

namespace Tests\Feature\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Order\Models\Order;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Sales\InvoiceController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxInvoiceSalespersonFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_salesperson_prefers_form_then_order_then_customer(): void
    {
        $formSp = User::factory()->create(['name' => 'Form SP']);
        $orderSp = User::factory()->create(['name' => 'Order SP']);
        $customerSp = User::factory()->create(['name' => 'Customer SP']);

        $customer = new Customer(['salesperson_id' => $customerSp->id]);
        $order = new Order(['salesperson_id' => $orderSp->id]);
        $existing = new Invoice(['salesperson_id' => $customerSp->id]);

        $this->assertSame($formSp->id, InvoiceController::resolveSalespersonId(
            ['salesperson_id' => $formSp->id],
            $order,
            $customer
        ));

        $this->assertSame($orderSp->id, InvoiceController::resolveSalespersonId(
            ['salesperson_id' => ''],
            $order,
            $customer
        ));

        $this->assertSame($customerSp->id, InvoiceController::resolveSalespersonId(
            ['salesperson_id' => ''],
            null,
            $customer
        ));

        $this->assertNull(InvoiceController::resolveSalespersonId(
            ['salesperson_id' => ''],
            null,
            new Customer(['salesperson_id' => null]),
            $existing
        ));
    }
}
