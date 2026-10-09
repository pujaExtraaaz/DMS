<?php

namespace App\Domains\Sync\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SyncFailureLogger
{
    public static function write(string $entityKey, string $direction, ?Model $source, Throwable|string $error): void
    {
        DB::table('sync_failures')->insert([
            'entity_key' => $entityKey,
            'direction' => $direction,
            'source_type' => $source ? $source::class : null,
            'source_id' => $source?->getKey(),
            'message' => $error instanceof Throwable ? $error->getMessage() : $error,
            'context' => $error instanceof Throwable ? json_encode([
                'exception' => $error::class,
                'file' => $error->getFile(),
                'line' => $error->getLine(),
            ]) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
