<?php

function makeIcon(string $path, int $size): void
{
    $im = imagecreatetruecolor($size, $size);
    $bg1 = imagecolorallocate($im, 15, 76, 129);
    $bg2 = imagecolorallocate($im, 79, 70, 229);
    $white = imagecolorallocate($im, 255, 255, 255);

    imagefilledrectangle($im, 0, 0, $size - 1, $size - 1, $bg1);
    $pad = (int) ($size * 0.12);
    imagefilledrectangle($im, $pad, $pad, $size - $pad - 1, $size - $pad - 1, $bg2);

    $text = 'D';
    $candidates = [
        '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/liberation/LiberationSans-Bold.ttf',
    ];
    $ttf = null;
    foreach ($candidates as $c) {
        if (file_exists($c)) {
            $ttf = $c;
            break;
        }
    }

    if ($ttf) {
        $fs = (int) ($size * 0.42);
        $bbox = imagettfbbox($fs, 0, $ttf, $text);
        $x = (int) (($size - ($bbox[2] - $bbox[0])) / 2);
        $y = (int) (($size - ($bbox[1] - $bbox[7])) / 2 + ($bbox[1] - $bbox[7]));
        imagettftext($im, $fs, 0, $x, (int) ($y - $size * 0.04), $white, $ttf, $text);
    } else {
        $font = 5;
        $tw = imagefontwidth($font) * strlen($text);
        $th = imagefontheight($font);
        imagestring($im, $font, (int) (($size - $tw) / 2), (int) (($size - $th) / 2), $text, $white);
    }

    imagepng($im, $path);
    imagedestroy($im);
}

$dir = __DIR__.'/icons';
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

makeIcon($dir.'/icon-192.png', 192);
makeIcon($dir.'/icon-512.png', 512);
makeIcon($dir.'/apple-touch-icon.png', 180);

echo "created\n";
