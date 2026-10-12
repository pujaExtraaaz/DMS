<?php

namespace App\Domains\TallySync\Support;

/**
 * Single switch for dormant TallyPrime sync.
 */
class SyncGate
{
    public static function enabled(): bool
    {
        return (bool) config('tally_sync.enabled', false);
    }
}
