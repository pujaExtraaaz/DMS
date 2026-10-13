<?php

namespace Tests\Unit\Tally\Assets;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Assets\DepreciationService;
use Tally\Assets\FixedAssetService;
use Tally\Models\FixedAssetCategory;

class SectionFGWave1Test extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_category_asset_and_straight_line_depreciation_amount_positive(): void
    {
        $company = $this->makeCompany();

        $category = FixedAssetCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'Plant & Machinery',
            'depreciation_method' => 'straight_line',
            'useful_life_years' => 5,
            'residual_percent' => 5,
            'is_active' => true,
        ]);

        $asset = app(FixedAssetService::class)->create($company, [
            'category_id' => $category->id,
            'name' => 'CNC Lathe',
            'acquisition_date' => $company->financial_year_start->toDateString(),
            'gross_value' => '120000.00',
        ]);

        $this->assertSame($category->id, $asset->category_id);
        $this->assertSame('120000.00', (string) $asset->gross_value);
        $this->assertSame('6000.00', (string) $asset->residual_value);
        $this->assertSame('120000.00', (string) $asset->book_value);

        $monthly = app(DepreciationService::class)->monthlyAmount($asset->fresh('category'));

        // (120000 - 6000) / 60 = 1900
        $this->assertGreaterThan(0, $monthly);
        $this->assertEqualsWithDelta(1900.0, $monthly, 0.01);

        $entries = app(DepreciationService::class)->runForPeriod($company, '2025-04');
        $this->assertCount(1, $entries);
        $this->assertGreaterThan(0, (float) $entries[0]->amount);
        $this->assertSame('straight_line', $entries[0]->method);
    }
}
