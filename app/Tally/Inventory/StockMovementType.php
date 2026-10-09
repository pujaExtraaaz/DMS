<?php

namespace Tally\Inventory;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Stock in',
            self::Out => 'Stock out',
            self::TransferIn => 'Transfer in',
            self::TransferOut => 'Transfer out',
            self::Adjustment => 'Adjustment',
        };
    }

    public function opposite(): self
    {
        return match ($this) {
            self::In => self::Out,
            self::Out => self::In,
            self::TransferIn => self::TransferOut,
            self::TransferOut => self::TransferIn,
            self::Adjustment => self::Adjustment,
        };
    }
}
