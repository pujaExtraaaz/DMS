<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (['orders', 'stock_adjustments'] as $table) {
    foreach (Illuminate\Support\Facades\DB::select('show columns from '.$table) as $column) {
        if (in_array($column->Field, ['fulfilment_mode', 'status', 'reason', 'credit_check_status'], true)) {
            echo $table.'.'.$column->Field.' '.$column->Type."\n";
        }
    }
}
