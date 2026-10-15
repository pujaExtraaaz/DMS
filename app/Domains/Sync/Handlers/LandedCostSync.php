<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Purchasing\Models\LandedCost;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\LandedCostAllocation;
use Tally\Models\LandedCostVoucher;

class LandedCostSync
{
    public const KEY = 'landed_cost';

    public function __construct(
        private readonly PurchaseInvoiceSync $purchaseInvoices,
        private readonly ProductSync $products,
    ) {}

    public function sync(Model $model): void
    {
        if ($model instanceof LandedCost) {
            $this->push($model);
        }
    }

    public function push(LandedCost $cost): void
    {
        $cost->loadMissing(['purchaseInvoice.supplier', 'items.product', 'freightBill']);
        $invoice = $cost->purchaseInvoice;
        if (! $invoice) {
            return;
        }

        $organizationId = (int) ($invoice->supplier?->company_id ?: $invoice->company_id);
        if (! $organizationId) {
            return;
        }

        $company = BooksCompany::forOrganization($organizationId);
        $date = $cost->landed_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId($organizationId) : null;
        if (! $company || ! $year || ! $branchId) {
            return;
        }

        $this->purchaseInvoices->push($invoice);
        $piId = SyncLinks::acctId(PurchaseInvoiceSync::KEY, $invoice);
        $freight = (float) ($cost->freightBill?->total_amount ?? $cost->total_additional_cost ?? 0);

        $books = LandedCostVoucher::query()->find(SyncLinks::acctId(self::KEY, $cost));
        if ($books && $books->status === 'posted') {
            SyncLinks::store(self::KEY, $cost, $books, 'conflict', 'Books landed cost already posted');

            return;
        }

        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'number' => mb_substr((string) $cost->landed_no, 0, 30),
            'voucher_date' => $date,
            'purchase_invoice_id' => $piId,
            'freight' => $freight,
            'duty' => 0,
            'other_charges' => 0,
            'total_charges' => $cost->total_additional_cost ?? $freight,
            'allocation_basis' => $cost->allocation_method ?: 'value',
            'status' => in_array($cost->status, ['posted', 'completed'], true) ? 'draft' : 'draft',
            'narration' => $cost->notes,
        ];

        $books ? $books->update($attributes) : $books = LandedCostVoucher::query()->create($attributes);
        $books->allocations()->delete();
        foreach ($cost->items as $item) {
            if (! $item->product) {
                continue;
            }
            $this->products->push($item->product);
            $productId = SyncLinks::acctId(ProductSync::KEY, $item->product);
            if (! $productId) {
                continue;
            }
            LandedCostAllocation::query()->create([
                'landed_cost_voucher_id' => $books->id,
                'product_id' => $productId,
                'item_name' => $item->product?->name ?? 'Item',
                'quantity' => $item->quantity ?? 0,
                'base_value' => $item->base_value ?? 0,
                'allocated_charges' => $item->allocated_cost ?? 0,
                'landed_unit_cost' => $item->landed_unit_cost ?? 0,
            ]);
        }
        SyncLinks::store(self::KEY, $cost, $books);
    }
}
