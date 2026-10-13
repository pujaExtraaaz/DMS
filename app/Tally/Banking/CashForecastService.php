<?php

namespace Tally\Banking;

use Tally\Models\CashForecast;
use Tally\Models\Company;

class CashForecastService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Company $company, array $data, ?CashForecast $forecast = null): CashForecast
    {
        $opening = round((float) ($data['opening_cash'] ?? 0), 2);
        $inflows = round((float) ($data['projected_inflows'] ?? 0), 2);
        $outflows = round((float) ($data['projected_outflows'] ?? 0), 2);
        $closing = $this->closingCash($opening, $inflows, $outflows);

        $payload = [
            'company_id' => $company->id,
            'name' => $data['name'],
            'as_of_date' => $data['as_of_date'],
            'horizon_days' => (int) ($data['horizon_days'] ?? 30),
            'opening_cash' => number_format($opening, 2, '.', ''),
            'projected_inflows' => number_format($inflows, 2, '.', ''),
            'projected_outflows' => number_format($outflows, 2, '.', ''),
            'closing_cash' => number_format($closing, 2, '.', ''),
            'scenario' => $data['scenario'] ?? 'base',
            'lines_json' => $data['lines_json'] ?? $data['lines'] ?? null,
        ];

        if ($forecast) {
            $forecast->update($payload);

            return $forecast->fresh();
        }

        return CashForecast::query()->create($payload);
    }

    public function closingCash(float $opening, float $projectedInflows, float $projectedOutflows): float
    {
        return round($opening + $projectedInflows - $projectedOutflows, 2);
    }
}
