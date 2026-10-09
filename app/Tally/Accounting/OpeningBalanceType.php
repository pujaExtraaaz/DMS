<?php

namespace Tally\Accounting;

enum OpeningBalanceType: string
{
    case Debit = 'debit';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Debit => 'Dr',
            self::Credit => 'Cr',
        };
    }
}
