<?php

namespace App\Domains\Sync\Handlers;

use App\Domains\Purchasing\Models\FreightBill;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Database\Eloquent\Model;
use Tally\Models\LandedCostVoucher;

class FreightBillSync
{
    public const KEY = 'freight_bill';

    public function __construct(private readonly PurchaseInvoiceSync $purchaseInvoices) {}

    public function sync(Model $model): void
    {
        if ($model instanceof FreightBill) {
            $this->push($model);
        }
    }

    public function push(FreightBill $bill): void
    {
        $bill->loadMissing(['allocations.allocatable', 'landedCosts.purchaseInvoice']);
        $invoice = $bill->landedCosts->first()?->purchaseInvoice;
        if (! $invoice) {
            foreach ($bill->allocations as $alloc) {
                if ($alloc->allocatable instanceof PurchaseInvoice) {
                    $invoice = $alloc->allocatable;
                    break;
                }
            }
        }
        if (! $invoice) {
            return;
        }

        $invoice->loadMissing('supplier');
        $organizationId = (int) ($invoice->supplier?->company_id ?: $invoice->company_id);
        if (! $organizationId) {
            return;
        }

        $company = BooksCompany::forOrganization($organizationId);
        $date = $bill->bill_date?->toDateString() ?: now()->toDateString();
        $year = $company ? BooksCompany::yearFor($company, $date) : null;
        $branchId = $company ? BooksCompany::branchId($organizationId) : null;
        if (! $company || ! $year || ! $branchId) {
            return;
        }

        $this->purchaseInvoices->push($invoice);
        $piId = SyncLinks::acctId(PurchaseInvoiceSync::KEY, $invoice);

        $books = LandedCostVoucher::query()->find(SyncLinks::acctId(self::KEY, $bill));
        $status = in_array($bill->status, ['posted', 'allocated', 'completed'], true) ? 'draft' : 'draft';
        $attributes = [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'financial_year_id' => $year->id,
            'number' => mb_substr((string) $bill->freight_no, 0, 30),
            'voucher_date' => $date,
            'purchase_invoice_id' => $piId,
            'freight' => $bill->amount ?? 0,
            'duty' => 0,
            'other_charges' => $bill->tax_amount ?? 0,
            'total_charges' => $bill->total_amount ?? 0,
            'allocation_basis' => $bill->allocation_basis ?: 'value',
            'status' => $status,
            'narration' => trim(($bill->transporter_name ?? '').' '.($bill->notes ?? '')),
        ];

        $books ? $books->update($attributes) : $books = LandedCostVoucher::query()->create($attributes);
        SyncLinks::store(self::KEY, $bill, $books);
    }
}
