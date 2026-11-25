<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domains\TallySync\Bridge\TallyInbound;

$zip = new ZipArchive();
$zip->open(__DIR__ . '/storage/app/tally-imports/Avit_data_xml.zip');
$raw = $zip->getFromName('Avit_data_xml/accouting.xml');
$zip->close();
$text = (substr($raw, 0, 2) === "\xFF\xFE" || substr($raw, 0, 2) === "\xFE\xFF")
    ? mb_convert_encoding($raw, 'UTF-8', 'UTF-16')
    : $raw;
$text = TallyInbound::sanitizeTallyXml($text);

$inbound = new TallyInbound();
$ref = new ReflectionClass($inbound);
$call = function (string $method, DOMElement $node, ...$extra) use ($inbound, $ref) {
    $m = $ref->getMethod($method);
    $m->setAccessible(true);
    return $m->invoke($inbound, $node, ...$extra);
};

foreach (['20 COMPUTER WAREHOUSE', '11 Wireless Experimental Unit', '360 Sound solutions'] as $name) {
    $q = preg_quote($name, '/');
    if (!preg_match('/<LEDGER[^>]*NAME="' . $q . '"[^>]*>[\s\S]*?<\/LEDGER>/i', $text, $m)) {
        echo "MISSING $name\n";
        continue;
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?><ROOT xmlns:UDF="TallyUDF">' . $m[0] . '</ROOT>';
    $xml = TallyInbound::sanitizeTallyXml($xml);
    $dom = new DOMDocument();
    $ok = @$dom->loadXML($xml);
    $node = $ok ? $dom->getElementsByTagName('LEDGER')->item(0) : null;
    if (!$node instanceof DOMElement) {
        $err = libxml_get_last_error();
        echo "PARSEFAIL $name err=" . ($err ? $err->message : 'unknown') . "\n";
        continue;
    }
    echo "=== $name ===\n";
    echo "gstin=" . $call('gstinFromLedger', $node) . "\n";
    echo "pan=" . $call('panFromLedger', $node, $call('gstinFromLedger', $node)) . "\n";
    echo "phone=" . ($call('phoneFromLedger', $node) ?? '') . "\n";
    echo "email=" . ($call('emailFromLedger', $node) ?? '') . "\n";
    echo "state=" . $call('stateFromLedger', $node) . "\n";
    echo "pincode=" . $call('pincodeFromLedger', $node) . "\n";
    echo "contact=" . $call('contactPersonFromLedger', $node) . "\n";
    echo "gstType=" . $call('gstRegistrationTypeFromLedger', $node) . "\n";
    echo "address=" . str_replace("\n", ' | ', $call('addressLinesFromLedger', $node)) . "\n\n";
}
