<?php

$root = dirname(__DIR__);
$tables = [];

foreach (glob($root.'/database/migrations/*acct*.php') as $file) {
    $src = file_get_contents($file);
    if (preg_match_all("/Schema::create\\('acct_([a-z0-9_]+)'/", $src, $m)) {
        foreach ($m[1] as $name) {
            $tables[$name] = true;
        }
    }
}

$tables = array_keys($tables);
usort($tables, fn ($a, $b) => strlen($b) <=> strlen($a));

$changed = 0;

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app/Tally'));
foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $src = file_get_contents($path);
    $orig = $src;

    foreach ($tables as $table) {
        $acct = 'acct_'.$table;
        $src = str_replace("table('{$table}')", "table('{$acct}')", $src);
        $src = str_replace("hasTable('{$table}')", "hasTable('{$acct}')", $src);
        $src = str_replace("table(\"{$table}\")", "table(\"{$acct}\")", $src);
        $src = str_replace("hasTable(\"{$table}\")", "hasTable(\"{$acct}\")", $src);

        foreach (['join', 'leftJoin', 'rightJoin', 'crossJoin'] as $join) {
            $src = str_replace("->{$join}('{$table}'", "->{$join}('{$acct}'", $src);
            $src = str_replace("->{$join}(\"{$table}\"", "->{$join}(\"{$acct}\"", $src);
        }

        $src = preg_replace("/'{$table}\\.([A-Za-z_][A-Za-z0-9_]*)'/", "'{$acct}.$1'", $src);
        $src = preg_replace('/"'.$table.'\\.([A-Za-z_][A-Za-z0-9_]*)"/', '"'.$acct.'.$1"', $src);
    }

    $lines = explode("\n", $src);
    foreach ($lines as $i => $line) {
        if (! preg_match('/Raw\(|::raw\(/', $line)) {
            continue;
        }
        foreach ($tables as $table) {
            $line = preg_replace('/(?<!acct_)(?<![A-Za-z0-9_])'.$table.'\\./', 'acct_'.$table.'.', $line);
        }
        $lines[$i] = $line;
    }
    $src = implode("\n", $lines);

    $src = preg_replace("/->route\\('(?!books\\.tally\\.)([a-z0-9]+(?:[.-][a-z0-9]+)+)'/", "->route('books.tally.$1'", $src);

    if ($src !== $orig) {
        file_put_contents($path, $src);
        $changed++;
        echo str_replace($root.'\\', '', $path), "\n";
    }
}

echo "files {$changed}, tables ".count($tables)."\n";
