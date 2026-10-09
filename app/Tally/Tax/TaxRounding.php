<?php

namespace Tally\Tax;

enum TaxRounding: string
{
    case Paisa = 'paisa';
    case Rupee = 'rupee';

    public function label(): string
    {
        return match ($this) {
            self::Paisa => 'Nearest paisa',
            self::Rupee => 'Nearest rupee',
        };
    }
}
