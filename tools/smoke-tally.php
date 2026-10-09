<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = App\Models\User::query()->first();
if (! $user) {
    echo "no user\n";
    exit(1);
}

auth()->login($user);
$request = Illuminate\Http\Request::create('/books/tally', 'GET');
$request->setLaravelSession($app->make('session')->driver());
$request->session()->start();
auth()->login($user);

try {
    $response = $kernel->handle($request);
    echo 'status '.$response->getStatusCode()."\n";
    $body = $response->getContent();
    if (preg_match('/class="exception-message[^"]*"[^>]*>(.*?)<\/div>/s', $body, $m)) {
        echo 'exception '.trim(strip_tags($m[1]))."\n";
    } elseif (preg_match('/<title>(.*?)<\/title>/s', $body, $m)) {
        echo 'title '.trim(html_entity_decode(strip_tags($m[1])))."\n";
    }
    if (preg_match('/Route \[.*?\] not defined\./', $body, $m)) {
        echo $m[0]."\n";
    } elseif (preg_match('/# [^\n]+Exception[^\n]*/', $body, $m)) {
        echo trim($m[0])."\n";
    }
    echo 'bytes '.strlen($body)."\n";
    echo (str_contains($body, 'No company') ? "company missing\n" : "company present\n");
    echo (str_contains($body, 'Back to DMS') ? "back link yes\n" : "back link no\n");
    echo (str_contains($body, 'Avit Digital') ? "avit yes\n" : "avit no\n");

    $home = Illuminate\Http\Request::create('/dashboard', 'GET');
    $home->setLaravelSession($request->session());
    $homeResponse = $kernel->handle($home);
    echo 'dashboard '.$homeResponse->getStatusCode()."\n";
} catch (Throwable $e) {
    echo $e::class.': '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}
