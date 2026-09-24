<?php

namespace App\Domains\Tally\Services;

use App\Domains\Tally\Models\TallySyncMapping;
use Illuminate\Database\Eloquent\Model;

class TallySyncMappingService
{
    public function findForDms(
        string $entityType,
        int $entityId,
        string $tallyType
    ): ?TallySyncMapping {
        return TallySyncMapping::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('tally_type', $tallyType)
            ->first();
    }

    public function findForTally(
        string $tallyType,
        ?string $tallyGuid
    ): ?TallySyncMapping {
        if (! $tallyGuid) {
            return null;
        }

        return TallySyncMapping::query()
            ->where('tally_type', $tallyType)
            ->where('tally_guid', $tallyGuid)
            ->first();
    }

    public function createOrUpdate(
        Model $model,
        string $entityType,
        string $tallyType,
        ?string $tallyGuid,
        ?string $tallyName = null,
        string $syncStatus = 'synced'
    ): TallySyncMapping {
        return TallySyncMapping::query()->updateOrCreate(
            [
                'entity_type' => $entityType,
                'entity_id' => $model->getKey(),
                'tally_type' => $tallyType,
            ],
            [
                'tally_guid' => $tallyGuid,
                'tally_name' => $tallyName,
                'sync_status' => $syncStatus,
                'last_synced_at' => now(),
            ]
        );
    }

    public function markStatus(
        TallySyncMapping $mapping,
        string $status
    ): TallySyncMapping {
        $mapping->update([
            'sync_status' => $status,
            'last_synced_at' => now(),
        ]);

        return $mapping->fresh();
    }
}