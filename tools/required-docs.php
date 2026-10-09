<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (['purchase_invoices', 'credit_notes', 'purchase_orders', 'invoices', 'payments'] as $table) {
    $values = Illuminate\Support\Facades\DB::table($table)->select('status')->distinct()->pluck('status');
    echo "$table status: ".($values->isEmpty() ? '(none)' : $values->implode(', '))."\n";
}
echo "counts pi ".Illuminate\Support\Facades\DB::table('purchase_invoices')->count()
    ." cn ".Illuminate\Support\Facades\DB::table('credit_notes')->count()
    ." po ".Illuminate\Support\Facades\DB::table('purchase_orders')->count()
    ." inv ".Illuminate\Support\Facades\DB::table('invoices')->count()
    ." pay ".Illuminate\Support\Facades\DB::table('payments')->count()."\n";
foreach (['purchase_invoices', 'credit_notes', 'purchase_orders'] as $table) {
    $type = Illuminate\Support\Facades\DB::selectOne("select column_type from information_schema.columns where table_schema = database() and table_name = ? and column_name = 'status'", [$table]);
    echo "$table type ".$type->column_type."\n";
}
exit;
foreach (['purchase_invoices', 'credit_notes', 'purchase_orders'] as $table) {
    echo "== $table\n";
    foreach (Illuminate\Support\Facades\Schema::getColumnListing($table) as $column) {
        echo "  $column\n";
    }
}
