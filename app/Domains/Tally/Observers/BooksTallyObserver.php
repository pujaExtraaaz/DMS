<?php

namespace App\Domains\Tally\Observers;

use App\Domains\TallySync\Queue\BooksSyncQueue;
use App\Domains\TallySync\Support\SyncGate;
use App\Domains\Sync\Support\SyncGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class BooksTallyObserver
{
    public function saved(Model $model): void
    {
        if (TallyAutoEnqueueObserver::muted() || SyncGuard::isSyncing() || ! SyncGate::enabled()) {
            return;
        }

        try {
            app(BooksSyncQueue::class)->enqueue($model);
        } catch (\Throwable $exception) {
            Log::warning('Books Tally enqueue failed', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}