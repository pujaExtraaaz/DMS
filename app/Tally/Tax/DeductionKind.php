<?php

namespace Tally\Tax;

enum DeductionKind: string
{
    case Tds = 'tds';
    case Tcs = 'tcs';

    public function label(): string
    {
        return match ($this) {
            self::Tds => 'TDS',
            self::Tcs => 'TCS',
        };
    }
}
