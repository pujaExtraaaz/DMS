<?php

$source = file_get_contents('C:/xampp/htdocs/tally_web/routes/web.php');
$source = str_replace('App\\Http\\Controllers\\', 'Tally\\Http\\Controllers\\', $source);
$source = str_replace('App\\Support\\', 'Tally\\Support\\', $source);
$source = str_replace('App\\Http\\Controllers\\Auth\\LoginController', 'SKIP', $source);

$start = strpos($source, "Route::middleware(['auth', 'authorize.route', 'company.access'])");
if ($start === false) {
    fwrite(STDERR, "auth group not found\n");
    exit(1);
}
$body = substr($source, $start);
$body = preg_replace(
    "/Route::middleware\\(\\['auth', 'authorize.route', 'company.access'\\]\\)->group\\(function \\(\\) \\{/",
    "Route::middleware(['auth', 'tally.workspace'])->prefix('books/tally')->name('books.tally.')->group(function () {",
    $body,
    1
);

$header = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

PHP;

preg_match_all('/^use .+;$/m', file_get_contents('C:/xampp/htdocs/tally_web/routes/web.php'), $uses);
$useLines = [];
foreach ($uses[0] as $line) {
    if (str_contains($line, 'Auth\\')) {
        continue;
    }
    $useLines[] = str_replace('App\\Http\\Controllers\\', 'Tally\\Http\\Controllers\\', $line);
    $useLines[array_key_last($useLines)] = str_replace('App\\Support\\', 'Tally\\Support\\', $useLines[array_key_last($useLines)]);
}

file_put_contents(
    'C:/xampp/htdocs/dms/routes/modules/books.php',
    $header.implode("\n", array_unique($useLines))."\n\n".$body
);
echo "routes written\n";
