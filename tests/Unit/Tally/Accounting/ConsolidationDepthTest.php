<?php

namespace Tests\Unit\Tally\Accounting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Accounting\ConsolidationService;
use Tally\Models\Ledger;

class ConsolidationDepthTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_map_ownership_percent_reflected_in_group_share(): void
    {
        $parent = $this->makeCompany(['name' => 'Parent Co']);
        $sub = $this->makeCompany(['name' => 'Sub Co']);

        $bankGroup = $sub->accountGroups()->where('code', 'BANK')->first();
        Ledger::query()->create([
            'company_id' => $sub->id,
            'account_group_id' => $bankGroup->id,
            'name' => 'Sub Bank',
            'opening_balance' => '10000.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        $service = app(ConsolidationService::class);
        $service->upsertMap($parent, [
            'subsidiary_company_id' => $sub->id,
            'ownership_percent' => 60,
        ]);

        $result = $service->consolidatedTotals($parent, '2025-09-30');
        $subRow = collect($result['totals']['companies'])->firstWhere('company_id', $sub->id);

        $this->assertNotNull($subRow);
        $this->assertSame('60.00', $subRow['ownership_percent']);

        $net = (float) $subRow['net'];
        $groupShare = (float) $subRow['group_share'];
        $this->assertEqualsWithDelta($net * 0.60, $groupShare, 0.02);

        $sheet = $service->worksheet($parent);
        $this->assertArrayHasKey('subsidiaries', $sheet);
        $this->assertCount(1, $sheet['subsidiaries']);
        $this->assertEqualsWithDelta(60, (float) $sheet['subsidiaries'][0]['ownership_percent'], 0.001);
        $this->assertSame($sub->id, $sheet['subsidiaries'][0]['company_id']);
    }
}
