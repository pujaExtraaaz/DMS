<?php

$path = __DIR__.'/../storage/logs/laravel.log';
$fh = fopen($path, 'r');
$size = filesize($path);
$read = min($size, 400000);
fseek($fh, $size - $read);
$chunk = fread($fh, $read);
fclose($fh);

$pos = strrpos($chunk, 'local.ERROR:');
if ($pos === false) {
    echo "no error\n";
    exit(1);
}
$slice = substr($chunk, $pos, 2500);
echo $slice, "\n";
