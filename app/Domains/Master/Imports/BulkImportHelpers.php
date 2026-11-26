<?php

namespace App\Domains\Master\Imports;

trait BulkImportHelpers
{
    protected function rowData(mixed $row): array
    {
        return collect($row)->mapWithKeys(fn ($v, $k) => [strtolower((string) $k) => $v])->toArray();
    }

    protected function str(array $data, string $key): string
    {
        return trim((string) ($data[$key] ?? ''));
    }

    protected function nullableStr(array $data, string $key): ?string
    {
        $value = $this->str($data, $key);

        return $value === '' ? null : $value;
    }

    protected function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y', 'on'], true);
    }

    protected function boolOrDefault(array $data, string $key, bool $default = true): bool
    {
        if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            return $default;
        }

        return $this->isTruthy($data[$key]);
    }

    protected function skip(int $rowNo, string $field, string $message): void
    {
        $this->skipped++;
        $this->errors[] = ['row' => $rowNo, 'field' => $field, 'message' => $message];
    }

    /**
     * True when any compared incoming field differs from the current record.
     * Keys missing from $incoming are ignored (partial updates stay no-ops).
     *
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $incoming
     * @param  list<string>  $keys
     */
    protected function payloadChanged(array $current, array $incoming, array $keys): bool
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $incoming)) {
                continue;
            }

            if ($this->normalizeCompareValue($current[$key] ?? null) !== $this->normalizeCompareValue($incoming[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeCompareValue(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value === null) {
            return '';
        }

        if (is_numeric($value) && ! is_string($value)) {
            $formatted = rtrim(rtrim(sprintf('%.6F', (float) $value), '0'), '.');

            return $formatted === '-0' ? '0' : $formatted;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return '';
        }

        if (is_numeric($trimmed)) {
            $formatted = rtrim(rtrim(sprintf('%.6F', (float) $trimmed), '0'), '.');

            return $formatted === '-0' ? '0' : $formatted;
        }

        return mb_strtolower($trimmed);
    }
}
