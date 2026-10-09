<?php

namespace Tally\DataExchange\Import\Definitions;

use Tally\DataExchange\Import\ImportBatch;
use Tally\Models\AccountGroup;

class PartyImporter extends AbstractLedgerImporter
{
    public function key(): string
    {
        return 'parties';
    }

    public function label(): string
    {
        return 'Customers / Suppliers';
    }

    public function description(): string
    {
        return 'Creates customer ledgers under Sundry Debtors and supplier ledgers under Sundry Creditors.';
    }

    public function columns(): array
    {
        return [
            ['key' => 'party_type', 'required' => true, 'hint' => 'customer or supplier'],
            ['key' => 'name', 'required' => true, 'hint' => ''],
            ['key' => 'code', 'required' => false, 'hint' => ''],
            ['key' => 'opening_balance', 'required' => false, 'hint' => '0.00'],
            ['key' => 'opening_balance_type', 'required' => false, 'hint' => 'debit or credit'],
            ['key' => 'address', 'required' => false, 'hint' => ''],
            ['key' => 'state', 'required' => false, 'hint' => ''],
            ['key' => 'phone', 'required' => false, 'hint' => ''],
            ['key' => 'email', 'required' => false, 'hint' => ''],
            ['key' => 'gstin', 'required' => false, 'hint' => ''],
            ['key' => 'gst_registration_type', 'required' => false, 'hint' => ''],
            ['key' => 'pan', 'required' => false, 'hint' => ''],
            ['key' => 'credit_limit', 'required' => false, 'hint' => ''],
            ['key' => 'credit_days', 'required' => false, 'hint' => ''],
            ['key' => 'country', 'required' => false, 'hint' => ''],
            ['key' => 'is_active', 'required' => false, 'hint' => 'yes or no'],
        ];
    }

    public function sample(): array
    {
        return [
            [
                'party_type' => 'customer',
                'name' => 'Northwind Traders',
                'opening_balance' => '1500.00',
                'opening_balance_type' => 'debit',
                'state' => 'Maharashtra',
                'is_active' => 'yes',
            ],
            [
                'party_type' => 'supplier',
                'name' => 'Metro Supplies',
                'opening_balance' => '800.00',
                'opening_balance_type' => 'credit',
                'is_active' => 'yes',
            ],
        ];
    }

    protected function resolveGroup(ImportBatch $batch, int $line, array $row): ?AccountGroup
    {
        $type = strtolower($batch->cell($row, 'party_type'));
        $code = match ($type) {
            'customer', 'debtor' => 'DEBTORS',
            'supplier', 'creditor' => 'CREDITORS',
            default => null,
        };

        if ($code === null) {
            $batch->add($line, 'Party type must be customer or supplier.');

            return null;
        }

        return $this->groupByToken($batch, $line, $code);
    }

    public function persist(ImportBatch $batch): int
    {
        $count = parent::persist($batch);

        foreach ($this->parsed as $row) {
            $ledger = \Tally\Models\Ledger::query()
                ->where('company_id', $row['company_id'])
                ->where('name', $row['name'])
                ->first();
            $ledger?->party?->update([
                'legal_name' => $row['legal_name'] ?? null,
                'contact_person' => $row['contact_person'] ?? null,
                'mobile' => $row['mobile'] ?? null,
                'shipping_address' => $row['shipping_address'] ?? null,
                'country' => $row['country'] ?? null,
            ]);
        }

        return $count;
    }

    protected function extraProfile(ImportBatch $batch, array $row): array
    {
        return [
            'legal_name' => $this->optional($batch, $row, 'legal_name', 255),
            'contact_person' => $this->optional($batch, $row, 'contact_person', 255),
            'mobile' => $this->optional($batch, $row, 'mobile', 30),
            'shipping_address' => $this->optional($batch, $row, 'shipping_address', 500),
            'country' => $this->optional($batch, $row, 'country', 80),
        ];
    }
}
