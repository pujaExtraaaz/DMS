<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo 'links '.DB::table('sync_entity_links')->count()."\n";
echo 'failures '.DB::table('sync_failures')->count()."\n";
echo 'invoices '.json_encode(DB::table('acct_invoices')->select('kind', 'status')->selectRaw('count(*) as n')->groupBy('kind', 'status')->get())."\n";
echo 'vouchers '.json_encode(DB::table('acct_vouchers')->select('voucher_type', 'status')->selectRaw('count(*) as n')->groupBy('voucher_type', 'status')->get())."\n";
echo 'pos '.DB::table('acct_purchase_orders')->count()." products ".DB::table('acct_products')->count()." parties ".DB::table('acct_parties')->count()."\n";
foreach (DB::table('sync_failures')->select('entity_key', 'message')->selectRaw('count(*) as n')->groupBy('entity_key', 'message')->get() as $row) {
    echo $row->n.' '.$row->entity_key.': '.$row->message."\n";
}
