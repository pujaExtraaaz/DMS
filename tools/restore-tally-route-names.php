<?php

$root = dirname(__DIR__);
$verbs = 'index|show|create|edit|update|destroy|store|view|delete|manage|print|current|features|barcode|other|sales|purchase|menu|import|export|backup|restore|lookup|retry|password';
$changed = 0;

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app/Tally'));
foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $src = file_get_contents($path);
    $orig = $src;
    $src = preg_replace("/acct_([a-z0-9_]+)\\.({$verbs})\\b/", '$1.$2', $src);
    $src = preg_replace("/->route\\('(?!books\\.tally\\.)([a-z0-9_]+(?:[.-][a-z0-9_]+)+)'/", "->route('books.tally.$1'", $src);
    if ($src !== $orig) {
        file_put_contents($path, $src);
        $changed++;
    }
}

echo "files {$changed}\n";
