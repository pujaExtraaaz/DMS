<?php

namespace Tally\Inventory;

enum StockTransactionType: string
{
    case In = 'in';
    case Out = 'out';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';
    case RejectionIn = 'rejection_in';
    case RejectionOut = 'rejection_out';
    case Physical = 'physical';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Receipt Note',
            self::Out => 'Delivery Note',
            self::Transfer => 'Stock transfer',
            self::Adjustment => 'Stock Journal',
            self::RejectionIn => 'Rejections In',
            self::RejectionOut => 'Rejections Out',
            self::Physical => 'Physical Stock',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::In => 'STI',
            self::Out => 'STO',
            self::Transfer => 'STF',
            self::Adjustment => 'STA',
            self::RejectionIn => 'RJI',
            self::RejectionOut => 'RJO',
            self::Physical => 'PHY',
        };
    }

    public function routeName(string $action): string
    {
        return 'stock.'.$this->value.'.'.$action;
    }
}