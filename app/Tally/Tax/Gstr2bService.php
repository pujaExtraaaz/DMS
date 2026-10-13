<?php

namespace Tally\Tax;

use Tally\Invoicing\InvoiceKind;
use Tally\Models\Company;
use Tally\Models\Gstr2bRow;
use Tally\Models\Invoice;

class Gstr2bService
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<Gstr2bRow>
     */
    public function import(Company $company, string $returnPeriod, array $rows): array
    {
        $created = [];

        foreach ($rows as $row) {
            if (blank($row['invoice_number'] ?? null)) {
                continue;
            }

            $created[] = Gstr2bRow::query()->create([
                'company_id' => $company->id,
                'return_period' => $returnPeriod,
                'supplier_gstin' => isset($row['supplier_gstin']) ? strtoupper(trim((string) $row['supplier_gstin'])) : null,
                'supplier_name' => $row['supplier_name'] ?? null,
                'invoice_number' => trim((string) $row['invoice_number']),
                'invoice_date' => $row['invoice_date'] ?? null,
                'taxable_value' => $row['taxable_value'] ?? 0,
                'igst' => $row['igst'] ?? 0,
                'cgst' => $row['cgst'] ?? 0,
                'sgst' => $row['sgst'] ?? 0,
                'cess' => $row['cess'] ?? 0,
                'match_status' => 'unmatched',
            ]);
        }

        return $created;
    }

    /**
     * Match GSTR-2B rows to purchase invoices by GSTIN + number + amount within tolerance.
     *
     * @return array{matched: int, partial: int, missing: int}
     */
    public function match(Company $company, ?string $returnPeriod = null, float $tolerance = 1.0): array
    {
        $query = Gstr2bRow::query()
            ->where('company_id', $company->id)
            ->whereIn('match_status', ['unmatched', 'partial', 'missing_in_books']);

        if ($returnPeriod) {
            $query->where('return_period', $returnPeriod);
        }

        $rows = $query->get();
        $invoices = Invoice::query()
            ->with('party')
            ->where('company_id', $company->id)
            ->where('kind', InvoiceKind::Purchase)
            ->get();

        $stats = ['matched' => 0, 'partial' => 0, 'missing' => 0];

        foreach ($rows as $row) {
            $candidates = $invoices->filter(function (Invoice $invoice) use ($row) {
                $gstin = strtoupper(trim((string) ($invoice->party?->gstin ?? '')));
                $rowGstin = strtoupper(trim((string) ($row->supplier_gstin ?? '')));
                $numberMatch = strcasecmp(trim((string) $invoice->invoice_number), trim((string) $row->invoice_number)) === 0;
                $gstinMatch = $rowGstin === '' || $gstin === $rowGstin;

                return $numberMatch && $gstinMatch;
            });

            if ($candidates->isEmpty()) {
                $row->update([
                    'matched_invoice_id' => null,
                    'match_status' => 'missing_in_books',
                ]);
                $stats['missing']++;
                continue;
            }

            $rowTotal = (float) $row->taxable_value
                + (float) $row->igst
                + (float) $row->cgst
                + (float) $row->sgst
                + (float) $row->cess;

            $best = null;
            $bestDiff = null;

            foreach ($candidates as $invoice) {
                $diff = abs((float) $invoice->grand_total - $rowTotal);
                if ($bestDiff === null || $diff < $bestDiff) {
                    $best = $invoice;
                    $bestDiff = $diff;
                }
            }

            if ($best !== null && $bestDiff !== null && $bestDiff <= $tolerance) {
                $row->update([
                    'matched_invoice_id' => $best->id,
                    'match_status' => 'matched',
                ]);
                $stats['matched']++;
            } else {
                $row->update([
                    'matched_invoice_id' => $best?->id,
                    'match_status' => 'partial',
                ]);
                $stats['partial']++;
            }
        }

        return $stats;
    }
}
