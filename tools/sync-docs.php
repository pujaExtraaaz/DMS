<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sync\Support\SyncGuard;
use App\Domains\Sync\Support\SyncRegistry;
use Illuminate\Support\Facades\DB;

$po = App\Domains\Purchasing\Models\PurchaseOrder::query()->orderBy('id')->first();
echo 'po company '.$po->company_id.' supplier '.$po->supplier_id.' status '.$po->status."\n";
$link = Illuminate\Support\Facades\DB::table('accounting_company_links')->where('organization_company_id', $po->company_id)->first();
echo 'link '.($link->acct_company_id ?? 'none')."\n";
DB::beginTransaction();
try {
    SyncGuard::run(function () {
        foreach ([
            PurchaseInvoice::query()->orderBy('id')->first(),
            PurchaseOrder::query()->orderBy('id')->first(),
            Invoice::query()->orderBy('id')->first(),
            Payment::query()->orderBy('id')->first(),
        ] as $row) {
            if (! $row) {
                echo "missing a sample\n";
                continue;
            }
            echo 'sync '.$row::class.' #'.$row->getKey()."\n";
            SyncRegistry::handler($row)->sync($row);
            echo "  ok\n";
        }
    });
    echo "links ".DB::table('sync_entity_links')->count()." failures ".DB::table('sync_failures')->count()."\n";
    echo "books invoices ".DB::table('acct_invoices')->count()." vouchers ".DB::table('acct_vouchers')->count()." pos ".DB::table('acct_purchase_orders')->count()."\n";
    $fail = DB::table('sync_failures')->orderByDesc('id')->limit(5)->get();
    foreach ($fail as $row) {
        echo "FAIL {$row->entity_key}: {$row->message}\n";
    }
} catch (Throwable $e) {
    echo "ERR ".$e::class." ".$e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n";
} finally {
    DB::rollBack();
    echo "rolled back\n";
}
