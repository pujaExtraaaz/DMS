<?php

namespace Tests\Feature\Payment;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Company;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OutstandingPaymentRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['payments.view', 'payments.create', 'payments.edit', 'payments.manage'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(array $permissions = ['payments.view']): User
    {
        $company = Company::firstOrCreate(
            ['code' => 'TESTCO'],
            ['name' => 'Test Company', 'currency' => 'INR', 'is_active' => true]
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        if (! empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    public function test_outstanding_routes_are_both_defined(): void
    {
        $this->assertTrue(Route::has('outstanding.index'), 'Route outstanding.index must be defined');
        $this->assertTrue(Route::has('payments.outstanding'), 'Route payments.outstanding must be defined');
    }

    public function test_outstanding_index_page_renders_successfully_without_route_not_found_exception(): void
    {
        $user = $this->makeUser(['payments.view']);

        $type = \App\Domains\Master\Models\CustomerType::firstOrCreate(
            ['code' => 'GEN'],
            ['name' => 'General', 'is_active' => true]
        );

        $customer = Customer::create([
            'company_id' => $user->company_id,
            'customer_type_id' => $type->id,
            'name' => 'Acme Trading Co',
            'code' => 'CUST-001',
            'is_active' => true,
        ]);

        OutstandingLedger::create([
            'company_id' => $user->company_id,
            'customer_id' => $customer->id,
            'type' => 'invoice',
            'description' => 'Test Invoice Entry',
            'debit' => 1500.00,
            'credit' => 0.00,
            'balance' => 1500.00,
        ]);

        $response = $this->actingAs($user)->get(route('outstanding.index'));

        $response->assertOk();
        $response->assertSee('Outstanding Ledger');
        $response->assertSee('Acme Trading Co');
        $response->assertSee('INVOICE');
        $response->assertSee('Customer Balances');
        $response->assertSee('Ledger Entries');
        // Ensure reset URL is present and matches the route
        $response->assertSee(route('outstanding.index'), false);
    }

    public function test_payments_outstanding_alias_route_renders_successfully(): void
    {
        $user = $this->makeUser(['payments.view']);

        $response = $this->actingAs($user)->get(route('payments.outstanding'));

        $response->assertOk();
        $response->assertSee('Outstanding Ledger');
    }

    public function test_unauthenticated_user_cannot_access_outstanding_page(): void
    {
        $response = $this->get(route('outstanding.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_payment_permission_is_forbidden(): void
    {
        $user = $this->makeUser([]); // no permissions

        $response = $this->actingAs($user)->get(route('outstanding.index'));
        $response->assertStatus(403);
    }
}
