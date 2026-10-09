<?php

namespace Tally\Invoicing;

use Tally\Accounting\Money;
use Tally\Models\Invoice;
use Illuminate\Validation\ValidationException;

/**
 * Builds the voucher lines for an invoice.
 *
 * Sales:       Customer Dr, Sales Cr
 * Credit note: Customer Cr, Sales Dr
 * Purchase:    Purchase Dr, Supplier Cr
 * Debit note:  Purchase Cr, Supplier Dr
 *
 * Placeholder tax is included in the sales or purchase amount.
 * When a tax calculator returns ledger lines, that amount is moved off the trading account.
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

        $party = $this->line($invoice->party_ledger_id, $debitParty ? $grand : 0, $debitParty ? 0 : $grand, $narration, $reference);
        $account = $this->line($invoice->account_ledger_id, $debitParty ? 0 : $accountCents, $debitParty ? $accountCents : 0, $narration, $reference);

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
