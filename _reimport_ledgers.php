<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domains\TallySync\Bridge\TallyInbound;
use App\Domains\Master\Models\Customer;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Organization\Models\Company as OrgCompany;

ini_set('memory_limit', '2048M');
set_time_limit(0);

$companyName = 'AVIT DIGITAL';
config(['tally_sync.company' => $companyName]);

$zip = new ZipArchive();
$zip->open(__DIR__ . '/storage/app/tally-imports/Avit_data_xml.zip');
$raw = $zip->getFromName('Avit_data_xml/accouting.xml');
$zip->close();
$xml = (substr($raw, 0, 2) === "\xFF\xFE" || substr($raw, 0, 2) === "\xFE\xFF")
    ? mb_convert_encoding($raw, 'UTF-8', 'UTF-16')
    : $raw;
$xml = TallyInbound::sanitizeTallyXml($xml);

$inbound = app(TallyInbound::class);
$ref = new ReflectionClass($inbound);
$force = $ref->getProperty('forceOverwrite');
$force->setAccessible(true);
if ($ref->hasProperty('forceOverwrite')) {
    try { $force->setValue($inbound, true); } catch (Throwable $e) {}
}
// set via method if exists
if (method_exists($inbound, 'force')) {
    $inbound->force(true);
}

echo "Re-importing cost_centres + ledgers (dms+books)...\n";
foreach ([
    ['cost_centres', 'dms'],
    ['ledgers', 'dms'],
    ['cost_categories', 'books'],
    ['cost_centres', 'books'],
    ['ledgers', 'books'],
] as [$key, $target]) {
    echo "→ $key ($target) ... ";
    $result = $inbound->apply($key, $xml, $target);
    echo json_encode($result) . "\n";
}

$samples = ['20 COMPUTER WAREHOUSE', '11 Wireless Experimental Unit', '360 Sound solutions'];
$org = OrgCompany::query()->whereRaw('LOWER(name)=?', [mb_strtolower('Avit Digital')])->first()
    ?: OrgCompany::query()->orderBy('id')->first();
echo "\nOrg company: {$org?->id} {$org?->name}\n";
foreach ($samples as $name) {
    $c = Customer::query()->where('company_id', $org->id)->whereRaw('LOWER(name)=?', [mb_strtolower($name)])->first();
    if (!$c) { echo "MISSING customer $name\n"; continue; }
    echo "=== {$c->name} ===\n";
    echo "gstin={$c->gstin} pan={$c->pan} phone={$c->phone} email={$c->email}\n";
    echo "state={$c->state} pincode={$c->pincode} gst_type={$c->gst_registration_type}\n";
    echo "address=" . str_replace(["\r","\n"], ['',' | '], (string)$c->address) . "\n\n";
}
