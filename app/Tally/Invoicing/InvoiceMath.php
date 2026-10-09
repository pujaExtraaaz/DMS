<?php

namespace Tally\Invoicing;

use Tally\Accounting\Money;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class InvoiceMath
{
    private const MAX_CENTS = 999999999999999;

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{
     *     lines: list<array<string, mixed>>,
     *     subtotal: string,
     *     discount_total: string,
     *     tax_total: string,
     *     grand_total: string
     * }
     */
    public function compile(array $lines, bool $post): array
    {
        $errors = [];
        $kept = [];
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach (array_values($lines) as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $item = trim((string) ($line['item_name'] ?? ''));
            $quantityRaw = trim((string) ($line['quantity'] ?? ''));
            $rateRaw = trim((string) ($line['rate'] ?? ''));
            $discountRaw = trim((string) ($line['discount'] ?? ''));
            $taxRaw = trim((string) ($line['tax_amount'] ?? ''));
            $productId = $this->nullableId($line['product_id'] ?? null);
            $godownId = $this->nullableId($line['godown_id'] ?? null);
            $taxRateId = $this->nullableId($line['tax_rate_id'] ?? null);
            $hsnId = $this->nullableId($line['hsn_sac_id'] ?? null);

            if ($this->isBlank($item, $quantityRaw, $rateRaw, $discountRaw, $taxRaw) && ! $productId && ! $godownId && ! $taxRateId && ! $hsnId) {
                continue;
            }

            if ($item === '') {
                $errors["lines.$index.item_name"] = 'Enter an item or product.';
            }

            try {
                $quantity = $this->quantityMillis($quantityRaw === '' ? '0' : $quantityRaw);
            } catch (InvalidArgumentException $exception) {
                $errors["lines.$index.quantity"] = $exception->getMessage();

                continue;
            }

            if ($quantity <= 0) {
                $errors["lines.$index.quantity"] = 'Enter a quantity greater than zero.';

                continue;
            }

            try {
                $rate = $this->money($rateRaw === '' ? '0' : $rateRaw, 'rate');
                $discount = $this->money($discountRaw === '' ? '0' : $discountRaw, 'discount');
                $tax = $this->money($taxRaw === '' ? '0' : $taxRaw, 'tax');
            } catch (InvalidArgumentException $exception) {
                $field = str_contains($exception->getMessage(), 'discount') ? 'discount' : (str_contains($exception->getMessage(), 'tax') ? 'tax_amount' : 'rate');
                $errors["lines.$index.$field"] = $exception->getMessage();

                continue;
            }

            $amount = $this->lineAmountCents($quantity, $rate);

            if ($amount > self::MAX_CENTS) {
                $errors["lines.$index.rate"] = 'This line amount is too large.';

                continue;
            }

            if ($discount > $amount) {
                $errors["lines.$index.discount"] = 'Discount cannot exceed the line amount.';

                continue;
            }

            $taxable = $amount - $discount;
            $lineTotal = $taxable + $tax;

            if ($lineTotal > self::MAX_CENTS || $subtotal > self::MAX_CENTS - $amount) {
                $errors['lines'] = 'The invoice total is too large.';

                continue;
            }

            $subtotal += $amount;
            $discountTotal += $discount;
            $taxTotal += $tax;

            $kept[] = [
                'line_number' => count($kept) + 1,
                'item_name' => $item,
                'product_id' => $productId,
                'godown_id' => $godownId,
                'tax_rate_id' => $taxRateId,
                'hsn_sac_id' => $hsnId,
                'quantity' => $this->formatQuantity($quantity),
                'rate' => Money::format($rate),
                'discount' => Money::format($discount),
                'taxable_amount' => Money::format($taxable),
                'tax_amount' => Money::format($tax),
                'cgst_amount' => '0.00',
                'sgst_amount' => '0.00',
                'igst_amount' => '0.00',
                'cess_amount' => '0.00',
                'line_total' => Money::format($lineTotal),
            ];
        }

        $grand = $subtotal - $discountTotal + $taxTotal;

        if ($kept === []) {
            $errors['lines'] = 'Add at least one item.';
        }

        if ($post && $errors === [] && $grand <= 0) {
            $errors['lines'] = 'The invoice total must be greater than zero before it can be posted.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'lines' => $kept,
            'subtotal' => Money::format($subtotal),
            'discount_total' => Money::format($discountTotal),
            'tax_total' => Money::format($taxTotal),
            'grand_total' => Money::format($grand),
        ];
    }

    public function quantityMillis(string $value): int
    {
        if (! preg_match('/^\d+(\.\d{1,4})?$/', $value)) {
            throw new InvalidArgumentException('Enter a quantity with up to 4 decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        $fraction = str_pad(substr($fraction, 0, 4), 4, '0');
        $millis = ((int) $whole * 10000) + (int) $fraction;

        if ($millis > 999999999999) {
            throw new InvalidArgumentException('Enter a smaller quantity.');
        }

        return $millis;
    }

    public function lineAmountCents(int $quantityMillis, int $rateCents): int
    {
        if ($quantityMillis === 0 || $rateCents === 0) {
            return 0;
        }

        if ($rateCents > intdiv(PHP_INT_MAX - 5000, $quantityMillis)) {
            return self::MAX_CENTS + 1;
        }

        return intdiv(($quantityMillis * $rateCents) + 5000, 10000);
    }

    public function formatQuantity(int $millis): string
    {
        return intdiv($millis, 10000).'.'.str_pad((string) ($millis % 10000), 4, '0', STR_PAD_LEFT);
    }

    private function money(string $value, string $field): int
    {
        try {
            $cents = Money::cents($value);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException('Enter a '.$field.' with up to 2 decimal places.');
        }

        if ($cents < 0) {
            throw new InvalidArgumentException('Enter a '.$field.' that is zero or more.');
        }

        return $cents;
    }

    private function nullableId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function isBlank(string $item, string $quantity, string $rate, string $discount, string $tax): bool
    {
        return $item === ''
            && ($quantity === '' || $quantity === '0')
            && ($rate === '' || $rate === '0')
            && ($discount === '' || $discount === '0' || $discount === '0.00')
            && ($tax === '' || $tax === '0' || $tax === '0.00');
    }
}
