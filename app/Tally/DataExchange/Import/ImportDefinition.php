<?php

namespace Tally\DataExchange\Import;

use Tally\Accounting\AccountNature;
use Tally\Accounting\OpeningBalanceType;
use Tally\Models\Company;
use Tally\Tax\GstRegistrationType;
use Carbon\Carbon;

abstract class ImportDefinition
{
    /** @var list<array<string, mixed>> */
    protected array $parsed = [];

    abstract public function key(): string;

    abstract public function label(): string;

    abstract public function description(): string;

    public function requiresCompany(): bool
    {
        return true;
    }

    /**
     * @return list<array{key: string, required: bool, hint: string}>
     */
    abstract public function columns(): array;

    /**
     * @return list<array<string, string>>
     */
    abstract public function sample(): array;

    abstract public function validate(ImportBatch $batch): void;

    abstract public function persist(ImportBatch $batch): int;

    /**
     * @return list<string>
     */
    public function requiredHeaders(): array
    {
        $headers = [];

        foreach ($this->columns() as $column) {
            if ($column['required']) {
                $headers[] = $column['key'];
            }
        }

        return $headers;
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return array_column($this->columns(), 'key');
    }

    protected function company(ImportBatch $batch, int $row): ?Company
    {
        if ($batch->company) {
            return $batch->company;
        }

        $batch->add($row, 'Select a company before importing this data.');

        return null;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function required(ImportBatch $batch, int $line, array $row, string $key, string $label): ?string
    {
        $value = $batch->cell($row, $key);

        if ($value === '') {
            $batch->add($line, $label.' is required.');

            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function optional(ImportBatch $batch, array $row, string $key, ?int $max = null): ?string
    {
        $value = $batch->cell($row, $key);

        if ($value === '') {
            return null;
        }

        return $value;
    }

    protected function limited(ImportBatch $batch, int $line, ?string $value, string $label, int $max): ?string
    {
        if ($value !== null && mb_strlen($value) > $max) {
            $batch->add($line, $label.' must be '.$max.' characters or fewer.');

            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function active(ImportBatch $batch, int $line, array $row): ?bool
    {
        $value = strtolower($batch->cell($row, 'is_active'));

        if ($value === '') {
            return true;
        }

        if (in_array($value, ['1', 'true', 'yes', 'y', 'active'], true)) {
            return true;
        }

        if (in_array($value, ['0', 'false', 'no', 'n', 'inactive'], true)) {
            return false;
        }

        $batch->add($line, 'Status must be yes or no.');

        return null;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function email(ImportBatch $batch, int $line, array $row): ?string
    {
        $value = $batch->cell($row, 'email');

        if ($value === '') {
            return null;
        }

        if (! filter_var($value, FILTER_VALIDATE_EMAIL) || mb_strlen($value) > 255) {
            $batch->add($line, 'Enter a valid email address.');

            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function gstin(ImportBatch $batch, int $line, array $row): ?string
    {
        $value = strtoupper((string) preg_replace('/\s+/', '', $batch->cell($row, 'gstin')));

        if ($value === '') {
            return null;
        }

        if (! preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $value)) {
            $batch->add($line, 'Enter a valid 15-character GSTIN.');

            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function pan(ImportBatch $batch, int $line, array $row): ?string
    {
        $value = strtoupper((string) preg_replace('/\s+/', '', $batch->cell($row, 'pan')));

        if ($value === '') {
            return null;
        }

        if (! preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $value)) {
            $batch->add($line, 'Enter a valid 10-character PAN.');

            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function registration(ImportBatch $batch, int $line, array $row): ?string
    {
        $value = strtolower($batch->cell($row, 'gst_registration_type'));

        if ($value === '') {
            return null;
        }

        if (GstRegistrationType::tryFrom($value) === null) {
            $batch->add($line, 'GST registration must be regular, composition, unregistered, or consumer.');

            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function date(ImportBatch $batch, int $line, array $row, string $key, string $label): ?string
    {
        $value = $batch->cell($row, $key);

        if ($value === '') {
            $batch->add($line, $label.' is required.');

            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            try {
                $date = Carbon::createFromFormat('Y-m-d', $value);

                if ($date && $date->format('Y-m-d') === $value) {
                    return $value;
                }
            } catch (\Throwable) {
                $batch->add($line, $label.' must be a date in YYYY-MM-DD format.');

                return null;
            }
        }

        if (preg_match('/^\d{5}$/', $value)) {
            $serial = (int) $value;

            if ($serial > 20000 && $serial < 80000) {
                return Carbon::create(1899, 12, 30, 0, 0, 0, 'UTC')->addDays($serial)->toDateString();
            }
        }

        $batch->add($line, $label.' must be a date in YYYY-MM-DD format.');

        return null;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function amount(ImportBatch $batch, int $line, array $row, string $key, string $label, bool $required = false, int $scale = 2, string $default = '0'): ?string
    {
        $value = $batch->cell($row, $key);

        if ($value === '') {
            if ($required) {
                $batch->add($line, $label.' is required.');

                return null;
            }

            return $default;
        }

        $pattern = $scale === 0 ? '/^\d+$/' : '/^\d+(\.\d{1,'.$scale.'})?$/';

        if (! preg_match($pattern, $value)) {
            $batch->add($line, $label.' must be a number with up to '.$scale.' decimal places.');

            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function balanceType(ImportBatch $batch, int $line, array $row, bool $required = false): ?string
    {
        $value = strtolower($batch->cell($row, 'opening_balance_type'));

        if ($value === '') {
            if ($required) {
                $batch->add($line, 'Opening balance type is required.');

                return null;
            }

            return OpeningBalanceType::Debit->value;
        }

        $value = match ($value) {
            'dr', 'debit' => OpeningBalanceType::Debit->value,
            'cr', 'credit' => OpeningBalanceType::Credit->value,
            default => null,
        };

        if ($value === null) {
            $batch->add($line, 'Opening balance type must be debit or credit.');
        }

        return $value;
    }

    protected function nature(ImportBatch $batch, int $line, string $value): ?string
    {
        $value = strtolower($value);
        $match = AccountNature::tryFrom($value);

        if ($match) {
            return $match->value;
        }

        foreach (AccountNature::cases() as $case) {
            if (strtolower($case->label()) === $value) {
                return $case->value;
            }
        }

        $batch->add($line, 'Nature must be asset, liability, income, or expense.');

        return null;
    }

    protected function code(ImportBatch $batch, int $line, string $value, int $max, bool $required): ?string
    {
        $value = strtoupper(trim($value));

        if ($value === '') {
            if ($required) {
                $batch->add($line, 'Code is required.');
            }

            return $required ? null : null;
        }

        $pattern = '/^[A-Z0-9][A-Z0-9_-]{0,'.($max - 1).'}$/';

        if (! preg_match($pattern, $value)) {
            $batch->add($line, 'Code must start with a letter or number and stay within '.$max.' characters.');

            return null;
        }

        return $value;
    }

    protected function taken(string $table, int $companyId, string $column, string $value): bool
    {
        if (! preg_match('/^[a-z_]+$/', $table) || ! preg_match('/^[a-z_]+$/', $column)) {
            throw new \InvalidArgumentException('The lookup column is not valid.');
        }

        return \Illuminate\Support\Facades\DB::table($table)
            ->where('company_id', $companyId)
            ->whereRaw('lower('.$column.') = ?', [mb_strtolower($value)])
            ->exists();
    }
}
