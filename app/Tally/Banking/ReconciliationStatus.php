<?php

namespace Tally\Banking;

enum ReconciliationStatus: string
{
    case Reconciled = 'reconciled';
    case Unreconciled = 'unreconciled';

    public function label(): string
    {
        return match ($this) {
            self::Reconciled => 'Reconciled',
            self::Unreconciled => 'Unreconciled',
        };
    }
}
