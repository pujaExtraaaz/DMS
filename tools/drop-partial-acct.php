<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach ([
    'acct_integration_references',
    'acct_idempotency_keys',
    'acct_api_request_logs',
    'acct_api_tokens',
] as $table) {
    Illuminate\Support\Facades\Schema::dropIfExists($table);
}

echo "ok\n";
