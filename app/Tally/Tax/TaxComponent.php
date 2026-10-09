<?php

namespace Tally\Tax;

enum TaxComponent: string
{
    case Cgst = 'cgst';
    case Sgst = 'sgst';
    case Igst = 'igst';
    case Cess = 'cess';

    public function label(): string
    {
        return match ($this) {
            self::Cgst => 'CGST',
            self::Sgst => 'SGST',
            self::Igst => 'IGST',
            self::Cess => 'Cess',
        };
    }
}
