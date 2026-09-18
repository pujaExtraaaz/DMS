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
    ): array {
        $company = $customer->company_id
            ? Company::find($customer->company_id)
            : Company::query()->first();

        $basis = $basisOverride
            ?: ($company?->due_date_basis ?? 'invoice_date');

        $creditDays = $creditDaysOverride
            ?? (int) ($customer->credit_days ?? 0);

        $source = $basis === 'inward_date' && $inwardDate
            ? Carbon::parse($inwardDate)
            : Carbon::parse($invoiceDate);

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
    ): array {
        $companyId = $supplier?->company_id ?? auth()->user()?->company_id;
        $company = $companyId ? Company::find($companyId) : Company::query()->first();

        $basis = $basisOverride
            ?: ($company?->due_date_basis ?? 'invoice_date');

        $creditDays = $creditDaysOverride
            ?? (int) ($supplier?->credit_days ?? 0);

        $source = $basis === 'inward_date' && $inwardDate
            ? Carbon::parse($inwardDate)
            : Carbon::parse($invoiceDate);

        return [
            'due_date' => $source->copy()->addDays($creditDays)->toDateString(),
            'due_date_basis' => $basis,
            'due_date_source_date' => $source->toDateString(),
            'credit_days' => $creditDays,
        ];
    }
}
