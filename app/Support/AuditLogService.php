<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(
        Model $model,
        string $action,
        ?string $actorName = null
    ): void {
        $actorName = trim(
            $actorName
                ?: auth()->user()?->name
                ?: 'System'
        );

        $field = match ($action) {
            'created' => 'created_by_name',

            'updated',
            'edited' => 'updated_by_name',

            'approved' => 'approved_by_name',

            'converted' => 'converted_by_name',

            'cancelled',
            'canceled' => 'cancelled_by_name',

            default => null,
        };

        if (! $field) {
            return;
        }

        if (! array_key_exists(
            $field,
            $model->getAttributes()
        )) {
            return;
        }

        $model->forceFill([
            $field => $actorName,
        ])->saveQuietly();
    }

    public function actorName(
        ?string $actorName = null
    ): string {
        return trim(
            $actorName
                ?: auth()->user()?->name
                ?: 'System'
        );
    }
}