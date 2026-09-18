<?php

namespace Tests\Feature;

use App\Support\ScreenHelp;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ClientProductSmokeTest extends TestCase
{
    public function test_screen_help_resolves_for_key_modules(): void
    {
        foreach ([
            'dashboard',
            'organization.companies.index',
            'masters.products.index',
            'purchasing.orders.index',
            'inventory.serials.index',
            'orders.index',
            'invoices.index',
            'payments.index',
            'cheques.index',
            'reports.pending-orders',
            'hrms.employees.index',
            'crm.leads.index',
            'tally.queue.index',
            'unknown.route.name',
        ] as $route) {
            $help = ScreenHelp::forRoute($route);
            $this->assertIsArray($help, "Missing help for {$route}");
            $this->assertNotEmpty($help['title'] ?? null);
            $this->assertNotEmpty($help['summary'] ?? null);
            $this->assertNotEmpty($help['steps'] ?? []);
        }
    }

    public function test_core_srs_routes_remain_registered(): void
    {
        foreach ([
            'dashboard',
            'organization.companies.index',
            'masters.products.index',
            'purchasing.orders.index',
            'inventory.serials.index',
            'inventory.valuation.index',
            'orders.index',
            'invoices.index',
            'payments.index',
            'cheques.index',
            'reports.pending-orders',
            'reports.margin',
            'hrms.employees.index',
            'crm.leads.index',
            'tally.queue.index',
        ] as $name) {
            $this->assertTrue(Route::has($name), "Missing route: {$name}");
        }
    }
}
