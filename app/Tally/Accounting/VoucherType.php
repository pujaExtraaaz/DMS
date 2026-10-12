<?php

namespace Tally\Accounting;

enum VoucherType: string
{
    case Journal = 'journal';
    case Adjustment = 'adjustment';
    case Payment = 'payment';
    case Receipt = 'receipt';
    case Contra = 'contra';
    case Transfer = 'transfer';
    case Sales = 'sales';
    case Purchase = 'purchase';
    case CreditNote = 'credit_note';
    case DebitNote = 'debit_note';

    public function label(): string
    {
        return match ($this) {
            self::Journal => 'Journal',
            self::Adjustment => 'Adjustment',
            self::Payment => 'Payment',
            self::Receipt => 'Receipt',
            self::Contra => 'Contra',
            self::Transfer => 'Transfer',
            self::Sales => 'Sales',
            self::Purchase => 'Purchase',
            self::CreditNote => 'Credit Note',
            self::DebitNote => 'Debit Note',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Journal => 'JRN',
            self::Adjustment => 'ADJ',
            self::Payment => 'PAY',
            self::Receipt => 'REC',
            self::Contra => 'CON',
            self::Transfer => 'TRF',
            self::Sales => 'SAL',
            self::Purchase => 'PUR',
            self::CreditNote => 'CRN',
            self::DebitNote => 'DBN',
        };
    }
}
