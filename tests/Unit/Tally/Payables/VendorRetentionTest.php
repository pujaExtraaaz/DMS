<?php

namespace Tests\Unit\Tally\Payables;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Models\Ledger;
use Tally\Models\Voucher;
use Tally\Parties\PartyService;
use Tally\Payables\VendorRetentionService;

class VendorRetentionTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_hold_and_release_post_journals_and_update_status(): void
    {
        $company = $this->makeCompany();
        $user = User::factory()->create();

        $liabilities = $company->accountGroups()->where('code', 'CURRENT_LIABILITIES')->first();
        $retentionLedger = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $liabilities->id,
            'name' => 'Vendor Retention Payable',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        $party = app(PartyService::class)->save($company, [
            'type' => 'supplier',
            'name' => 'Retention Vendor',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        $service = app(VendorRetentionService::class);
        $hold = $service->hold($company, $user, [
            'supplier_ledger_id' => $party->ledger_id,
            'retention_ledger_id' => $retentionLedger->id,
            'retention_amount' => '1000.00',
            'retention_date' => '2025-04-15',
            'kind' => 'retention',
        ]);

        $this->assertSame('held', $hold->status);
        $this->assertSame('1000.00', (string) $hold->retention_amount);
        $this->assertNotNull($hold->hold_voucher_id);

        $holdVoucher = Voucher::query()->findOrFail($hold->hold_voucher_id);
        $this->assertTrue($holdVoucher->isPosted());
        $this->assertCount(2, $holdVoucher->entries);

        $released = $service->release($hold, '400.00', $user, null, '2025-06-01');

        $this->assertSame('partial', $released->status);
        $this->assertSame('400.00', (string) $released->released_amount);
        $this->assertNotNull($released->release_voucher_id);

        $fully = $service->release($released->fresh(), '600.00', $user, null, '2025-06-15');

        $this->assertSame('released', $fully->status);
        $this->assertSame('1000.00', (string) $fully->released_amount);
    }
}
