<?php

namespace App\Domains\TallySync\Import;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chunked INSERT … ON DUPLICATE KEY UPDATE helpers for simple master tables (point 2).
 */
final class TallyBulkUpsert
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $updateColumns
     */
    public static function upsert(string $table, array $rows, array $uniqueBy, array $updateColumns, int $chunk = 250): int
    {
        if ($rows === [] || ! Schema::hasTable($table)) {
            return 0;
        }

        $written = 0;
        foreach (array_chunk($rows, $chunk) as $batch) {
            DB::table($table)->upsert($batch, $uniqueBy, $updateColumns);
            $written += count($batch);
        }

        return $written;
    }

    /**
     * Disable expensive MySQL extras for the duration of a bulk import callback.
     *
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public static function withFastSession(callable $callback): mixed
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            return $callback();
        }

        try {
            DB::statement('SET SESSION unique_checks=0');
            DB::statement('SET SESSION foreign_key_checks=0');
        } catch (\Throwable) {
            // Shared hosts may forbid SESSION tweaks — continue normally.
        }

        try {
            return $callback();
        } finally {
            try {
                DB::statement('SET SESSION unique_checks=1');
                DB::statement('SET SESSION foreign_key_checks=1');
            } catch (\Throwable) {
            }
        }
    }
}
