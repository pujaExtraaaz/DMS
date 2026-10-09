<?php

namespace Tally\Tax;

enum HsnKind: string
{
    case Hsn = 'hsn';
    case Sac = 'sac';

    public function label(): string
    {
        return match ($this) {
            self::Hsn => 'HSN',
            self::Sac => 'SAC',
        };
    }
}
