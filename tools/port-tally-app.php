<?php

/**
 * Copy tally_web application code into DMS under the Tally\ namespace
 * and books view namespace, without overwriting DMS App\ classes.
 */

$sourceRoot = 'C:/xampp/htdocs/tally_web';
$destApp = 'C:/xampp/htdocs/dms/app/Tally';
$destViews = 'C:/xampp/htdocs/dms/resources/views/tally';

function rrmdir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

function copyTree(string $from, string $to, array $skipSuffixes = []): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $relative = substr($item->getPathname(), strlen($from) + 1);
        $relative = str_replace('\\', '/', $relative);
        foreach ($skipSuffixes as $skip) {
            if (str_starts_with($relative, $skip)) {
                continue 2;
            }
        }
        $target = $to.'/'.$relative;
        if ($item->isDir()) {
            if (! is_dir($target)) {
                mkdir($target, 0777, true);
            }
            continue;
        }
        $dir = dirname($target);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        copy($item->getPathname(), $target);
    }
}

rrmdir($destApp);
rrmdir($destViews);
mkdir($destApp, 0777, true);
mkdir($destViews, 0777, true);

copyTree($sourceRoot.'/app', $destApp, [
    'Http/Controllers/Auth',
    'Models/User.php',
    'Providers',
]);
copyTree($sourceRoot.'/resources/views', $destViews);

$phpFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($destApp, FilesystemIterator::SKIP_DOTS));
foreach ($phpFiles as $file) {
    if (! str_ends_with($file->getFilename(), '.php')) {
        continue;
    }
    $path = $file->getPathname();
    $code = file_get_contents($path);
    $code = str_replace('namespace App\\', 'namespace Tally\\', $code);
    $code = str_replace('use App\\', 'use Tally\\', $code);
    $code = str_replace('\\App\\', '\\Tally\\', $code);
    $code = str_replace('Tally\\Models\\User', 'App\\Models\\User', $code);
    $code = str_replace('\\Tally\\Models\\User', '\\App\\Models\\User', $code);
    $code = str_replace("view('", "view('tally::", $code);
    $code = str_replace('view("', 'view("tally::', $code);

    if (str_contains($path, DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR)
        && str_contains($code, 'extends Model')
        && ! str_contains($path, 'AccountingModel.php')) {
        $code = str_replace('extends Model', 'extends AccountingModel', $code);
        if (! str_contains($code, 'use Tally\\Models\\AccountingModel;') && ! str_contains($file->getFilename(), 'AccountingModel.php')) {
            $code = preg_replace('/namespace Tally\\\\Models;/', "namespace Tally\\Models;\n\nuse Tally\\Models\\AccountingModel;", $code, 1);
        }
    }

    file_put_contents($path, $code);
}

$bladeFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($destViews, FilesystemIterator::SKIP_DOTS));
foreach ($bladeFiles as $file) {
    if (! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }
    $code = file_get_contents($file->getPathname());
    $code = str_replace('<x-layouts.', '<x-tally::layouts.', $code);
    $code = str_replace('<x-shell.', '<x-tally::shell.', $code);
    $code = str_replace('<x-ui.', '<x-tally::ui.', $code);
    $code = str_replace('<x-form.', '<x-tally::form.', $code);
    $code = str_replace('<x-invoice.', '<x-tally::invoice.', $code);
    $code = str_replace('<x-voucher.', '<x-tally::voucher.', $code);
    $code = str_replace("@include('", "@include('tally::", $code);
    $code = str_replace('@include("', '@include("tally::', $code);
    $code = preg_replace("/route\\('(?!books\\.tally\\.)/", "route('books.tally.", $code);
    $code = preg_replace('/route\\("(?!books\\.tally\\.)/', 'route("books.tally.', $code);
    file_put_contents($file->getPathname(), $code);
}

foreach (['keyboard.php', 'navigation.php', 'operations.php', 'shortcuts.php', 'states.php', 'domains.php'] as $config) {
    copy($sourceRoot.'/config/'.$config, 'C:/xampp/htdocs/dms/config/'.$config);
}

echo "ported\n";
