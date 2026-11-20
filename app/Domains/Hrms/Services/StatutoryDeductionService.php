<?php

namespace App\Domains\Hrms\Services;

class StatutoryDeductionService
{
    /**
     * Merge statutory PF / ESI / PT / TDS into deductions when not already present.
     *
     * @param  array<string, float>  $earnings
     * @param  array<string, float>  $deductions
     * @return array<string, float>
     */
    public function apply(array $earnings, array $deductions): array
    {
        $cfg = config('hrms.statutory', []);
        $basic = (float) ($earnings['BASIC'] ?? 0);
        $gross = array_sum($earnings);

        // PF
        if (($cfg['pf']['enabled'] ?? false) && ! isset($deductions['PF'])) {
            $base = match ($cfg['pf']['base'] ?? 'basic') {
                'gross' => $gross,
                default => $basic,
            };
            $ceiling = (float) ($cfg['pf']['wage_ceiling'] ?? 15000);
            $wage = min($base, $ceiling);
            $rate = (float) ($cfg['pf']['employee_rate'] ?? 12);
            if ($wage > 0 && $rate > 0) {
                $deductions['PF'] = round($wage * $rate / 100, 2);
            }
        }

        // ESI — only when gross ≤ ceiling
        if (($cfg['esi']['enabled'] ?? false) && ! isset($deductions['ESI'])) {
            $ceiling = (float) ($cfg['esi']['gross_ceiling'] ?? 21000);
            $rate = (float) ($cfg['esi']['employee_rate'] ?? 0.75);
            if ($gross > 0 && $gross <= $ceiling && $rate > 0) {
                $deductions['ESI'] = round($gross * $rate / 100, 2);
            }
        }

        // Professional Tax slabs
        if (($cfg['pt']['enabled'] ?? false) && ! isset($deductions['PT'])) {
            $amount = 0.0;
            foreach ($cfg['pt']['slabs'] ?? [] as $slab) {
                $upto = $slab['upto'] ?? null;
                if ($upto === null || $gross <= (float) $upto) {
                    $amount = (float) ($slab['amount'] ?? 0);
                    break;
                }
            }
            if ($amount > 0) {
                $deductions['PT'] = round($amount, 2);
            }
        }

        // Provisional TDS
        if (($cfg['tds']['enabled'] ?? false) && ! isset($deductions['TDS'])) {
            $rate = (float) ($cfg['tds']['rate'] ?? 0);
            $std = (float) ($cfg['tds']['standard_deduction_monthly'] ?? 0);
            $taxable = max(0, $gross - ($deductions['PF'] ?? 0) - $std);
            if ($rate > 0 && $taxable > 0) {
                $deductions['TDS'] = round($taxable * $rate / 100, 2);
            }
        }

        return $deductions;
    }
}
