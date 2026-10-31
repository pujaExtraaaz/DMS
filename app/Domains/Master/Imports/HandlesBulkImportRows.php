<?php

namespace App\Domains\Master\Imports;

use Illuminate\Support\Collection;

trait HandlesBulkImportRows
{
    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    protected function rowData(mixed $row): array
    {
        return collect($row)->mapWithKeys(fn ($v, $k) => [strtolower((string) $k) => $v])->toArray();
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

    protected function str(array $data, string $key): ?string
    {
        $value = trim((string) ($data[$key] ?? ''));

        return $value === '' ? null : $value;
    }
}
