<?php

namespace App\Support;

use Barryvdh\DomPDF\PDF;

/**
 * Shared DomPDF tuning for server-side exports (shared hosting).
 */
final class DompdfConfigurator
{
    /** Max table rows rendered in a single PDF download. */
    public const MAX_TABULAR_ROWS = 300;

    public static function bootstrap(): void
    {
        @set_time_limit(180);

        $previousLimit = ini_get('memory_limit');
        if ($previousLimit !== false && $previousLimit !== '-1') {
            $bytes = self::memoryLimitToBytes($previousLimit);
            if ($bytes > 0 && $bytes < 1536 * 1024 * 1024) {
                @ini_set('memory_limit', '1536M');
            }
        }
    }

    public static function apply(PDF $pdf): PDF
    {
        return $pdf
            ->setOption('isRemoteEnabled', false)
            ->setOption('isPhpEnabled', false)
            ->setOption('isFontSubsettingEnabled', false)
            ->setOption('dpi', 72)
            ->setOption('chroot', [public_path(), storage_path(), resource_path()]);
    }

    /**
     * @template T of string|int|float|null
     * @param  array<int, array<int, T>>  $rows
     * @return array{0: array<int, array<int, T>>, 1: ?string}
     */
    public static function capRows(array $rows, int $max = self::MAX_TABULAR_ROWS): array
    {
        $count = count($rows);
        if ($count <= $max) {
            return [$rows, null];
        }

        $note = "Showing first {$max} of {$count} rows. Use Excel or CSV for the full list.";

        return [array_slice($rows, 0, $max), $note];
    }

    private static function memoryLimitToBytes(string $limit): int
    {
        $limit = trim($limit);
        if ($limit === '-1') {
            return -1;
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}