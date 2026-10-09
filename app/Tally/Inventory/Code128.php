<?php

namespace Tally\Inventory;

/**
 * Code 128-B bars for product labels. USB scanners read the printed bars.
 */
class Code128
{
    /** @var list<string> */
    private const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    public function svg(string $code): string
    {
        $values = [104];

        foreach (str_split($code) as $character) {
            $ascii = ord($character);

            if ($ascii < 32 || $ascii > 126) {
                continue;
            }

            $values[] = $ascii - 32;
        }

        $checksum = $values[0];

        foreach (array_slice($values, 1) as $position => $value) {
            $checksum += $value * ($position + 1);
        }

        $values[] = $checksum % 103;
        $values[] = 106;
        $x = 10;
        $bars = '';

        foreach ($values as $value) {
            $pattern = self::PATTERNS[$value];
            $bar = true;

            foreach (str_split($pattern) as $width) {
                $modules = (int) $width * 2;

                if ($bar) {
                    $bars .= '<rect x="'.$x.'" y="10" width="'.$modules.'" height="70" fill="#1a2332"/>';
                }

                $x += $modules;
                $bar = ! $bar;
            }
        }

        $width = $x + 10;

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$width.' 100" width="'.($width * 1.4).'" height="90" role="img" aria-label="Barcode '.$code.'"><rect width="100%" height="100%" fill="#fff"/>'.$bars.'<text x="'.($width / 2).'" y="96" text-anchor="middle" font-size="12" font-family="sans-serif">'.e($code).'</text></svg>';
    }
}
