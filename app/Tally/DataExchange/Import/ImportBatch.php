<?php

namespace Tally\DataExchange\Import;

use Tally\Models\Company;

class ImportBatch
{
    /** @var list<array{row: int, message: string}> */
    public array $errors = [];

    /** @var array<string, array<string, int>> */
    private array $seen = [];

    /**
     * @param  list<array<string, string>>  $rows
     */
    public function __construct(
        public readonly ?Company $company,
        public readonly array $rows,
    ) {}

    public function add(int $row, string $message): void
    {
        $this->errors[] = ['row' => $row, 'message' => $message];
    }

    public function failed(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @param  array<string, string>  $row
     */
    public function cell(array $row, string $key): string
    {
        return trim((string) ($row[$key] ?? ''));
    }

    public function claim(int $row, string $bucket, string $value): void
    {
        $normalized = mb_strtolower(trim($value));

        if ($normalized === '') {
            return;
        }

        if (isset($this->seen[$bucket][$normalized])) {
            $this->add($row, 'Duplicate '.$bucket.' "'.$value.'" also appears on row '.$this->seen[$bucket][$normalized].'.');

            return;
        }

        $this->seen[$bucket][$normalized] = $row;
    }
}
