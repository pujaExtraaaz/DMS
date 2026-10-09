<?php

namespace Tally\DataExchange\Import\Definitions;

use Tally\DataExchange\Import\ImportBatch;
use Tally\DataExchange\Import\ImportDefinition;
use Tally\Models\AccountGroup;
use Tally\Models\Ledger;

abstract class AbstractLedgerImporter extends ImportDefinition
{
    public function validate(ImportBatch $batch): void
    {
        $this->parsed = [];
        $company = $batch->company;

        if (! $company) {
            $batch->add(0, 'Select a company before importing '.$this->label().'.');

            return;
        }

        foreach ($batch->rows as $index => $row) {
            $line = $index + 2;
            $name = $this->required($batch, $line, $row, 'name', 'Name');
            $code = $this->code($batch, $line, $batch->cell($row, 'code'), 32, false);
            $group = $this->resolveGroup($batch, $line, $row);
            $balance = $this->amount($batch, $line, $row, 'opening_balance', 'Opening balance');
            $type = $this->balanceType($batch, $line, $row);
            $active = $this->active($batch, $line, $row);
            $gstin = $this->gstin($batch, $line, $row);
            $pan = $this->pan($batch, $line, $row);
            $email = $this->email($batch, $line, $row);
            $registration = $this->registration($batch, $line, $row);
            $limit = $this->nullableAmount($batch, $line, $row, 'credit_limit', 'Credit limit');
            $days = $this->days($batch, $line, $row);

            if ($name) {
                $batch->claim($line, 'ledger name', $name);

                if ($this->taken('ledgers', $company->id, 'name', $name)) {
                    $batch->add($line, 'Ledger "'.$name.'" already exists in this company.');
                }
            }

            if ($code) {
                $batch->claim($line, 'ledger code', $code);

                if ($this->taken('ledgers', $company->id, 'code', $code)) {
                    $batch->add($line, 'Ledger code '.$code.' already exists in this company.');
                }
            }

            if ($gstin) {
                $batch->claim($line, 'GSTIN', $gstin);

                if ($this->taken('ledgers', $company->id, 'gstin', $gstin)) {
                    $batch->add($line, 'GSTIN '.$gstin.' is already used in this company.');
                }
            }

            if ($name === null || $group === null || $balance === null || $type === null || $active === null) {
                continue;
            }

            $this->parsed[] = [
                'company_id' => $company->id,
                'account_group_id' => $group->id,
                'name' => $name,
                'code' => $code,
                'opening_balance' => $balance,
                'opening_balance_type' => $type,
                'address' => $this->limited($batch, $line, $this->optional($batch, $row, 'address', 500), 'Address', 500),
                'state' => $this->limited($batch, $line, $this->optional($batch, $row, 'state', 255), 'State', 255),
                'phone' => $this->limited($batch, $line, $this->optional($batch, $row, 'phone', 30), 'Phone', 30),
                'email' => $email,
                'gstin' => $gstin,
                'gst_registration_type' => $registration,
                'pan' => $pan,
                'credit_limit' => $limit,
                'credit_days' => $days,
                'is_active' => $active,
                'is_system' => false,
            ] + $this->extraProfile($batch, $row);
        }
    }

    public function persist(ImportBatch $batch): int
    {
        foreach ($this->parsed as $row) {
            Ledger::query()->create($row);
        }

        return count($this->parsed);
    }

    /**
     * @param  array<string, string>  $row
     */
    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    protected function extraProfile(ImportBatch $batch, array $row): array
    {
        return [];
    }

    abstract protected function resolveGroup(ImportBatch $batch, int $line, array $row): ?AccountGroup;

    protected function groupByToken(ImportBatch $batch, int $line, string $token): ?AccountGroup
    {
        $company = $batch->company;

        if (! $company || $token === '') {
            return null;
        }

        $group = AccountGroup::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where(function ($query) use ($token) {
                $query->whereRaw('lower(code) = ?', [strtolower($token)])
                    ->orWhereRaw('lower(name) = ?', [mb_strtolower($token)]);
            })
            ->first();

        if (! $group) {
            $batch->add($line, 'Account group "'.$token.'" was not found in this company.');
        }

        return $group;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function nullableAmount(ImportBatch $batch, int $line, array $row, string $key, string $label): ?string
    {
        if ($batch->cell($row, $key) === '') {
            return null;
        }

        return $this->amount($batch, $line, $row, $key, $label, true);
    }

    /**
     * @param  array<string, string>  $row
     */
    private function days(ImportBatch $batch, int $line, array $row): ?int
    {
        $value = $batch->cell($row, 'credit_days');

        if ($value === '') {
            return null;
        }

        if (! preg_match('/^\d+$/', $value) || (int) $value > 9999) {
            $batch->add($line, 'Credit days must be a whole number.');

            return null;
        }

        return (int) $value;
    }
}
