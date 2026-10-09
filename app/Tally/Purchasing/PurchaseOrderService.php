<?php

namespace Tally\Purchasing;

use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\InvoiceMath;
use Tally\Invoicing\InvoiceTax;
use Tally\Invoicing\PartyDirectory;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Accounting\Money;
use Tally\Models\Ledger;
use Tally\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A purchase order is a document only. It does not move stock or post a voucher.
 * Line totals use the same invoice maths and tax split as a purchase invoice.
 */
class PurchaseOrderService
{
    public function __construct(
        private readonly InvoiceMath $math,
        private readonly InvoiceTax $tax,
        private readonly PartyDirectory $parties,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, Branch $branch, FinancialYear $year, User $user, array $input): PurchaseOrder
    {
        return DB::transaction(function () use ($company, $branch, $year, $user, $input) {
            $date = $this->date($year, (string) ($input['order_date'] ?? ''));
            $supplier = $this->parties->find($company, InvoiceKind::Purchase, (int) ($input['supplier_ledger_id'] ?? 0));
            $built = $this->tax->apply($company, $supplier, $this->math->compile($this->percentDiscounts($input['lines'] ?? []), true));
            $delivery = $this->charge($input['delivery_charge'] ?? '0');
            $purchaseLedger = $this->purchaseLedger($company, $input['purchase_ledger_id'] ?? null);
            $count = PurchaseOrder::query()
                ->where('company_id', $company->id)
                ->where('financial_year_id', $year->id)
                ->count() + 1;

            $order = PurchaseOrder::query()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'financial_year_id' => $year->id,
                'number' => 'PO-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT),
                'order_date' => $date,
                'supplier_ledger_id' => $supplier->id,
                'purchase_ledger_id' => $purchaseLedger?->id,
                'reference_number' => $this->blank($input['reference_number'] ?? null),
                'narration' => $this->blank($input['narration'] ?? null),
                'subtotal' => $built['subtotal'],
                'discount_total' => $built['discount_total'],
                'delivery_charge' => Money::format($delivery),
                'tax_total' => $built['tax_total'],
                'grand_total' => Money::format(Money::cents($built['grand_total']) + $delivery),
                'status' => 'open',
                'created_by' => $user->id,
            ]);

            $order->lines()->createMany(array_map(fn (array $line): array => [
                'line_number' => $line['line_number'],
                'item_name' => $line['item_name'],
                'product_id' => $line['product_id'] ?? null,
                'tax_rate_id' => $line['tax_rate_id'] ?? null,
                'hsn_sac_id' => $line['hsn_sac_id'] ?? null,
                'quantity' => $line['quantity'],
                'rate' => $line['rate'],
                'discount' => $line['discount'],
                'taxable_amount' => $line['taxable_amount'],
                'tax_amount' => $line['tax_amount'],
                'cgst_amount' => $line['cgst_amount'] ?? '0.00',
                'sgst_amount' => $line['sgst_amount'] ?? '0.00',
                'igst_amount' => $line['igst_amount'] ?? '0.00',
                'cess_amount' => $line['cess_amount'] ?? '0.00',
                'line_total' => $line['line_total'],
            ], $built['lines']));

            return $order->fresh(['lines.product', 'lines.hsnSac', 'supplier', 'company', 'branch']);
        });
    }

    private function date(FinancialYear $year, string $value): string
    {
        if ($value === '' || ! strtotime($value)) {
            throw ValidationException::withMessages([
                'order_date' => 'Enter an order date.',
            ]);
        }

        $date = date('Y-m-d', strtotime($value));

        if ($date < $year->start_date->toDateString() || $date > $year->end_date->toDateString()) {
            throw ValidationException::withMessages([
                'order_date' => 'The order date must fall in '.$year->name.'.',
            ]);
        }

        return $date;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    private function percentDiscounts(array $lines): array
    {
        foreach ($lines as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $percent = trim((string) ($line['discount_percent'] ?? ''));

            if ($percent === '') {
                continue;
            }

            $gross = (float) ($line['quantity'] ?? 0) * (float) ($line['rate'] ?? 0);
            $lines[$index]['discount'] = number_format($gross * ((float) $percent) / 100, 2, '.', '');
        }

        return $lines;
    }

    private function charge(mixed $value): int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        return Money::cents($value);
    }

    private function purchaseLedger(Company $company, mixed $id): ?Ledger
    {
        if ($id === null || $id === '') {
            return null;
        }

        $ledger = Ledger::query()->where('company_id', $company->id)->find((int) $id);

        if (! $ledger) {
            throw ValidationException::withMessages([
                'purchase_ledger_id' => 'Choose a purchase ledger from this company.',
            ]);
        }

        return $ledger;
    }

    private function blank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
