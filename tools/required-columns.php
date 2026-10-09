<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = Illuminate\Support\Facades\DB::select("
    select table_name, column_name, column_type
    from information_schema.columns
    where table_schema = database()
      and table_name in ('products', 'customers', 'uoms', 'warehouses', 'invoices', 'invoice_items', 'payments')
      and is_nullable = 'NO'
      and column_default is null
      and extra not like '%auto_increment%'
      and column_name not in ('created_at', 'updated_at')
    order by table_name, ordinal_position
");
foreach ($rows as $row) {
    echo $row->table_name.'.'.$row->column_name.' '.$row->column_type."\n";
}
