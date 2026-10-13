<?php

namespace Tally\Reporting;

use Tally\Accounting\Money;

class BreakEvenReport
{
    /**
     * @param  array{fixed_costs: mixed, price_per_unit?: mixed, variable_cost_per_unit?: mixed, contribution_margin_percent?: mixed}  $input
     * @return array{fixed_costs: string, contribution_per_unit: string, break_even_units: string, break_even_revenue: string, mode: string}
     */
    public function calculate(array $input): array
    {
        $fixed = Money::cents((string) ($input['fixed_costs'] ?? '0'));
        if ($fixed <= 0) {
            return [
                'fixed_costs' => '0.00',
                'contribution_per_unit' => '0.00',
                'break_even_units' => '0.00',
                'break_even_revenue' => '0.00',
                'mode' => 'none',
            ];
        }

        $price = Money::cents((string) ($input['price_per_unit'] ?? '0'));
        $variable = Money::cents((string) ($input['variable_cost_per_unit'] ?? '0'));
        $marginPct = (float) ($input['contribution_margin_percent'] ?? 0);

        if ($price > 0 && $price > $variable) {
            $contribution = $price - $variable;
            $units = $contribution > 0 ? round($fixed / $contribution, 4) : 0;
            $revenue = (int) round($units * $price);

            return [
                'fixed_costs' => Money::format($fixed),
                'contribution_per_unit' => Money::format($contribution),
                'break_even_units' => number_format($units, 4, '.', ''),
                'break_even_revenue' => Money::format($revenue),
                'mode' => 'unit',
            ];
        }

        if ($marginPct > 0 && $marginPct < 100) {
            $revenue = (int) round($fixed / ($marginPct / 100));

            return [
                'fixed_costs' => Money::format($fixed),
                'contribution_per_unit' => number_format($marginPct, 2, '.', '').'%',
                'break_even_units' => '—',
                'break_even_revenue' => Money::format($revenue),
                'mode' => 'margin',
            ];
        }

        return [
            'fixed_costs' => Money::format($fixed),
            'contribution_per_unit' => '0.00',
            'break_even_units' => '0.00',
            'break_even_revenue' => '0.00',
            'mode' => 'none',
        ];
    }
}
