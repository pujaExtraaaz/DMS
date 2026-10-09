<?php

namespace Tally\Tax;

enum PartyRole: string
{
    case Any = 'any';
    case Customer = 'customer';
    case Supplier = 'supplier';

    public function label(): string
    {
        return match ($this) {
            self::Any => 'Any party',
            self::Customer => 'Customers',
            self::Supplier => 'Suppliers',
        };
    }
}
