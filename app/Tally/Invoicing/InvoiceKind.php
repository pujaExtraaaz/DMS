<?php

namespace Tally\Invoicing;

use Tally\Accounting\VoucherType;

enum InvoiceKind: string
{
    case Sales = 'sales';
    case Purchase = 'purchase';
    case CreditNote = 'credit_note';
    case DebitNote = 'debit_note';

    public function label(): string
    {
        return match ($this) {
            self::Sales => 'Sales',
            self::Purchase => 'Purchase',
            self::CreditNote => 'Credit Note',
            self::DebitNote => 'Debit Note',
        };
    }

    public function documentLabel(): string
    {
        return match ($this) {
            self::Sales => 'Sales invoice',
            self::Purchase => 'Purchase invoice',
            self::CreditNote => 'Credit note',
            self::DebitNote => 'Debit note',
        };
    }

    public function voucherType(): VoucherType
    {
        return match ($this) {
            self::Sales => VoucherType::Sales,
            self::Purchase => VoucherType::Purchase,
            self::CreditNote => VoucherType::CreditNote,
            self::DebitNote => VoucherType::DebitNote,
        };
    }

    /**
     * Sales and debit notes debit the party. Credit notes and purchases credit the party.
     */
    public function debitsParty(): bool
    {
        return $this === self::Sales || $this === self::DebitNote;
    }

    /**
     * Sales and credit notes move value through cost of goods sold.
     */
    public function usesCostOfGoods(): bool
    {
        return $this === self::Sales || $this === self::CreditNote;
    }

    public function stockLeaves(): bool
    {
        return $this->debitsParty();
    }

    public function slug(): string
    {
        return str_replace('_', '-', $this->value);
    }

    public function postingNote(): string
    {
        return match ($this) {
            self::Sales => 'Customer Dr, Sales Cr',
            self::CreditNote => 'Sales Dr, Customer Cr',
            self::Purchase => 'Purchase Dr, Supplier Cr',
            self::DebitNote => 'Supplier Dr, Purchase Cr',
        };
    }

    public function partyLabel(): string
    {
        return $this->usesCostOfGoods() ? 'Customer' : 'Supplier';
    }

    public function accountLabel(): string
    {
        return $this->usesCostOfGoods() ? 'Sales ledger' : 'Purchase ledger';
    }

    public function referenceLabel(): string
    {
        return $this->usesCostOfGoods() ? 'Reference' : 'Supplier invoice';
    }

    public function partyGroupCode(): string
    {
        return $this->usesCostOfGoods() ? 'DEBTORS' : 'CREDITORS';
    }

    public function accountGroupCode(): string
    {
        return $this->usesCostOfGoods() ? 'SALES' : 'PURCHASE';
    }

    public function missingPartyMessage(): string
    {
        return $this->usesCostOfGoods()
            ? 'No customer ledgers yet. Create an active ledger under Sundry Debtors.'
            : 'No supplier ledgers yet. Create an active ledger under Sundry Creditors.';
    }

    public function missingAccountMessage(): string
    {
        return $this->usesCostOfGoods()
            ? 'No sales ledgers yet. Create an active ledger under Sales Accounts.'
            : 'No purchase ledgers yet. Create an active ledger under Purchase Accounts.';
    }

    public function partyError(): string
    {
        return $this->usesCostOfGoods()
            ? 'Select an active customer ledger under Sundry Debtors for this company.'
            : 'Select an active supplier ledger under Sundry Creditors for this company.';
    }

    public function accountError(): string
    {
        return $this->usesCostOfGoods()
            ? 'Select an active sales ledger under Sales Accounts for this company.'
            : 'Select an active purchase ledger under Purchase Accounts for this company.';
    }

    public function routeName(string $action): string
    {
        return 'invoices.'.$this->slug().'.'.$action;
    }
}
