<?php

namespace Tally\Accounting;

use Tally\Tax\TaxCalculationService;

/**
 * Simple interest on an outstanding amount. Nothing here posts a voucher.
 */
final class InterestCalculator
{
    public function __construct(private readonly TaxCalculationService $tax) {}

    /**
     * @return array{days: int, interest: string}
     */
    public function onOutstanding(string $principal, string $annualPercent, string $dueDate, string $asOf, int $dayCount = 365): array
    {
        if ($asOf <= $dueDate || Money::cents($principal) <= 0) {
            return ['days' => 0, 'interest' => '0.00'];
        }

        $days = (int) ((strtotime($asOf) - strtotime($dueDate)) / 86400);
        $days = max(0, $days);

        if ($days === 0 || $dayCount < 1) {
            return ['days' => $days, 'interest' => '0.00'];
        }

        $annual = Money::cents($this->tax->percentOf($principal, $annualPercent));
        $interest = intdiv($annual * $days, $dayCount);
        $remainder = ($annual * $days) % $dayCount;

        if ($remainder * 2 >= $dayCount) {
            $interest++;
        }

        return ['days' => $days, 'interest' => Money::format($interest)];
    }
}
