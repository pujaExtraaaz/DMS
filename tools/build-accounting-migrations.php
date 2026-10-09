<?php

/**
 * Rewrite tally_web migrations into DMS migrations with an acct_ table prefix.
 * Skips framework auth/cache/job tables so DMS users and queues stay untouched.
 */

$source = 'C:/xampp/htdocs/tally_web/database/migrations';
$target = 'C:/xampp/htdocs/dms/database/migrations';

$skipFiles = [
    '0001_01_01_000000_create_users_table.php',
    '0001_01_01_000001_create_cache_table.php',
    '0001_01_01_000002_create_jobs_table.php',
    '2026_09_30_140000_add_owner_singleton_to_users_table.php',
];

$doNotPrefix = [
    'users',
    'cache',
    'cache_locks',
    'jobs',
    'job_batches',
    'failed_jobs',
    'sessions',
    'password_reset_tokens',
    'migrations',
];

$files = glob($source.'/*.php');
sort($files);

$tables = [];
foreach ($files as $file) {
    if (in_array(basename($file), $skipFiles, true)) {
        continue;
    }
    $contents = file_get_contents($file);
    if (preg_match_all("/Schema::create\\('([a-z0-9_]+)'/", $contents, $matches)) {
        foreach ($matches[1] as $table) {
            $tables[$table] = true;
        }
    }
}

$tables = array_keys($tables);
usort($tables, fn ($a, $b) => strlen($b) <=> strlen($a));

function pluralize(string $word): string
{
    if (str_ends_with($word, 'y') && ! preg_match('/[aeiou]y$/', $word)) {
        return substr($word, 0, -1).'ies';
    }
    if (str_ends_with($word, 's')) {
        return $word;
    }

    return $word.'s';
}

$written = 0;
foreach ($files as $file) {
    $base = basename($file);
    if (in_array($base, $skipFiles, true)) {
        continue;
    }

    $contents = file_get_contents($file);
    if (preg_match("/Schema::(table|create)\\('users'/", $contents) && ! preg_match("/Schema::create\\('(?!users)/", $contents)) {
        // users-only alterations
        if (! preg_match("/Schema::create\\('[a-z0-9_]+'/", $contents)) {
            continue;
        }
    }

    $contents = preg_replace_callback(
        "/->foreignId\\('([a-z0-9_]+)'\\)([^;\\n]*?)->constrained\\(\\)/",
        function (array $m) use ($doNotPrefix) {
            $column = $m[1];
            if (! str_ends_with($column, '_id')) {
                return $m[0];
            }
            $base = substr($column, 0, -3);
            if ($base === 'created_by' || $base === 'updated_by' || $base === 'user') {
                return "->foreignId('{$column}'){$m[2]}->constrained('users')";
            }
            $table = pluralize($base);
            if (in_array($table, $doNotPrefix, true)) {
                return "->foreignId('{$column}'){$m[2]}->constrained('{$table}')";
            }

            return "->foreignId('{$column}'){$m[2]}->constrained('acct_{$table}')";
        },
        $contents
    );

    foreach ($tables as $table) {
        if (in_array($table, $doNotPrefix, true)) {
            continue;
        }
        $contents = preg_replace("/(?<!acct_)'{$table}'/", "'acct_{$table}'", $contents);
        $contents = preg_replace('/(?<!acct_)"'.$table.'"/', '"acct_'.$table.'"', $contents);
    }

    $out = $target.'/2026_10_09_13'.str_pad((string) $written, 4, '0', STR_PAD_LEFT).'_acct_'.preg_replace('/^\\d+_/', '', $base);
    file_put_contents($out, $contents);
    $written++;
    echo "wrote {$out}\n";
}

echo "tables: ".count($tables)." files: {$written}\n";
