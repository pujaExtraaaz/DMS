<?php

namespace Tally\Selling;

use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\InvoiceMath;
use Tally\Invoicing\InvoiceTax;
use Tally\Invoicing\PartyDirectory;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Inventory\StockMovementService;
use Tally\Models\Invoice;
use Tally\Models\Product;
use Tally\Models\SalesOrder;
use Tally\Models\SalesOrderLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A sales order is a document only. It does not move stock or post a voucher.
 */
class SalesOrderService
{
    public function __construct(
        private readonly InvoiceMath $math,
        private readonly InvoiceTax $tax,
        private readonly PartyDirectory $parties,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, Branch $branch, FinancialYear $year, User $user, array $input, ?SalesOrder $order = null): SalesOrder
    {
        return DB::transaction(function () use ($company, $branch, $year, $user, $input, $order) {
            if ($order && $order->status === 'fulfilled') {
                throw ValidationException::withMessages([
                    'status' => 'A fulfilled sales order cannot be changed.',
                ]);
            }

            $date = $this->date($year, (string) ($input['order_date'] ?? ''));
            $customer = $this->parties->find($company, InvoiceKind::Sales, (int) ($input['customer_ledger_id'] ?? 0));
            $built = $this->tax->apply($company, $customer, $this->math->compile($input['lines'] ?? [], true));

            $payload = [
                'order_date' => $date,
                'customer_ledger_id' => $customer->id,
                'reference_number' => $this->blank($input['reference_number'] ?? null),
                'narration' => $this->blank($input['narration'] ?? null),
                'subtotal' => $built['subtotal'],
                'discount_total' => $built['discount_total'],
                'tax_total' => $built['tax_total'],
                'grand_total' => $built['grand_total'],
            ];

            if ($order) {
                $order->update($payload);
                $order->lines()->delete();
            } else {
                $count = SalesOrder::query()->where('company_id', $company->id)->where('financial_year_id', $year->id)->count() + 1;
                $order = SalesOrder::query()->create($payload + [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'financial_year_id' => $year->id,
                    'number' => 'SO-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT),
                    'status' => 'pending',
                    'created_by' => $user->id,
                ]);
            }

            $this->reserve($company, $built['lines'], $order);

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

            return $order->fresh(['lines.product', 'customer']);
        });
    }

    public function fulfil(Invoice $invoice): void
    {
        if (! $invoice->sales_order_id) {
            return;
        }

        $order = SalesOrder::query()->whereKey($invoice->sales_order_id)->lockForUpdate()->first();

        if (! $order || $order->company_id !== $invoice->company_id) {
            throw ValidationException::withMessages(['sales_order_id' => 'The sales order is not in this company.']);
        }

        $order->load('lines');

        foreach ($invoice->lines as $line) {
            $match = $order->lines->first(function (SalesOrderLine $row) use ($line) {
                $open = (float) $row->quantity - (float) $row->fulfilled_quantity - (float) $row->cancelled_quantity;

                if ($open <= 0) {
                    return false;
                }

                if ($line->product_id && (int) $row->product_id === (int) $line->product_id) {
                    return true;
                }

                return strcasecmp((string) $row->item_name, (string) $line->item_name) === 0;
            });

            if (! $match) {
                throw ValidationException::withMessages(['lines' => $line->item_name.' is not open on the sales order.']);
            }

            $open = (float) $match->quantity - (float) $match->fulfilled_quantity - (float) $match->cancelled_quantity;

            if ((float) $line->quantity > $open + 0.0001) {
                throw ValidationException::withMessages(['lines' => $line->item_name.' exceeds the pending order quantity.']);
            }

            $match->fulfilled_quantity = number_format((float) $match->fulfilled_quantity + (float) $line->quantity, 4, '.', '');
            $match->save();
        }

        $order->load('lines');
        $pending = $order->lines->contains(fn (SalesOrderLine $row) => (float) $row->quantity - (float) $row->fulfilled_quantity - (float) $row->cancelled_quantity > 0.0001);
        $order->update(['status' => $pending ? 'pending' : 'fulfilled']);
    }

    public function release(Invoice $invoice): void
    {
        if (! $invoice->sales_order_id) {
            return;
        }

        $order = SalesOrder::query()->whereKey($invoice->sales_order_id)->lockForUpdate()->first();

        if (! $order) {
            return;
        }

        $order->load('lines');

        foreach ($invoice->lines as $line) {
            $match = $order->lines->first(function (SalesOrderLine $row) use ($line) {
                if ($line->product_id && (int) $row->product_id === (int) $line->product_id) {
                    return true;
                }

                return strcasecmp((string) $row->item_name, (string) $line->item_name) === 0;
            });

            if (! $match) {
                continue;
            }

            $next = max(0, (float) $match->fulfilled_quantity - (float) $line->quantity);
            $match->fulfilled_quantity = number_format($next, 4, '.', '');
            $match->save();
        }

        $order->update(['status' => 'pending']);
    }

    public function cancel(SalesOrder $order): void
    {
        $order->load('lines');

        foreach ($order->lines as $line) {
            $open = max(0, (float) $line->quantity - (float) $line->fulfilled_quantity - (float) $line->cancelled_quantity);
            $line->cancelled_quantity = number_format((float) $line->cancelled_quantity + $open, 4, '.', '');
            $line->save();
        }

        $order->update(['status' => 'cancelled']);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function reserve(Company $company, array $lines, ?SalesOrder $except): void
    {
        if ($company->allow_negative_stock) {
            return;
        }

        $stock = app(StockMovementService::class);

        foreach ($lines as $line) {
            if (empty($line['product_id'])) {
                continue;
            }

            $product = Product::query()->where('company_id', $company->id)->find($line['product_id']);

            if (! $product) {
                continue;
            }

            $onHand = (float) $stock->quantity($product, null);
            $reserved = SalesOrderLine::query()
                ->where('product_id', $product->id)
                ->whereHas('order', function ($query) use ($company, $except) {
                    $query->where('company_id', $company->id)->where('status', 'pending');

                    if ($except) {
                        $query->whereKeyNot($except->id);
                    }
                })
                ->get()
                ->sum(fn (SalesOrderLine $row) => (float) $row->quantity - (float) $row->fulfilled_quantity - (float) $row->cancelled_quantity);

            if ($onHand - $reserved + 0.0001 < (float) $line['quantity']) {
                throw ValidationException::withMessages([
                    'lines' => $product->name.' does not have enough free stock. Other sales orders already commit the available quantity.',
                ]);
            }
        }
    }

    private function date(FinancialYear $year, string $value): string
    {
        if ($value === '' || ! strtotime($value)) {
            throw ValidationException::withMessages(['order_date' => 'Enter an order date.']);
        }

        $date = date('Y-m-d', strtotime($value));

        if ($date < $year->start_date->toDateString() || $date > $year->end_date->toDateString()) {
            throw ValidationException::withMessages(['order_date' => 'The order date must fall in '.$year->name.'.']);
        }

        return $date;
    }

    private function blank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
