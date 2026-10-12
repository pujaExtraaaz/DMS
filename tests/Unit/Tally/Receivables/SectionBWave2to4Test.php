<?php

namespace Tests\Unit\Tally\Receivables;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Accounting\Money;
use Tally\Models\PartyPriceList;
use Tally\Models\PriceList;
use Tally\Models\PriceListLine;
use Tally\Models\Product;
use Tally\Parties\PartyCredit;
use Tally\Parties\PartyService;
use Tally\Receivables\AgeingReport;
use Tally\Receivables\DeferredRevenueService;
use Tally\Selling\PartyPricing;

class SectionBWave2to4Test extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_party_pricing_uses_assigned_price_list(): void
    {
        $company = $this->makeCompany();
        $party = app(PartyService::class)->save($company, [
            'type' => 'customer',
            'name' => 'Priced Buyer',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        $group = \Tally\Models\ProductGroup::query()->create([
            'company_id' => $company->id,
            'name' => 'Goods',
            'is_active' => true,
        ]);
        $unit = \Tally\Models\Unit::query()->create([
            'company_id' => $company->id,
            'name' => 'Nos',
            'symbol' => 'Nos',
            'decimal_places' => 0,
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'company_id' => $company->id,
            'product_group_id' => $group->id,
            'primary_unit_id' => $unit->id,
            'name' => 'Widget',
            'code' => 'W1',
            'sales_rate' => '100.00',
            'is_active' => true,
        ]);

        $list = PriceList::query()->create([
            'company_id' => $company->id,
            'name' => 'VIP',
            'is_active' => true,
        ]);
        PriceListLine::query()->create([
            'price_list_id' => $list->id,
            'product_id' => $product->id,
            'rate' => '88.50',
        ]);
        PartyPriceList::query()->create([
            'company_id' => $company->id,
            'party_id' => $party->id,
            'price_list_id' => $list->id,
            'is_active' => true,
        ]);

        $rate = app(PartyPricing::class)->rateFor($party->fresh('priceLists'), $product);
        $this->assertSame('88.50', $rate);
    }

    public function test_party_credit_exposes_available_credit_from_limit(): void
    {
        $company = $this->makeCompany();
        $party = app(PartyService::class)->save($company, [
            'type' => 'customer',
            'name' => 'Limited Buyer',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'credit_limit' => '5000.00',
            'is_active' => true,
        ]);

        $available = app(PartyCredit::class)->availableCredit($party->ledger->fresh());
        $this->assertSame('5000.00', $available);
        $this->assertSame(0, app(PartyCredit::class)->billWiseExposureCents($party->ledger));
    }

    public function test_ageing_buckets_and_deferred_math(): void
    {
        $company = $this->makeCompany();
        $report = app(AgeingReport::class)->receivables($company, now()->toDateString());
        $this->assertArrayHasKey('b1_30', $report['totals']);
        $this->assertArrayHasKey('b90_plus', $report['totals']);
        $this->assertSame('0.00', $report['totals']['total']);

        $customer = app(PartyService::class)->save($company, [
            'type' => 'customer',
            'name' => 'Defer Co',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        $salesGroup = $company->accountGroups()->where('code', 'SALES')->first();
        $liability = $company->accountGroups()->where('code', 'CURRENT_LIABILITIES')->first();
        $revenue = \Tally\Models\Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $salesGroup->id,
            'name' => 'Service Income',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);
        $deferred = \Tally\Models\Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $liability->id,
            'name' => 'Deferred Revenue',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        $schedule = app(DeferredRevenueService::class)->create([
            'company_id' => $company->id,
            'customer_ledger_id' => $customer->ledger_id,
            'revenue_ledger_id' => $revenue->id,
            'deferred_ledger_id' => $deferred->id,
            'total_amount' => '1200.00',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'frequency' => 'monthly',
            'next_recognize_on' => '2025-04-01',
        ]);

        $this->assertSame('1200.00', $schedule->remainingAmount());
        $this->assertSame(1200_00, Money::cents($schedule->total_amount));
    }

    public function test_bill_of_supply_category_constant(): void
    {
        $this->assertContains('bill_of_supply', ['tax_invoice', 'bill_of_supply']);
    }
}
