<?php

namespace Tally\Receivables;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\Invoice;
use Tally\Models\Party;
use Tally\Models\SalesCommission;

class CommissionService
{
    public function __construct(private readonly VoucherEngine $vouchers) {}

    public function accrueOnPostedInvoice(Invoice $invoice, User $user): ?SalesCommission
    {
        if (! $invoice->isPosted() || $invoice->kind->value !== 'sales') {
            return null;
        }

        $party = Party::query()->where('ledger_id', $invoice->party_ledger_id)->first();
        $percent = (float) ($party?->commission_percent ?? 0);

        if ($percent <= 0) {
            return null;
        }

        $company = $invoice->company;
        if (! $company->commission_expense_ledger_id || ! $company->commission_payable_ledger_id) {
            return null;
        }

        if (SalesCommission::query()->where('invoice_id', $invoice->id)->exists()) {
            return SalesCommission::query()->where('invoice_id', $invoice->id)->first();
        }

        $base = Money::cents((string) $invoice->grand_total);
        $amount = (int) round($base * ($percent / 100));

        if ($amount <= 0) {
            return null;
        }

        $voucher = $this->vouchers->save(
            $company,
            $invoice->branch,
            $invoice->financialYear,
            $user,
            VoucherType::Journal,
            [
                'voucher_date' => $invoice->invoice_date->toDateString(),
                'narration' => 'Sales commission '.$invoice->invoice_number,
                'entries' => [
                    ['ledger_id' => $company->commission_expense_ledger_id, 'debit' => Money::format($amount), 'credit' => '0.00'],
                    ['ledger_id' => $company->commission_payable_ledger_id, 'debit' => '0.00', 'credit' => Money::format($amount)],
                ],
            ],
            true
        );

        return SalesCommission::query()->create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'party_id' => $party?->id,
            'salesperson_name' => $party?->salesperson_name,
            'base_amount' => Money::format($base),
            'percent' => number_format($percent, 2, '.', ''),
            'commission_amount' => Money::format($amount),
            'accrual_voucher_id' => $voucher->id,
            'status' => 'accrued',
        ]);
    }
}
