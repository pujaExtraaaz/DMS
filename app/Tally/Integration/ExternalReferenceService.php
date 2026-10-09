<?php

namespace Tally\Integration;

use Tally\Models\IntegrationReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class ExternalReferenceService
{
    public function attach(Model $model, ?int $companyId, mixed $source, mixed $externalId, mixed $status = null): void
    {
        $externalId = trim((string) $externalId);
        $source = trim((string) $source);

        if ($externalId === '' && $source === '') {
            return;
        }

        if ($externalId === '' || $source === '') {
            throw ValidationException::withMessages([
                'external_reference_id' => 'Send both an external source and an external reference id.',
            ]);
        }

        $sync = SyncStatus::tryFrom((string) ($status ?: SyncStatus::Synced->value)) ?? SyncStatus::Synced;

        try {
            $model->integrationReference()->updateOrCreate(
                [],
                [
                    'company_id' => $companyId,
                    'source' => $source,
                    'external_reference_id' => $externalId,
                    'sync_status' => $sync,
                ],
            );
        } catch (QueryException $exception) {
            if (! $this->isDuplicate($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'external_reference_id' => 'This external reference is already linked to another record.',
            ]);
        }
    }

    /**
     * @return array{source: string, external_reference_id: string, sync_status: string}|null
     */
    public function present(Model $model): ?array
    {
        $link = $model->relationLoaded('integrationReference')
            ? $model->getRelation('integrationReference')
            : $model->integrationReference()->first();

        if (! $link instanceof IntegrationReference) {
            return null;
        }

        return [
            'source' => $link->source,
            'external_reference_id' => $link->external_reference_id,
            'sync_status' => $link->sync_status->value,
        ];
    }

    private function isDuplicate(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? '';

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
