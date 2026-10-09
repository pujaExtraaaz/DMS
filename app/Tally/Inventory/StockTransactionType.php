<?php

namespace Tally\Inventory;

enum StockTransactionType: string
{
    case In = 'in';
    case Out = 'out';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Receipt Note',
            self::Out => 'Delivery Note',
            self::Transfer => 'Stock transfer',
            self::Adjustment => 'Stock Journal',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::In => 'STI',
            self::Out => 'STO',
            self::Transfer => 'STF',
            self::Adjustment => 'STA',
        };
    }

    public function routeName(string $action): string
    {
        return 'stock.'.$this->value.'.'.$action;
    }
}
