<?php

namespace Tally\Tax;

use Tally\Accounting\Money;
use Tally\Models\TaxRate;
use InvalidArgumentException;

/**
 * Splits a taxable amount into CGST, SGST, IGST, and cess.
 * Intra-state supply uses CGST and SGST. Inter-state supply uses IGST.
 * Rates come from the tax rate record. Nothing here is hardcoded.
 */
final class TaxCalculationService
{
    public function calculate(
        string $taxableAmount,
        TaxRate $rate,
        bool $intraState,
        TaxRounding $rounding = TaxRounding::Paisa,
    ): TaxBreakdown {
        $taxable = Money::cents($taxableAmount);

        if ($taxable < 0) {
            throw new InvalidArgumentException('Taxable amount cannot be negative.');
        }

        [$cgstRate, $sgstRate, $igstRate, $cessRate] = $this->rates($rate);
        $cgst = $intraState ? $this->applyRounding($this->taxCents($taxable, $cgstRate), $rounding) : 0;
        $sgst = $intraState ? $this->applyRounding($this->taxCents($taxable, $sgstRate), $rounding) : 0;
        $igst = $intraState ? 0 : $this->applyRounding($this->taxCents($taxable, $igstRate), $rounding);
        $cess = $this->applyRounding($this->taxCents($taxable, $cessRate), $rounding);

        return $this->breakdown($taxable, $cgst, $sgst, $igst, $cess, $intraState);
    }

    /**
     * The amount is the price the party pays. Tax is extracted so the
     * components plus the taxable value equal that price.
     */
    public function fromInclusive(
        string $grossAmount,
        TaxRate $rate,
        bool $intraState,
        TaxRounding $rounding = TaxRounding::Paisa,
    ): TaxBreakdown {
        $gross = Money::cents($grossAmount);

        if ($gross < 0) {
            throw new InvalidArgumentException('Taxable amount cannot be negative.');
        }

        [$cgstRate, $sgstRate, $igstRate, $cessRate] = $this->rates($rate);
        $combined = $intraState ? $cgstRate + $sgstRate + $cessRate : $igstRate + $cessRate;

        if ($combined === 0 || $gross === 0) {
            return $this->breakdown($gross, 0, 0, 0, 0, $intraState);
        }

        $tax = $this->proportion($gross, $combined, 1000000 + $combined);
        $parts = $intraState
            ? ['cgst' => $cgstRate, 'sgst' => $sgstRate, 'igst' => 0, 'cess' => $cessRate]
            : ['cgst' => 0, 'sgst' => 0, 'igst' => $igstRate, 'cess' => $cessRate];
        $split = $this->split($tax, $parts);

        foreach ($split as $key => $cents) {
            $split[$key] = $this->applyRounding($cents, $rounding);
        }

        $roundedTax = array_sum($split);

        return $this->breakdown($gross - $roundedTax, $split['cgst'], $split['sgst'], $split['igst'], $split['cess'], $intraState);
    }

    public function percentOf(string $amount, string $percent, TaxRounding $rounding = TaxRounding::Paisa): string
    {
        $cents = Money::cents($amount);

        if ($cents < 0) {
            throw new InvalidArgumentException('Amount cannot be negative.');
        }

        return Money::format($this->applyRounding($this->taxCents($cents, $this->percentUnits($percent)), $rounding));
    }

    public function intraState(?string $companyState, ?string $partyState): ?bool
    {
        $company = $this->normalize($companyState);
        $party = $this->normalize($partyState);

        if ($company === '' || $party === '') {
            return null;
        }

        return $company === $party;
    }

    public function percentUnits(string $value): int
    {
        $value = trim($value);

        if (! preg_match('/^\d+(\.\d{1,4})?$/', $value)) {
            throw new InvalidArgumentException('Enter a percentage with up to 4 decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        $fraction = str_pad(substr($fraction, 0, 4), 4, '0');
        $units = ((int) $whole * 10000) + (int) $fraction;

        if ($units > 1000000) {
            throw new InvalidArgumentException('A tax percentage cannot be more than 100.');
        }

        return $units;
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    private function rates(TaxRate $rate): array
    {
        return [
            $this->percentUnits((string) $rate->cgst_rate),
            $this->percentUnits((string) $rate->sgst_rate),
            $this->percentUnits((string) $rate->igst_rate),
            $this->percentUnits((string) $rate->cess_rate),
        ];
    }

    private function breakdown(int $taxable, int $cgst, int $sgst, int $igst, int $cess, bool $intraState): TaxBreakdown
    {
        $tax = $cgst + $sgst + $igst + $cess;

        return new TaxBreakdown(
            taxable: Money::format($taxable),
            cgst: Money::format($cgst),
            sgst: Money::format($sgst),
            igst: Money::format($igst),
            cess: Money::format($cess),
            tax: Money::format($tax),
            total: Money::format($taxable + $tax),
            intraState: $intraState,
        );
    }

    private function applyRounding(int $cents, TaxRounding $rounding): int
    {
        if ($rounding === TaxRounding::Paisa || $cents === 0) {
            return $cents;
        }

        $rupees = intdiv($cents, 100);
        $remainder = $cents % 100;

        if ($remainder >= 50) {
            $rupees++;
        }

        return $rupees * 100;
    }

    /**
     * @param  array<string, int>  $rates
     * @return array<string, int>
     */
    private function split(int $total, array $rates): array
    {
        $sum = array_sum($rates);
        $amounts = [];
        $used = 0;
        $last = null;

        foreach ($rates as $key => $units) {
            if ($units > 0) {
                $last = $key;
            }
        }

        foreach ($rates as $key => $units) {
            if ($units === 0 || $key === $last || $sum === 0) {
                $amounts[$key] = 0;

                continue;
            }

            $share = $this->proportion($total, $units, $sum);
            $amounts[$key] = $share;
            $used += $share;
        }

        if ($last !== null) {
            $amounts[$last] = $total - $used;
        }

        return $amounts;
    }

    private function proportion(int $amount, int $numerator, int $denominator): int
    {
        if ($amount === 0 || $numerator === 0 || $denominator === 0) {
            return 0;
        }

        $whole = intdiv($amount, $denominator) * $numerator;
        $remainder = $amount % $denominator;
        $product = $remainder * $numerator;
        $cents = $whole + intdiv($product, $denominator);

        if (($product % $denominator) * 2 >= $denominator) {
            $cents++;
        }

        return $cents;
    }

    private function taxCents(int $taxableCents, int $rateUnits): int
    {
        if ($taxableCents === 0 || $rateUnits === 0) {
            return 0;
        }

        $high = intdiv($taxableCents, 1000000);
        $low = $taxableCents % 1000000;
        $fromHigh = $high * $rateUnits;
        $productLow = $low * $rateUnits;
        $cents = $fromHigh + intdiv($productLow, 1000000);
        $remainder = $productLow % 1000000;

        if ($remainder >= 500000) {
            $cents++;
        }

        return $cents;
    }

    private function normalize(?string $state): string
    {
        $state = preg_replace('/\s+/', ' ', trim((string) $state)) ?? '';

        return mb_strtolower($state);
    }
}
