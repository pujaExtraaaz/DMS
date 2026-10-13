<?php

namespace Tally\Purchasing;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\PurchaseOrder;
use Tally\Models\RequestForQuotation;
use Tally\Models\VendorQuotation;

class RfqService
{
    public function __construct(
        private readonly PurchaseOrderService $purchaseOrders,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, Branch $branch, FinancialYear $year, User $user, array $input, ?RequestForQuotation $rfq = null): RequestForQuotation
    {
        return DB::transaction(function () use ($company, $branch, $year, $user, $input, $rfq) {
            $payload = [
                'rfq_date' => (string) ($input['rfq_date'] ?? now()->toDateString()),
                'due_date' => ($input['due_date'] ?? null) ?: null,
                'purchase_requisition_id' => ($input['purchase_requisition_id'] ?? null) ?: null,
                'status' => ($input['status'] ?? null) ?: ($rfq?->status ?? 'draft'),
                'narration' => ($input['narration'] ?? null) ?: null,
            ];

            if ($rfq) {
                $rfq->update($payload);
                $rfq->lines()->delete();
            } else {
                $count = RequestForQuotation::query()
                    ->where('company_id', $company->id)
                    ->where('financial_year_id', $year->id)
                    ->count() + 1;
                $rfq = RequestForQuotation::query()->create($payload + [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'financial_year_id' => $year->id,
                    'number' => 'RFQ-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT),
                    'created_by' => $user->id,
                ]);
            }

            $lineNumber = 1;
            foreach ($input['lines'] ?? [] as $line) {
                if (! is_array($line) || trim((string) ($line['item_name'] ?? '')) === '') {
                    continue;
                }
                $rfq->lines()->create([
                    'line_number' => $lineNumber++,
                    'product_id' => ($line['product_id'] ?? null) ?: null,
                    'item_name' => trim((string) $line['item_name']),
                    'quantity' => $line['quantity'] ?? 1,
                    'unit' => ($line['unit'] ?? null) ?: null,
                ]);
            }

            return $rfq->fresh(['lines', 'vendorQuotations']);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function saveVendorQuote(Company $company, RequestForQuotation $rfq, array $input): VendorQuotation
    {
        abort_unless($rfq->company_id === $company->id, 404);

        return DB::transaction(function () use ($company, $rfq, $input) {
            $lines = [];
            $subtotal = 0.0;
            $taxTotal = 0.0;
            $lineNumber = 1;

            foreach ($input['lines'] ?? [] as $line) {
                if (! is_array($line) || trim((string) ($line['item_name'] ?? '')) === '') {
                    continue;
                }
                $qty = (float) ($line['quantity'] ?? 1);
                $rate = (float) ($line['rate'] ?? 0);
                $taxRate = (float) ($line['tax_rate'] ?? 0);
                $base = round($qty * $rate, 2);
                $tax = round($base * $taxRate / 100, 2);
                $lineTotal = round($base + $tax, 2);
                $subtotal += $base;
                $taxTotal += $tax;
                $lines[] = [
                    'line_number' => $lineNumber++,
                    'product_id' => ($line['product_id'] ?? null) ?: null,
                    'item_name' => trim((string) $line['item_name']),
                    'quantity' => $qty,
                    'rate' => $rate,
                    'tax_rate' => $taxRate,
                    'line_total' => $lineTotal,
                ];
            }

            $quote = VendorQuotation::query()->updateOrCreate(
                [
                    'request_for_quotation_id' => $rfq->id,
                    'supplier_ledger_id' => (int) ($input['supplier_ledger_id'] ?? 0),
                ],
                [
                    'company_id' => $company->id,
                    'quote_date' => (string) ($input['quote_date'] ?? now()->toDateString()),
                    'valid_until' => ($input['valid_until'] ?? null) ?: null,
                    'subtotal' => round($subtotal, 2),
                    'tax_total' => round($taxTotal, 2),
                    'grand_total' => round($subtotal + $taxTotal, 2),
                    'lead_days' => (int) ($input['lead_days'] ?? 0),
                    'payment_terms_note' => ($input['payment_terms_note'] ?? null) ?: null,
                    'status' => ($input['status'] ?? null) ?: 'received',
                    'notes' => ($input['notes'] ?? null) ?: null,
                ]
            );

            $quote->lines()->delete();
            $quote->lines()->createMany($lines);

            if ($rfq->status === 'draft') {
                $rfq->update(['status' => 'open']);
            }

            return $quote->fresh(['lines', 'supplier']);
        });
    }

    public function selectWinner(Company $company, RequestForQuotation $rfq, VendorQuotation $quote): VendorQuotation
    {
        abort_unless($rfq->company_id === $company->id && $quote->company_id === $company->id, 404);
        abort_unless($quote->request_for_quotation_id === $rfq->id, 404);

        return DB::transaction(function () use ($rfq, $quote) {
            VendorQuotation::query()
                ->where('request_for_quotation_id', $rfq->id)
                ->update(['is_selected' => false]);

            $quote->update(['is_selected' => true, 'status' => 'selected']);
            $rfq->update(['status' => 'awarded']);

            return $quote->fresh(['lines', 'supplier']);
        });
    }

    public function convertToPurchaseOrder(
        Company $company,
        Branch $branch,
        FinancialYear $year,
        User $user,
        RequestForQuotation $rfq,
        ?VendorQuotation $quote = null,
    ): PurchaseOrder {
        abort_unless($rfq->company_id === $company->id, 404);

        $quote ??= VendorQuotation::query()
            ->where('request_for_quotation_id', $rfq->id)
            ->where('is_selected', true)
            ->first();

        if (! $quote || $quote->request_for_quotation_id !== $rfq->id) {
            throw ValidationException::withMessages([
                'vendor_quotation' => 'Select a vendor quotation before converting to a purchase order.',
            ]);
        }

        $quote->loadMissing('lines');

        if ($quote->lines->isEmpty()) {
            throw ValidationException::withMessages([
                'vendor_quotation' => 'The selected vendor quotation has no lines.',
            ]);
        }

        $today = now()->toDateString();
        $orderDate = ($today >= $year->start_date->toDateString() && $today <= $year->end_date->toDateString())
            ? $today
            : $year->start_date->toDateString();

        $order = $this->purchaseOrders->save($company, $branch, $year, $user, [
            'order_date' => $orderDate,
            'supplier_ledger_id' => $quote->supplier_ledger_id,
            'reference_number' => $rfq->number,
            'narration' => trim('Converted from '.$rfq->number.($rfq->narration ? ' — '.$rfq->narration : '')),
            'purchase_requisition_id' => $rfq->purchase_requisition_id,
            'vendor_quotation_id' => $quote->id,
            'lines' => $quote->lines->map(fn ($line) => [
                'item_name' => $line->item_name,
                'product_id' => $line->product_id,
                'quantity' => number_format((float) $line->quantity, 4, '.', ''),
                'rate' => number_format((float) $line->rate, 2, '.', ''),
                'discount' => '0.00',
                'tax_amount' => number_format(
                    max(0, (float) $line->line_total - ((float) $line->quantity * (float) $line->rate)),
                    2,
                    '.',
                    ''
                ),
            ])->all(),
        ]);

        if ($rfq->status !== 'cancelled') {
            $rfq->update(['status' => 'awarded']);
        }

        return $order;
    }
}
