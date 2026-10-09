<?php

$roots = [
    'C:/xampp/htdocs/dms/app/Tally',
    'C:/xampp/htdocs/dms/resources/views/tally',
];

foreach ($roots as $root) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        $path = $file->getPathname();
        if (str_ends_with($path, 'helpers.php')) {
            continue;
        }
        if (! str_ends_with($path, '.php') && ! str_ends_with($path, '.blade.php')) {
            continue;
        }
        $code = file_get_contents($path);
        $code = str_replace('\\App\\', '\\Tally\\', $code);
        $code = str_replace('\\Tally\\Models\\User', '\\App\\Models\\User', $code);
        $code = preg_replace('/(?<!tally_)route\(/', 'tally_route(', $code);
        $code = str_replace('Route::has(', 'tally_route_has(', $code);
        $code = str_replace('\\Illuminate\\Support\\Facades\\tally_route_has(', 'tally_route_has(', $code);
        file_put_contents($path, $code);
    }
}

echo "rewritten\n";
