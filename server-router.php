<?php

/**
 * Local router for `php -S` — silences stdout request-log notices that
 * corrupt JSON API responses when the console pipe is broken (disk full / bad fd).
 */
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$publicPath = __DIR__.'/public';
chdir($publicPath);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';
