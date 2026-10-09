<?php

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('C:/xampp/htdocs/dms/app/Tally', FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (! str_ends_with($file->getFilename(), '.php')) {
        continue;
    }
    $path = $file->getPathname();
    $code = file_get_contents($path);
    $updated = str_replace('->tally_route(', '->route(', $code);
    if ($updated !== $code) {
        file_put_contents($path, $updated);
    }
}
echo "fixed\n";
