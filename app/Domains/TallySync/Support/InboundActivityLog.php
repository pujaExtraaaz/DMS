<?php

namespace App\Domains\TallySync\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InboundActivityLog
{
    public static function item(string $collection, string $action, ?string $name): void
    {
        if (! Schema::hasTable('tally_inbound_logs')) {
            return;
        }

        $label = SyncCoverage::labelForInboundCollection($collection);
        DB::table('tally_inbound_logs')->insert([
            'collection' => substr($collection, 0, 40),
            'item' => substr(SyncCoverage::formatSyncItem($label, $name), 0, 255),
            'action' => substr($action, 0, 20),
            'created_count' => $action === 'created' ? 1 : 0,
            'updated_count' => $action === 'updated' ? 1 : 0,
            'conflict_count' => $action === 'conflict' ? 1 : 0,
            'skipped_count' => $action === 'skipped' ? 0 : 0,
            'alter_id' => 0,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array{created?:int,updated?:int,conflicts?:int,skipped?:int}  $counts
     */
    public static function batch(string $collection, array $counts, int $alterId): void
    {
        if (! Schema::hasTable('tally_inbound_logs')) {
            return;
        }

        $created = (int) ($counts['created'] ?? 0);
        $updated = (int) ($counts['updated'] ?? 0);
        $conflicts = (int) ($counts['conflicts'] ?? 0);
        $skipped = (int) ($counts['skipped'] ?? 0);
        if ($created + $updated + $conflicts === 0) {
            return;
        }

        $label = SyncCoverage::labelForInboundCollection($collection);
        $parts = [];
        if ($created > 0) {
            $parts[] = $created.' created';
        }
        if ($updated > 0) {
            $parts[] = $updated.' updated';
        }
        if ($conflicts > 0) {
            $parts[] = $conflicts.' conflicts';
        }
        if ($skipped > 0) {
            $parts[] = $skipped.' skipped';
        }

        DB::table('tally_inbound_logs')->insert([
            'collection' => substr($collection, 0, 40),
            'item' => substr($label.' window — '.implode(', ', $parts), 0, 255),
            'action' => 'batch',
            'created_count' => $created,
            'updated_count' => $updated,
            'conflict_count' => $conflicts,
            'skipped_count' => $skipped,
            'alter_id' => $alterId,
            'created_at' => now(),
        ]);
    }
}
