<?php

namespace Tally\Invoicing;

use Tally\Accounting\Money;
use Tally\Models\Invoice;
use Illuminate\Validation\ValidationException;

/**
 * Builds the voucher lines for an invoice.
 *
 * Sales:       Customer Dr, Sales Cr
 * Advance:     Customer Dr, Customer advances Cr
 * Credit note: Customer Cr, Sales Dr
 * Purchase:    Purchase Dr, Supplier Cr
 * Debit note:  Purchase Cr, Supplier Dr
 *
 * Retention (sales): Customer Dr full; sales reduced; retention liability Cr.
 * Placeholder tax is included in the sales or purchase amount.
 */
class InvoiceAccounting
{
    public function __construct(private readonly TaxCalculator $taxes) {}

    /**
     * @return list<array{ledger_id: int, debit: string, credit: string, narration: ?string, reference: string}>
     */
    public function entries(Invoice $invoice): array
    {
        $grand = Money::cents((string) $invoice->grand_total);
        $taxLines = $this->taxLines($invoice);
        $taxCents = 0;

        foreach ($taxLines as $line) {
            $taxCents += Money::cents($line['debit']) + Money::cents($line['credit']);
        }

        if ($taxCents > $grand) {
            throw ValidationException::withMessages([
                'lines' => 'Tax cannot exceed the invoice total.',
            ]);
        }

        $accountCents = $grand - $taxCents;
        $debitParty = $invoice->kind->debitsParty();
        $narration = $invoice->narration;
        $reference = $invoice->invoice_number;

        $accountLedgerId = (int) $invoice->account_ledger_id;
        if ($invoice->billing_mode === 'advance' && $invoice->kind === InvoiceKind::Sales) {
            $advanceId = $invoice->company?->customer_advance_ledger_id;
            if (! $advanceId) {
                throw ValidationException::withMessages([
                    'billing_mode' => 'Set a customer advance ledger on the company before posting an advance invoice.',
                ]);
            }
            $accountLedgerId = (int) $advanceId;
        }

        $retention = Money::cents((string) ($invoice->retention_amount ?? '0'));
        if ($retention > 0 && $invoice->kind === InvoiceKind::Sales && $debitParty) {
            $retentionLedgerId = $invoice->company?->retention_ledger_id;
            if (! $retentionLedgerId) {
                throw ValidationException::withMessages([
                    'retention_percent' => 'Set a retention ledger on the company before posting retention.',
                ]);
            }
            if ($retention >= $accountCents) {
                throw ValidationException::withMessages([
                    'retention_percent' => 'Retention cannot exceed the taxable invoice amount.',
                ]);
            }

            $party = $this->line($invoice->party_ledger_id, $grand, 0, $narration, $reference);
            $account = $this->line($accountLedgerId, 0, $accountCents - $retention, $narration, $reference);
            $retentionLine = $this->line((int) $retentionLedgerId, 0, $retention, $narration, $reference);

            return array_values(array_filter(
                [$party, $account, $retentionLine, ...$taxLines],
                fn (array $line) => Money::cents($line['debit']) > 0 || Money::cents($line['credit']) > 0,
            ));
        }

        $party = $this->line($invoice->party_ledger_id, $debitParty ? $grand : 0, $debitParty ? 0 : $grand, $narration, $reference);
        $account = $this->line($accountLedgerId, $debitParty ? 0 : $accountCents, $debitParty ? $accountCents : 0, $narration, $reference);

        $lines = $debitParty
            ? [$party, $account, ...$taxLines]
            : [$account, $party, ...$taxLines];

        return array_values(array_filter(
            $lines,
            fn (array $line) => Money::cents($line['debit']) > 0 || Money::cents($line['credit']) > 0,
        ));
    }

    /**
     * @return list<array{ledger_id: int, debit: string, credit: string, narration: ?string, reference: string}>
     */
    private function taxLines(Invoice $invoice): array
    {
        $lines = [];

        foreach ($this->taxes->ledgerLines($invoice) as $line) {
            $lines[] = $this->line(
                (int) $line['ledger_id'],
                Money::cents($line['debit'] ?? 0),
                Money::cents($line['credit'] ?? 0),
                $invoice->narration,
                $invoice->invoice_number,
            );
        }

        return $lines;
    }

    /**
     * @return array{ledger_id: int, debit: string, credit: string, narration: ?string, reference: string}
     */
    private function line(int $ledgerId, int $debit, int $credit, ?string $narration, string $reference): array
    {
        return [
            'ledger_id' => $ledgerId,
            'debit' => Money::format($debit),
            'credit' => Money::format($credit),
            'narration' => $narration,
            'reference' => $reference,
        ];
    }
}
