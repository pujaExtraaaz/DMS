<?php

namespace Tally\Tax;

final class TaxBreakdown
{
    public function __construct(
        public readonly string $taxable,
        public readonly string $cgst,
        public readonly string $sgst,
        public readonly string $igst,
        public readonly string $cess,
        public readonly string $tax,
        public readonly string $total,
        public readonly bool $intraState,
    ) {}
}
