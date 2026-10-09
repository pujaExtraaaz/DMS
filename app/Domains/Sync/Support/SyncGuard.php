<?php

namespace App\Domains\Sync\Support;

final class SyncGuard
{
    private static int $depth = 0;

    public static function isSyncing(): bool
    {
        return self::$depth > 0;
    }

    public static function run(callable $callback): mixed
    {
        self::$depth++;

        try {
            return $callback();
        } finally {
            self::$depth--;
        }
    }
}
