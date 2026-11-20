<?php

namespace App\Jobs;

use App\Domains\Hrms\Services\EsslSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncEsslPunchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(EsslSyncService $sync): void
    {
        if (! config('hrms.essl.enabled')) {
            return;
        }

        $sync->sync(now()->subDay()->startOfDay(), now()->endOfDay());
    }
}
