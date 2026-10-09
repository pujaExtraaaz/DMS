<?php

namespace Tally\Tax;

enum SupplyType: string
{
    case Intra = 'intra';
    case Inter = 'inter';

    public function label(): string
    {
        return match ($this) {
            self::Intra => 'Intra-state',
            self::Inter => 'Inter-state',
        };
    }
}
