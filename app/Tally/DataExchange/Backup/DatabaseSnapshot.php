<?php

namespace Tally\DataExchange\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSnapshot
{
    public const FORMAT = 'tally-cloud-backup';

    public const VERSION = 1;

    /**
     * Tables that stay under the running application: the Super Admin account,
     * framework tables, and the backup history itself.
     *
     * @var list<string>
     */
    public const EXCLUDED = [
        'migrations',
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'data_backups',
        'data_restores',
        'keyboard_shortcuts',
        'api_tokens',
        'api_request_logs',
        'idempotency_keys',
        'integration_references',
        'webhook_endpoints',
        'webhook_deliveries',
        'audit_logs',
    ];

    /**
     * Business tables owned by this application. The MySQL database can also
     * contain unrelated tables, so the snapshot never reads or replaces those.
     *
     * @var list<string>
     */
    public const APPLICATION_TABLES = [
        'companies',
        'branches',
        'financial_years',
        'account_groups',
        'ledgers',
        'voucher_sequences',
        'vouchers',
        'voucher_entries',
        'invoices',
        'invoice_lines',
        'units',
        'product_groups',
        'godowns',
        'products',
        'hsn_sacs',
        'tax_categories',
        'tax_rates',
        'tax_accounts',
        'stock_transactions',
        'stock_transaction_lines',
        'stock_movements',
        'stock_batches',
        'stock_serials',
        'bills_of_materials',
        'bom_lines',
        'bom_byproducts',
        'manufacturing_orders',
        'bills',
        'bill_allocations',
        'bank_accounts',
        'bank_reconciliations',
        'cost_categories',
        'cost_centres',
        'budgets',
        'deduction_sections',
        'purchase_orders',
        'purchase_order_lines',
        'parties',
        'product_barcodes',
        'preferences',
        'integrations',
        'user_companies',
        'user_branches',
        'ledger_openings',
    ];

    /**
     * @return list<string>
     */
    public function tables(): array
    {
        $present = [];

        foreach (self::APPLICATION_TABLES as $name) {
            if (Schema::hasTable($name)) {
                $present[] = $name;
            }
        }

        return $this->order($present);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function capture(): array
    {
        $tables = [];

        foreach ($this->tables() as $table) {
            $tables[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
        }

        return $tables;
    }

    /**
     * @param  array<string, mixed>  $tables
     */
    public function checksum(array $tables): string
    {
        return hash('sha256', json_encode($this->canonicalize($tables), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /**
     * @param  list<string>  $tables
     * @return list<string>
     */
    private function order(array $tables): array
    {
        $dependencies = [];

        foreach ($tables as $table) {
            $dependencies[$table] = [];

            foreach (Schema::getForeignKeys($table) as $key) {
                $foreign = (string) ($key['foreign_table'] ?? '');

                if ($foreign !== '' && $foreign !== $table && in_array($foreign, $tables, true)) {
                    $dependencies[$table][$foreign] = $foreign;
                }
            }
        }

        $ready = [];
        $guard = 0;

        while ($dependencies !== [] && $guard < count($tables) + 2) {
            $guard++;

            foreach ($dependencies as $table => $needs) {
                if ($needs === [] || array_diff($needs, $ready) === []) {
                    $ready[] = $table;
                    unset($dependencies[$table]);
                }
            }
        }

        return array_values(array_unique([...$ready, ...array_keys($dependencies)]));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map($this->canonicalize(...), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
