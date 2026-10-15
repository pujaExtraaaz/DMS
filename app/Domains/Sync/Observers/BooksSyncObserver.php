<?php

namespace App\Domains\Sync\Observers;

use App\Domains\Sync\Support\SyncFailureLogger;
use App\Domains\Sync\Support\SyncGuard;
use App\Domains\Sync\Support\SyncRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

class BooksSyncObserver
{
    public function saved(Model $model): void
    {
        $this->later('sync', $model);
    }

    public function deleted(Model $model): void
    {
        $handler = SyncRegistry::handler($model);
        if (! $handler || ! method_exists($handler, 'remove')) {
            return;
        }

        $this->later('remove', $model);
    }

    private function later(string $method, Model $model): void
    {
        if (SyncGuard::isSyncing() || ! SyncRegistry::handlerClass($model)) {
            return;
        }

        $class = $model::class;
        $id = $model->getKey();
        $key = SyncRegistry::entityKey($model);

        DB::afterCommit(function () use ($method, $class, $id, $key, $model) {
            if (SyncGuard::isSyncing()) {
                return;
            }

            $query = $class::query();
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($class), true)) {
                $query->withTrashed();
            }

            $fresh = $method === 'remove' ? $model : $query->find($id);
            if (! $fresh) {
                return;
            }

            // Soft-deleted DMS rows should deactivate Books counterparts, not re-push as active.
            if (
                $method === 'sync'
                && method_exists($fresh, 'trashed')
                && $fresh->trashed()
            ) {
                $method = 'remove';
            }

            try {
                SyncGuard::run(function () use ($method, $fresh) {
                    $handler = SyncRegistry::handler($fresh);
                    if ($handler && method_exists($handler, $method)) {
                        $handler->{$method}($fresh);
                    }
                });
            } catch (Throwable $exception) {
                SyncFailureLogger::write($key, str_starts_with($class, 'Tally\\') ? 'books_to_dms' : 'dms_to_books', $fresh, $exception);
            }
        });
    }
}
