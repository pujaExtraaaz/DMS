<?php

namespace Tally\Tax;

enum GstRegistrationType: string
{
    case Regular = 'regular';
    case Composition = 'composition';
    case Unregistered = 'unregistered';
    case Consumer = 'consumer';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Composition => 'Composition',
            self::Unregistered => 'Unregistered',
            self::Consumer => 'Consumer',
        };
    }
}
