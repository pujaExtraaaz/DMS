<?php

namespace App\Support;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Company;
use Illuminate\Support\Carbon;

class DueDateService
{
    /**
     * @return array{due_date: string, due_date_basis: string, due_date_source_date: string, credit_days: int}
     */
    public function forSalesInvoice(
        Customer $customer,
        $invoiceDate,
        ?string $basisOverride = null,
        ?int $creditDaysOverride = null,
        $inwardDate = null,
        $receiptDate = null,
    ): array {
        $company = $customer->company_id
            ? Company::find($customer->company_id)
            : Company::query()->first();

        $basis = $this->resolveBasis($customer, $basisOverride, $company);

        $creditDays = $creditDaysOverride
            ?? (int) ($customer->credit_days ?? 0);

        $source = $this->sourceDate($basis, $invoiceDate, $inwardDate, $receiptDate);

        return [
            'due_date' => $source->copy()->addDays($creditDays)->toDateString(),
            'due_date_basis' => $basis,
            'due_date_source_date' => $source->toDateString(),
            'credit_days' => $creditDays,
        ];
    }

    /**
     * @return array{due_date: string, due_date_basis: string, due_date_source_date: string, credit_days: int}
     */
    public function forPurchaseInvoice(
        ?Customer $supplier,
        $invoiceDate,
        ?string $basisOverride = null,
        ?int $creditDaysOverride = null,
        $inwardDate = null,
        $receiptDate = null,
    ): array {
        $companyId = $supplier?->company_id ?? auth()->user()?->company_id;
        $company = $companyId ? Company::find($companyId) : Company::query()->first();

        $basis = $this->resolveBasis($supplier, $basisOverride, $company);

        $creditDays = $creditDaysOverride
            ?? (int) ($supplier?->credit_days ?? 0);

        $source = $this->sourceDate($basis, $invoiceDate, $inwardDate, $receiptDate);

        return [
            'due_date' => $source->copy()->addDays($creditDays)->toDateString(),
            'due_date_basis' => $basis,
            'due_date_source_date' => $source->toDateString(),
            'credit_days' => $creditDays,
        ];
    }

    /**
     * @return array{due_date: string, credit_days: int}
     */
    public function forPurchaseOrder(
        ?Customer $supplier,
        $poDate,
        ?int $creditDaysOverride = null,
    ): array {
        $creditDays = $creditDaysOverride !== null
            ? max(0, (int) $creditDaysOverride)
            : (int) ($supplier?->credit_days ?? 0);

        $source = Carbon::parse($poDate);

        return [
            'due_date' => $source->copy()->addDays($creditDays)->toDateString(),
            'credit_days' => $creditDays,
        ];
    }

    protected function resolveBasis(?Customer $party, ?string $basisOverride, ?Company $company): string
    {
        if ($basisOverride) {
            return $basisOverride;
        }

        $partyBasis = $party?->credit_period_basis;
        if (in_array($partyBasis, ['invoice_date', 'inward_date', 'receipt_date'], true)) {
            return $partyBasis;
        }

        return $company?->due_date_basis ?? 'invoice_date';
    }

    protected function sourceDate(string $basis, $invoiceDate, $inwardDate, $receiptDate): Carbon
    {
        if ($basis === 'inward_date' && $inwardDate) {
            return Carbon::parse($inwardDate);
        }

        if ($basis === 'receipt_date' && $receiptDate) {
            return Carbon::parse($receiptDate);
        }

        return Carbon::parse($invoiceDate);
    }
}
