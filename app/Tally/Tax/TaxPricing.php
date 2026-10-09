<?php

namespace Tally\Tax;

enum TaxPricing: string
{
    case Exclusive = 'exclusive';
    case Inclusive = 'inclusive';

    public function label(): string
    {
        return match ($this) {
            self::Exclusive => 'Tax exclusive',
            self::Inclusive => 'Tax inclusive',
        };
    }
}
