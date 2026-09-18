<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SrsAcceptanceSmokeTest extends TestCase
{
    public function test_phase_migration_files_exist(): void
    {
        $migrations = [
            '2026_09_08_100000_phase1_organization_catalog_party.php',
            '2026_09_08_110000_phase2_purchase_inventory.php',
            '2026_09_08_120000_phase3_sales_hybrid.php',
            '2026_09_08_130000_phase4_receivables.php',
            '2026_09_08_140000_phase5_deal_expenses.php',
            '2026_09_08_150000_phase6_targets_schemes.php',
            '2026_09_08_160000_phase7_interest.php',
            '2026_09_08_170000_phase9_hrms.php',
            '2026_09_08_180000_phase10_meta_crm.php',
            '2026_09_08_190000_phase11_tally.php',
            '2026_09_08_200000_harden_partial_gaps.php',
        ];

        foreach ($migrations as $file) {
            $this->assertTrue(
                File::exists(database_path('migrations/'.$file)),
                "Missing migration: {$file}"
            );
        }
    }

    public function test_key_srs_routes_are_registered(): void
    {
        foreach ([
            'organization.companies.index',
            'masters.brands.index',
            'purchasing.orders.index',
            'quotations.index',
            'cheques.index',
            'credit-notes.index',
            'deals.index',
            'targets.index',
            'schemes.index',
            'interest.index',
            'hrms.employees.index',
            'crm.leads.index',
            'tally.queue.index',
            'reports.pending-orders',
            'reports.salesman-outstanding',
            'reports.margin',
            'reports.aging',
            'dashboard',
            'inventory.serials.index',
            'inventory.serials.reserve',
            'inventory.serials.deliver',
            'inventory.serials.return',
            'inventory.valuation.index',
        ] as $route) {
            $this->assertTrue(Route::has($route), "Missing route: {$route}");
        }
    }

    public function test_scheduler_commands_are_registered(): void
    {
        $this->artisan('list')
            ->expectsOutputToContain('dms:refresh-aging')
            ->expectsOutputToContain('dms:identify-overdue')
            ->expectsOutputToContain('dms:preview-interest')
            ->expectsOutputToContain('dms:credit-risk-alerts')
            ->expectsOutputToContain('dms:refresh-pending-orders')
            ->assertSuccessful();
    }

    public function test_critical_views_exist(): void
    {
        foreach ([
            'resources/views/organization/companies/index.blade.php',
            'resources/views/purchasing/orders/index.blade.php',
            'resources/views/sales/quotations/index.blade.php',
            'resources/views/deals/index.blade.php',
            'resources/views/targets/index.blade.php',
            'resources/views/schemes/index.blade.php',
            'resources/views/interest/index.blade.php',
            'resources/views/hrms/employees/index.blade.php',
            'resources/views/crm/leads/index.blade.php',
            'resources/views/tally/queue.blade.php',
            'resources/views/reporting/margin.blade.php',
            'resources/views/inventory/serials/index.blade.php',
            'resources/views/inventory/valuation/index.blade.php',
            'docs/SRS_UAT_CHECKLIST.md',
        ] as $path) {
            $this->assertTrue(
                File::exists(base_path($path)),
                "Missing file: {$path}"
            );
        }
    }
}
