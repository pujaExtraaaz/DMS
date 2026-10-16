<?php

namespace App\Console\Commands;

use App\Support\NameCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UppercaseNamesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dms:uppercase-names
                            {--audit : Audit and display records that have non-uppercase name values}
                            {--apply : Safely update non-uppercase name values in the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and safely update all name fields in the database to full uppercase.';

    /**
     * Registered table and column targets for uppercase name maintenance.
     *
     * @var array<string, array<int, string>>
     */
    public static array $targets = [
        'companies' => ['name'],
        'branches' => ['name'],
        'warehouses' => ['name'],
        'business_groups' => ['name'],
        'financial_years' => ['name'],
        'brands' => ['name'],
        'categories' => ['name'],
        'sub_categories' => ['name'],
        'products' => ['name'],
        'customers' => ['name', 'shipping_name', 'contact_person'],
        'customer_types' => ['name'],
        'areas' => ['name'],
        'routes' => ['name'],
        'vehicles' => ['name', 'vehicle_name'],
        'drivers' => ['name'],
        'delivery_people' => ['name'],
        'tax_rates' => ['name'],
        'uoms' => ['name'],
        'party_addresses' => ['contact_name'],
        'party_contacts' => ['name'],
        'party_bank_accounts' => ['account_holder_name', 'bank_name', 'branch_name'],
        'party_credit_cheques' => ['bank_name'],
        'freight_bills' => ['transporter_name'],
        'eway_bills' => ['transporter_name'],
        'orders' => ['shipping_name'],
        'quotations' => ['customer_name'],
        'credit_notes' => ['customer_name'],
        'cheques' => ['party_name', 'bank_name'],
        'purchases' => ['supplier_name'],
        'purchase_order_items' => ['product_name'],
        'expense_types' => ['name'],
        'schemes' => ['name'],
        'target_periods' => ['name'],
        'interest_rules' => ['name'],
        'interest_documents' => ['customer_name'],
        'deals' => ['customer_name', 'contact_person'],
        'deal_expenses' => ['vendor_name'],
        'departments' => ['name'],
        'designations' => ['name'],
        'employees' => ['first_name', 'middle_name', 'last_name', 'bank_name', 'branch_name'],
        'leave_types' => ['name'],
        'employee_kpis' => ['name'],
        'leads' => ['contact_name', 'company_name'],
        'lead_campaigns' => ['name'],
        'lead_segments' => ['name'],
        'lead_sources' => ['name'],
        'meta_lead_forms' => ['name'],
        'users' => ['name'],
        'od_accounts' => ['bank_name'],
        'tally_ledgers' => ['name'],
        'tally_groups' => ['name'],
        'tally_cost_centres' => ['name'],
        'tally_stock_items' => ['name'],
        'tally_stock_groups' => ['name'],
        'tally_units' => ['name'],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'Starting in-place uppercase update...' : 'Running uppercase audit...');

        $auditRows = [];
        $totalNeedsUpdate = 0;
        $totalUpdated = 0;

        foreach (self::$targets as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $primaryKey = Schema::hasColumn($table, 'id') ? 'id' : null;
                $query = DB::table($table)->whereNotNull($column)->where($column, '!=', '');

                if ($primaryKey) {
                    $query->orderBy($primaryKey);
                }

                $records = $query->get();
                $tableNeedsUpdate = 0;
                $sampleBefore = null;
                $sampleAfter = null;

                foreach ($records as $record) {
                    $original = $record->{$column};
                    $uppercased = NameCase::format($original);

                    // Binary comparison or exact case comparison
                    if ($original !== $uppercased) {
                        $tableNeedsUpdate++;
                        if ($sampleBefore === null) {
                            $sampleBefore = $original;
                            $sampleAfter = $uppercased;
                        }

                        if ($apply) {
                            if ($primaryKey) {
                                DB::table($table)
                                    ->where($primaryKey, $record->{$primaryKey})
                                    ->update([$column => $uppercased]);
                            } else {
                                DB::table($table)
                                    ->where($column, $original)
                                    ->update([$column => $uppercased]);
                            }
                            $totalUpdated++;
                        }
                    }
                }

                if ($tableNeedsUpdate > 0) {
                    $totalNeedsUpdate += $tableNeedsUpdate;
                    $auditRows[] = [
                        $table,
                        $column,
                        $tableNeedsUpdate,
                        $sampleBefore ?? '-',
                        $sampleAfter ?? '-',
                    ];
                }
            }
        }

        if (! empty($auditRows)) {
            $this->table(
                ['Table', 'Column', 'Pending Updates', 'Sample (Current)', 'Sample (Uppercased)'],
                $auditRows
            );
        } else {
            $this->info('All target name fields in the database are already 100% full uppercase.');
        }

        if ($apply) {
            $this->info("Completed! Total records updated in place: {$totalUpdated}");
        } else {
            $this->info("Audit complete. Total records needing uppercase update: {$totalNeedsUpdate}");
            if ($totalNeedsUpdate > 0) {
                $this->comment('To apply these changes safely in-place to the database, run: php artisan dms:uppercase-names --apply');
            }
        }

        return Command::SUCCESS;
    }
}

