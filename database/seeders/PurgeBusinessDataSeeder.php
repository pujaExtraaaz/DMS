<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Purge seeder — truncates EVERY application table except:
 * - migrations
 * - users (+ login helpers: password_reset_tokens, sessions)
 * - Spatie roles / permissions and their pivots
 *
 * DOES NOT RUN AUTOMATICALLY. Call only when you intend to wipe data:
 *   php artisan db:seed --class=Database\\Seeders\\PurgeBusinessDataSeeder --force
 *
 * Edit preservedTables() if you need to keep anything else before running.
 */
class PurgeBusinessDataSeeder extends Seeder
{
    public function run(): void
    {
        $preserved = array_fill_keys($this->preservedTables(), true);
        $tables = $this->tables();

        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            if (isset($preserved[$table])) {
                $this->command?->line("keep {$table}");
                continue;
            }

            if (! Schema::hasTable($table)) {
                $this->command?->warn("missing {$table}");
                continue;
            }

            try {
                DB::table($table)->truncate();
                $this->command?->line("truncated {$table}");
            } catch (\Throwable $e) {
                $this->command?->warn("skip {$table}: ".$e->getMessage());
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->command?->info('Purged all tables except users, roles/permissions, and migrations.');
    }

    /**
     * Only these tables are never truncated.
     *
     * @return list<string>
     */
    public function preservedTables(): array
    {
        return [
            'migrations',

            // Users / auth session helpers
            'users',
            'password_reset_tokens',
            'sessions',

            // Spatie roles & permissions
            'permissions',
            'roles',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
        ];
    }

    /**
     * Every DMS connection table (current DB), excluding other schemas.
     * Preserved tables are skipped in run(); listed here for an explicit catalog.
     *
     * @return list<string>
     */
    public function tables(): array
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $listed = Schema::getTableListing();
        $tables = [];

        foreach ($listed as $name) {
            // Drivers may return "database.table"
            if (str_contains($name, '.')) {
                [$schema, $table] = explode('.', $name, 2);
                if ($schema !== $database) {
                    continue;
                }
                $tables[] = $table;
                continue;
            }

            $tables[] = $name;
        }

        $tables = array_values(array_unique($tables));
        sort($tables);

        return $tables;
    }
}
