<?php

namespace App\Support;

use App\Domains\Organization\Models\ActivityLog;
use App\Domains\Organization\Models\ApprovalLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(
        Model $model,
        string $action,
        ?string $actorName = null,
        array $properties = [],
    ): void {
        $actorName = trim(
            $actorName
                ?: auth()->user()?->name
                ?: 'System'
        );

        $field = match ($action) {
            'created' => 'created_by_name',
            'updated', 'edited' => 'updated_by_name',
            'approved' => 'approved_by_name',
            'converted' => 'converted_by_name',
            'cancelled', 'canceled' => 'cancelled_by_name',
            default => null,
        };

        if ($field && array_key_exists($field, $model->getAttributes())) {
            $model->forceFill([$field => $actorName])->saveQuietly();
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'actor_name' => $actorName,
            'action' => $action,
            'subject_type' => $model::class,
            'subject_id' => $model->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }

    public function approval(
        Model $model,
        string $action,
        ?string $decision = null,
        ?string $reason = null,
        array $meta = [],
    ): ApprovalLog {
        return ApprovalLog::create([
            'user_id' => auth()->id(),
            'actor_name' => $this->actorName(),
            'approvable_type' => $model::class,
            'approvable_id' => $model->getKey(),
            'action' => $action,
            'decision' => $decision,
            'reason' => $reason,
            'meta' => $meta ?: null,
        ]);
    }

    public function actorName(?string $actorName = null): string
    {
        return trim(
            $actorName
                ?: auth()->user()?->name
                ?: 'System'
        );
    }
}
