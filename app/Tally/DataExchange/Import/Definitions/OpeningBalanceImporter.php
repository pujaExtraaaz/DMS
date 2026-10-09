<?php

namespace Tally\DataExchange\Import\Definitions;

use Tally\DataExchange\Import\ImportBatch;
use Tally\DataExchange\Import\ImportDefinition;
use Tally\Models\Ledger;

class OpeningBalanceImporter extends ImportDefinition
{
    public function key(): string
    {
        return 'opening-balances';
    }

    public function label(): string
    {
        return 'Opening balances';
    }

    public function description(): string
    {
        return 'Updates opening balances on ledgers that already exist in the current company. It does not create ledgers.';
    }

    public function columns(): array
    {
        return [
            ['key' => 'ledger_code', 'required' => false, 'hint' => 'Code or name is required'],
            ['key' => 'ledger_name', 'required' => false, 'hint' => ''],
            ['key' => 'opening_balance', 'required' => true, 'hint' => '0.00'],
            ['key' => 'opening_balance_type', 'required' => true, 'hint' => 'debit or credit'],
        ];
    }

    public function sample(): array
    {
        return [[
            'ledger_name' => 'Cash',
            'opening_balance' => '2500.00',
            'opening_balance_type' => 'debit',
        ]];
    }

    public function validate(ImportBatch $batch): void
    {
        $this->parsed = [];
        $company = $batch->company;

        if (! $company) {
            $batch->add(0, 'Select a company before importing opening balances.');

            return;
        }

        foreach ($batch->rows as $index => $row) {
            $line = $index + 2;
            $code = $batch->cell($row, 'ledger_code');
            $name = $batch->cell($row, 'ledger_name');
            $balance = $this->amount($batch, $line, $row, 'opening_balance', 'Opening balance', true);
            $type = $this->balanceType($batch, $line, $row, true);

            if ($code === '' && $name === '') {
                $batch->add($line, 'Ledger code or ledger name is required.');

                continue;
            }

            $ledger = null;

            if ($code !== '') {
                $batch->claim($line, 'ledger', $code);
                $ledger = Ledger::query()
                    ->where('company_id', $company->id)
                    ->whereRaw('lower(code) = ?', [strtolower($code)])
                    ->first();
            }

            if ($name !== '') {
                $batch->claim($line, 'ledger', $name);
                $byName = Ledger::query()
                    ->where('company_id', $company->id)
                    ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
                    ->first();

                if ($ledger && $byName && $ledger->id !== $byName->id) {
                    $batch->add($line, 'Ledger code and name point to different ledgers.');

                    continue;
                }

                $ledger ??= $byName;
            }

            if (! $ledger) {
                $batch->add($line, 'Ledger "'.($code !== '' ? $code : $name).'" was not found in this company.');

                continue;
            }

            $batch->claim($line, 'ledger id', (string) $ledger->id);

            if ($balance === null || $type === null) {
                continue;
            }

            $this->parsed[] = [
                'id' => $ledger->id,
                'opening_balance' => $balance,
                'opening_balance_type' => $type,
            ];
        }
    }

    public function persist(ImportBatch $batch): int
    {
        foreach ($this->parsed as $row) {
            Ledger::query()->whereKey($row['id'])->update([
                'opening_balance' => $row['opening_balance'],
                'opening_balance_type' => $row['opening_balance_type'],
            ]);
        }

        return count($this->parsed);
    }
}
