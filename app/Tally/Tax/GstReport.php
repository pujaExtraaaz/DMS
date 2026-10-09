<?php

namespace Tally\Tax;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherStatus;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\Company;
use Tally\Models\Invoice;
use Tally\Models\InvoiceLine;
use Tally\Support\Queries\DateRange;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * GST figures from posted sales and purchase invoices only.
 */
class GstReport
{
    /**
     * @return array<string, mixed>
     */
    public function build(Company $company, string $from, string $to, ?int $branchId, string $search = '', int $page = 1): array
    {
        $like = '%'.addcslashes($search, '%_\\').'%';
        $filtered = Invoice::query()
            ->where('company_id', $company->id)
            ->where('status', VoucherStatus::Posted)
            ->tap(fn ($query) => DateRange::apply($query, 'invoice_date', $from, $to))
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($search !== '', function ($query) use ($like) {
                $query->where(function ($query) use ($like) {
                    $query->where('invoice_number', 'like', $like)
                        ->orWhereHas('party', fn ($party) => $party->where('name', 'like', $like));
                });
            });

        $output = $this->blank();
        $input = $this->blank();
        $byRate = ['output' => [], 'input' => []];
        $byHsn = [];

        $lines = InvoiceLine::query()
            ->join('acct_invoices', 'acct_invoices.id', '=', 'acct_invoice_lines.invoice_id')
            ->leftJoin('acct_tax_rates', 'acct_tax_rates.id', '=', 'acct_invoice_lines.tax_rate_id')
            ->leftJoin('acct_hsn_sacs', 'acct_hsn_sacs.id', '=', 'acct_invoice_lines.hsn_sac_id')
            ->whereIn('acct_invoices.id', (clone $filtered)->select('id'))
            ->selectRaw("acct_invoices.kind as kind, COALESCE(acct_tax_rates.name, 'No tax rate') as rate_name, COALESCE(acct_hsn_sacs.code, '—') as hsn, SUM(acct_invoice_lines.taxable_amount) as taxable, SUM(acct_invoice_lines.cgst_amount) as cgst, SUM(acct_invoice_lines.sgst_amount) as sgst, SUM(acct_invoice_lines.igst_amount) as igst, SUM(acct_invoice_lines.cess_amount) as cess, SUM(acct_invoice_lines.tax_amount) as tax")
            ->groupBy('acct_invoices.kind')
            ->groupByRaw("COALESCE(acct_tax_rates.name, 'No tax rate')")
            ->groupByRaw("COALESCE(acct_hsn_sacs.code, '—')")
            ->get();

        foreach ($lines as $line) {
            $kind = InvoiceKind::tryFrom((string) $line->kind) ?? InvoiceKind::Purchase;
            $bucket = $kind->usesCostOfGoods() ? 'output' : 'input';
            $sign = $kind->debitsParty() === $kind->usesCostOfGoods() ? 1 : -1;
            $row = [
                'taxable' => $sign * $this->cents($line->taxable),
                'cgst' => $sign * $this->cents($line->cgst),
                'sgst' => $sign * $this->cents($line->sgst),
                'igst' => $sign * $this->cents($line->igst),
                'cess' => $sign * $this->cents($line->cess),
                'tax' => $sign * $this->cents($line->tax),
            ];
            $byRate[$bucket][$line->rate_name] ??= $this->blank();
            $this->addBucket($byRate[$bucket][$line->rate_name], $row);
            $byHsn[$line->hsn][$bucket] ??= $this->blank();
            $this->addBucket($byHsn[$line->hsn][$bucket], $row);
            $side = $bucket === 'output' ? $output : $input;
            $this->addBucket($side, $row);

            if ($bucket === 'output') {
                $output = $side;
            } else {
                $input = $side;
            }
        }

        $transactions = (clone $filtered)
            ->with(['party', 'lines'])
            ->orderBy('invoice_date')
            ->orderBy('invoice_number')
            ->paginate(50, ['*'], 'page', max(1, $page))
            ->through(function (Invoice $invoice) {
                $row = $this->blank();

                $sign = $invoice->kind->debitsParty() === $invoice->kind->usesCostOfGoods() ? 1 : -1;

                foreach ($invoice->lines as $line) {
                    $this->addLine($row, $line, $sign);
                }

                return [
                    'invoice' => $invoice,
                    'kind' => $invoice->kind,
                    'party' => $invoice->party?->name ?? '',
                    'taxable' => Money::format($row['taxable']),
                    'cgst' => Money::format($row['cgst']),
                    'sgst' => Money::format($row['sgst']),
                    'igst' => Money::format($row['igst']),
                    'cess' => Money::format($row['cess']),
                    'tax' => Money::format($row['tax']),
                ];
            });

        return [
            'output' => $this->format($output),
            'input' => $this->format($input),
            'net' => Money::format($output['tax'] - $input['tax']),
            'by_rate' => [
                'output' => $this->formatMap($byRate['output']),
                'input' => $this->formatMap($byRate['input']),
            ],
            'by_hsn' => $this->formatHsn($byHsn),
            'transactions' => $transactions,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $transactions
     */
    private function page(array $transactions, int $page): LengthAwarePaginator
    {
        $perPage = 50;
        $page = max(1, $page);

        return new LengthAwarePaginator(
            array_slice($transactions, ($page - 1) * $perPage, $perPage),
            count($transactions),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
    }

    /**
     * @return array{taxable: int, cgst: int, sgst: int, igst: int, cess: int, tax: int}
     */
    private function blank(): array
    {
        return ['taxable' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0, 'cess' => 0, 'tax' => 0];
    }

    /**
     * @param  array{taxable: int, cgst: int, sgst: int, igst: int, cess: int, tax: int}  $bucket
     */
    private function addLine(array &$bucket, InvoiceLine $line, int $sign = 1): void
    {
        $bucket['taxable'] += $sign * Money::cents((string) $line->taxable_amount);
        $bucket['cgst'] += $sign * Money::cents((string) $line->cgst_amount);
        $bucket['sgst'] += $sign * Money::cents((string) $line->sgst_amount);
        $bucket['igst'] += $sign * Money::cents((string) $line->igst_amount);
        $bucket['cess'] += $sign * Money::cents((string) $line->cess_amount);
        $bucket['tax'] += $sign * Money::cents((string) $line->tax_amount);
    }

    private function cents(mixed $value): int
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return 0;
        }

        if (! str_contains($raw, '.')) {
            $raw .= '.00';
        }

        [$whole, $fraction] = explode('.', ltrim($raw, '-'), 2);
        $negative = str_starts_with($raw, '-');
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);

        return Money::cents(($negative ? '-' : '').((int) $whole).'.'.$fraction);
    }

    /**
     * @param  array{taxable: int, cgst: int, sgst: int, igst: int, cess: int, tax: int}  $target
     * @param  array{taxable: int, cgst: int, sgst: int, igst: int, cess: int, tax: int}  $row
     */
    private function addBucket(array &$target, array $row): void
    {
        foreach ($row as $key => $value) {
            $target[$key] += $value;
        }
    }

    /**
     * @param  array{taxable: int, cgst: int, sgst: int, igst: int, cess: int, tax: int}  $row
     * @return array<string, string>
     */
    private function format(array $row): array
    {
        $formatted = [];

        foreach ($row as $key => $value) {
            $formatted[$key] = Money::format($value);
        }

        return $formatted;
    }

    /**
     * @param  array<string, array{taxable: int, cgst: int, sgst: int, igst: int, cess: int, tax: int}>  $rows
     * @return list<array<string, string>>
     */
    private function formatMap(array $rows): array
    {
        $formatted = [];

        foreach ($rows as $name => $row) {
            $formatted[] = ['name' => $name] + $this->format($row);
        }

        return $formatted;
    }

    /**
     * @param  array<string, array<string, array{taxable: int, cgst: int, sgst: int, igst: int, cess: int, tax: int}>>  $rows
     * @return list<array<string, string>>
     */
    private function formatHsn(array $rows): array
    {
        $formatted = [];

        foreach ($rows as $code => $sides) {
            $output = $sides['output'] ?? $this->blank();
            $input = $sides['input'] ?? $this->blank();
            $formatted[] = [
                'code' => $code,
                'output_taxable' => Money::format($output['taxable']),
                'output_cgst' => Money::format($output['cgst']),
                'output_sgst' => Money::format($output['sgst']),
                'output_igst' => Money::format($output['igst']),
                'output_cess' => Money::format($output['cess']),
                'output_tax' => Money::format($output['tax']),
                'input_taxable' => Money::format($input['taxable']),
                'input_cgst' => Money::format($input['cgst']),
                'input_sgst' => Money::format($input['sgst']),
                'input_igst' => Money::format($input['igst']),
                'input_cess' => Money::format($input['cess']),
                'input_tax' => Money::format($input['tax']),
            ];
        }

        return $formatted;
    }
}
