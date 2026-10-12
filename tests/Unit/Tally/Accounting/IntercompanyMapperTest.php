<?php

namespace Tests\Unit\Tally\Accounting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Accounting\IntercompanyMapper;
use Tally\Models\Ledger;
use Tally\Models\LedgerMirror;

class IntercompanyMapperTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_prefers_explicit_ledger_mirror_over_name_match(): void
    {
        $source = $this->makeCompany(['name' => 'Source Co']);
        $counter = $this->makeCompany(['name' => 'Counter Co']);

        $groupSrc = $source->accountGroups()->create([
            'name' => 'Expenses',
            'code' => 'EXP',
            'nature' => 'expense',
            'is_active' => true,
        ]);
        $groupCtr = $counter->accountGroups()->create([
            'name' => 'Expenses',
            'code' => 'EXP',
            'nature' => 'expense',
            'is_active' => true,
        ]);

        $sourceLedger = Ledger::query()->create([
            'company_id' => $source->id,
            'account_group_id' => $groupSrc->id,
            'name' => 'Travel',
            'opening_balance' => 0,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);
        $nameMatch = Ledger::query()->create([
            'company_id' => $counter->id,
            'account_group_id' => $groupCtr->id,
            'name' => 'Travel',
            'opening_balance' => 0,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);
        $mapped = Ledger::query()->create([
            'company_id' => $counter->id,
            'account_group_id' => $groupCtr->id,
            'name' => 'Travel Mapped',
            'opening_balance' => 0,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        LedgerMirror::query()->create([
            'source_company_id' => $source->id,
            'source_ledger_id' => $sourceLedger->id,
            'counterparty_company_id' => $counter->id,
            'counterparty_ledger_id' => $mapped->id,
        ]);

        $result = app(IntercompanyMapper::class)->mirrorLedger($source, $counter, $sourceLedger);

        $this->assertNotNull($result);
        $this->assertSame($mapped->id, $result->id);
        $this->assertNotSame($nameMatch->id, $result->id);
    }

    public function test_falls_back_to_name_match(): void
    {
        $source = $this->makeCompany(['name' => 'A Co']);
        $counter = $this->makeCompany(['name' => 'B Co']);
        $groupSrc = $source->accountGroups()->create([
            'name' => 'Income',
            'code' => 'INC',
            'nature' => 'income',
            'is_active' => true,
        ]);
        $groupCtr = $counter->accountGroups()->create([
            'name' => 'Income',
            'code' => 'INC',
            'nature' => 'income',
            'is_active' => true,
        ]);
        $sourceLedger = Ledger::query()->create([
            'company_id' => $source->id,
            'account_group_id' => $groupSrc->id,
            'name' => 'Service Income',
            'opening_balance' => 0,
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);
        $match = Ledger::query()->create([
            'company_id' => $counter->id,
            'account_group_id' => $groupCtr->id,
            'name' => 'Service Income',
            'opening_balance' => 0,
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        $result = app(IntercompanyMapper::class)->mirrorLedger($source, $counter, $sourceLedger);

        $this->assertSame($match->id, $result?->id);
    }
}
