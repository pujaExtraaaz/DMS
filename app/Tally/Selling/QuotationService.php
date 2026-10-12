<?php

namespace Tally\Selling;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\InvoiceMath;
use Tally\Invoicing\InvoiceTax;
use Tally\Invoicing\PartyDirectory;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Quotation;
use Tally\Models\SalesOrder;

class QuotationService
{
    public function __construct(
        private readonly InvoiceMath $math,
        private readonly InvoiceTax $tax,
        private readonly PartyDirectory $parties,
        private readonly SalesOrderService $orders,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, Branch $branch, FinancialYear $year, User $user, array $input, ?Quotation $quote = null): Quotation
    {
        return DB::transaction(function () use ($company, $branch, $year, $user, $input, $quote) {
            if ($quote && in_array($quote->status, ['cancelled', 'expired'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'This quotation cannot be changed.',
                ]);
            }

            $customer = $this->parties->find($company, InvoiceKind::Sales, (int) ($input['customer_ledger_id'] ?? 0));
            $built = $this->tax->apply($company, $customer, $this->math->compile($input['lines'] ?? [], true));
            $date = (string) ($input['quote_date'] ?? now()->toDateString());

            $payload = [
                'quote_date' => $date,
                'valid_until' => ($input['valid_until'] ?? null) ?: null,
                'customer_ledger_id' => $customer->id,
                'billing_address_id' => ($input['billing_address_id'] ?? null) ?: null,
                'shipping_address_id' => ($input['shipping_address_id'] ?? null) ?: null,
                'reference_number' => ($input['reference_number'] ?? null) ?: null,
                'narration' => ($input['narration'] ?? null) ?: null,
                'status' => ($input['status'] ?? null) ?: ($quote?->status ?? 'draft'),
                'subtotal' => $built['subtotal'],
                'discount_total' => $built['discount_total'],
                'tax_total' => $built['tax_total'],
                'grand_total' => $built['grand_total'],
            ];

            if ($quote) {
                $quote->update($payload);
                $quote->lines()->delete();
            } else {
                $count = Quotation::query()->where('company_id', $company->id)->where('financial_year_id', $year->id)->count() + 1;
                $quote = Quotation::query()->create($payload + [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'financial_year_id' => $year->id,
                    'number' => 'QT-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT),
                    'created_by' => $user->id,
                ]);
            }

            $quote->lines()->createMany(array_map(fn (array $line): array => [
                'line_number' => $line['line_number'],
                'item_name' => $line['item_name'],
                'product_id' => $line['product_id'] ?? null,
                'hsn_sac' => null,
                'quantity' => $line['quantity'],
                'rate' => $line['rate'],
                'discount' => $line['discount'],
                'tax_rate' => 0,
                'tax_amount' => $line['tax_amount'],
                'line_total' => $line['line_total'],
            ], $built['lines']));

            return $quote->fresh(['lines', 'customer']);
        });
    }

    public function convertToSalesOrder(Quotation $quote, User $user): SalesOrder
    {
        if (! $quote->canConvert()) {
            throw ValidationException::withMessages([
                'status' => 'This quotation cannot be converted to a sales order.',
            ]);
        }

        return DB::transaction(function () use ($quote, $user) {
            $quote->loadMissing(['lines', 'company', 'branch', 'financialYear']);
            $order = $this->orders->save(
                $quote->company,
                $quote->branch,
                $quote->financialYear,
                $user,
                [
                    'order_date' => $quote->quote_date->toDateString(),
                    'customer_ledger_id' => $quote->customer_ledger_id,
                    'reference_number' => $quote->number,
                    'narration' => $quote->narration,
                    'lines' => $quote->lines->map(fn ($line) => [
                        'item_name' => $line->item_name,
                        'product_id' => $line->product_id,
                        'quantity' => number_format((float) $line->quantity, 4, '.', ''),
                        'rate' => number_format((float) $line->rate, 2, '.', ''),
                        'discount' => number_format((float) $line->discount, 2, '.', ''),
                        'tax_amount' => number_format((float) $line->tax_amount, 2, '.', ''),
                    ])->all(),
                ]
            );

            $order->update([
                'quotation_id' => $quote->id,
                'billing_address_id' => $quote->billing_address_id,
                'shipping_address_id' => $quote->shipping_address_id,
            ]);

            $quote->update([
                'status' => 'accepted',
                'sales_order_id' => $order->id,
            ]);

            return $order->fresh(['lines', 'customer']);
        });
    }
}
