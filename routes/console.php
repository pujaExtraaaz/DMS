<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tally:retry-failed {--id=* : Only these queue ids}', function () {
    $service = app(\App\Domains\Tally\Services\TallyExportService::class);

    $items = \App\Domains\Tally\Models\TallySyncQueue::query()
        ->where('status', 'failed')
        ->when($this->option('id'), fn ($q, $ids) => $q->whereIn('id', $ids))
        ->orderBy('id')
        ->get();

    foreach ($items as $item) {
        try {
            $service->retry($item);
            $this->info("#{$item->id} {$item->document_type} rebuilt and re-queued");
        } catch (\Throwable $e) {
            $this->error("#{$item->id} {$item->document_type}: {$e->getMessage()}");
        }
    }

    $this->comment($items->count().' failed item(s) processed.');
})->purpose('Rebuild XML for failed Tally sync rows and put them back to pending');

Schedule::command('dms:refresh-aging')->dailyAt('01:00');
Schedule::command('dms:identify-overdue')->dailyAt('01:10');
Schedule::command('dms:preview-interest')->dailyAt('01:20');
Schedule::command('dms:credit-risk-alerts')->dailyAt('01:30');
Schedule::command('dms:refresh-pending-orders')->dailyAt('01:40');
