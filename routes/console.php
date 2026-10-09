<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('dms:refresh-aging')->dailyAt('01:00');
Schedule::command('dms:identify-overdue')->dailyAt('01:10');
Schedule::command('dms:preview-interest')->dailyAt('01:20');
Schedule::command('dms:credit-risk-alerts')->dailyAt('01:30');
Schedule::command('dms:refresh-pending-orders')->dailyAt('01:40');
