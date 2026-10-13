<?php

namespace Tally\Analytics;

use Tally\Accounting\Money;
use Tally\Models\Company;
use Tally\Models\ProfitabilitySnapshot;

class ProfitabilityService
{
    /**
     * Store a profitability snapshot by dimension.
     *
     * @param  array{dimension: string, dimension_key: string, dimension_label: string, from_date: string, to_date: string, revenue: mixed, cost: mixed}  $data
     */
    public function snapshot(Company $company, array $data): ProfitabilitySnapshot
    {
        $revenue = Money::cents((string) $data['revenue']);
        $cost = Money::cents((string) $data['cost']);
        $contribution = $revenue - $cost;
        $margin = $revenue > 0 ? round(($contribution / $revenue) * 100, 4) : 0;

        return ProfitabilitySnapshot::query()->create([
            'company_id' => $company->id,
            'dimension' => $data['dimension'],
            'dimension_key' => $data['dimension_key'],
            'dimension_label' => $data['dimension_label'],
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'revenue' => Money::format($revenue),
            'cost' => Money::format($cost),
            'contribution' => Money::format($contribution),
            'margin_percent' => $margin,
        ]);
    }
}
