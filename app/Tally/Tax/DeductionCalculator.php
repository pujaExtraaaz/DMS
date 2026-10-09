<?php

namespace Tally\Tax;

use Tally\Accounting\Money;
use Tally\Models\DeductionSection;

/**
 * Transaction-level TDS/TCS. Rates and thresholds come from the section
 * record. This does not post a voucher.
 */
final class DeductionCalculator
{
    public function __construct(private readonly TaxCalculationService $tax) {}

    public function calculate(string $transactionAmount, DeductionSection $section, string $yearToDateBefore = '0.00'): DeductionResult
    {
        $amount = Money::cents($transactionAmount);
        $before = Money::cents($yearToDateBefore);
        $threshold = Money::cents((string) $section->threshold_amount);
        $crossed = ($before + $amount) > $threshold;
        $rate = (string) $section->rate;

        if (! $crossed || $amount <= 0) {
            return new DeductionResult(Money::format($amount), '0.00', false, $rate);
        }

        return new DeductionResult(
            Money::format($amount),
            $this->tax->percentOf(Money::format($amount), $rate),
            true,
            $rate,
        );
    }
}
