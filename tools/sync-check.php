<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Sync\Handlers\ProductSync;
use App\Domains\Sync\Handlers\UomSync;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncGuard;
use Illuminate\Support\Facades\DB;
use Tally\Models\Product as BooksProduct;
use Tally\Models\Unit;

$companyId = (int) DB::table('accounting_company_links')->orderBy('organization_company_id')->value('organization_company_id');
$books = BooksCompany::forOrganization($companyId);
echo "org {$companyId} books ".($books?->id).' '.$books?->name."\n";

DB::beginTransaction();
try {
    $uom = Uom::query()->create([
        'name' => 'Sync Check Unit '.uniqid(),
        'code' => 'SC'.substr(uniqid(), -6),
        'is_active' => true,
    ]);
    SyncGuard::run(fn () => app(UomSync::class)->pushToCompany($uom, $books));
    $unitId = DB::table('sync_entity_links')->where('dms_id', $uom->id)->where('entity_key', 'uom:'.$books->id)->value('acct_id');
    $unit = Unit::query()->find($unitId);
    echo 'unit '.($unit?->symbol ?? 'MISSING')."\n";

    $product = Product::query()->create([
        'company_id' => $companyId,
        'name' => 'Sync Check Product '.uniqid(),
        'sku' => 'SC'.substr(uniqid(), -8),
        'base_uom_id' => $uom->id,
        'serial_no' => 'SN'.substr(uniqid(), -8),
        'is_active' => true,
        'selling_price' => 10,
        'purchase_price' => 8,
    ]);
    SyncGuard::run(fn () => app(ProductSync::class)->push($product));
    $booksProductId = DB::table('sync_entity_links')->where('entity_key', 'product')->where('dms_id', $product->id)->value('acct_id');
    $booksProduct = BooksProduct::query()->find($booksProductId);
    echo 'product '.($booksProduct?->code ?? 'MISSING').' unit '.$booksProduct?->primary_unit_id."\n";
    $before = $product->name;
    $booksProduct->update(['name' => $before.' Books']);
    SyncGuard::run(fn () => app(ProductSync::class)->pull($booksProduct->fresh()));
    echo 'pulled '.$product->fresh()->name."\n";

    $customer = App\Domains\Master\Models\Customer::query()->where('company_id', $companyId)->first();
    if ($customer) {
        SyncGuard::run(fn () => app(App\Domains\Sync\Handlers\CustomerSync::class)->push($customer));
        $partyId = DB::table('sync_entity_links')->where('entity_key', 'customer')->where('dms_id', $customer->id)->value('acct_id');
        echo 'party '.($partyId ?: 'MISSING')."\n";
        $freshBooks = BooksProduct::query()->create([
            'company_id' => $books->id,
            'product_group_id' => App\Domains\Sync\Support\BooksCompany::generalGroup($books)->id,
            'primary_unit_id' => $unit->id,
            'name' => 'Books Only '.uniqid(),
            'code' => 'BO'.substr(uniqid(), -8),
            'is_active' => true,
        ]);
        SyncGuard::run(fn () => app(ProductSync::class)->pull($freshBooks));
        $copiedId = DB::table('sync_entity_links')->where('entity_key', 'product')->where('acct_id', $freshBooks->id)->value('dms_id');
        $copied = Product::query()->find($copiedId);
        echo 'copied '.($copied?->sku ?? 'MISSING').' serial '.($copied?->serial_no ?? '')."\n";
    } else {
        echo "no customer\n";
    }
} catch (Throwable $e) {
    echo $e::class.': '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
} finally {
    DB::rollBack();
}

$left = DB::table('acct_products')->where('name', 'like', 'Sync Check Product%')->count();
echo "leftover products {$left}\n";
