<?php

namespace Tally\Selling;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\InvoiceService;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Invoice;
use Tally\Models\RecurringInvoice;

class RecurringInvoiceService
{
    public function __construct(private readonly InvoiceService $invoices) {}

    /**
     * @return list<Invoice>
     */
    public function runDue(?string $asOn = null): array
    {
        $asOn ??= now()->toDateString();
        $created = [];

        $templates = RecurringInvoice::query()
            ->with(['lines', 'company', 'branch', 'customer'])
            ->where('is_active', true)
            ->whereNotNull('next_run_on')
            ->where('next_run_on', '<=', $asOn)
            ->get();

        foreach ($templates as $template) {
            $year = FinancialYear::query()
                ->where('company_id', $template->company_id)
                ->where('is_active', true)
                ->where('start_date', '<=', $asOn)
                ->where('end_date', '>=', $asOn)
                ->first();

            if (! $year) {
                continue;
            }

            $user = User::query()->find($template->company->created_by ?? 0)
                ?? User::query()->first();

            if (! $user) {
                continue;
            }

            $invoice = DB::transaction(function () use ($template, $year, $user, $asOn) {
                $invoice = $this->invoices->save(
                    $template->company,
                    $template->branch,
                    $year,
                    $user,
                    InvoiceKind::Sales,
                    [
                        'invoice_date' => $asOn,
                        'party_ledger_id' => $template->customer_ledger_id,
                        'account_ledger_id' => $template->account_ledger_id,
                        'narration' => $template->narration ?: 'Recurring: '.$template->name,
                        'invoice_category' => $template->invoice_category,
                        'lines' => $template->lines->map(fn ($line) => [
                            'item_name' => $line->item_name,
                            'product_id' => $line->product_id,
                            'quantity' => $line->quantity,
                            'rate' => $line->rate,
                            'discount' => $line->discount,
                            'tax_amount' => number_format(
                                max(0, ((float) $line->quantity * (float) $line->rate - (float) $line->discount) * ((float) $line->tax_rate / 100)),
                                2,
                                '.',
                                ''
                            ),
                        ])->all(),
                    ],
                    $template->auto_post
                );

                $template->update(['next_run_on' => $this->nextDate($template, $asOn)]);

                return $invoice;
            });

            $created[] = $invoice;
        }

        return $created;
    }

    private function nextDate(RecurringInvoice $template, string $from): string
    {
        $base = strtotime($from);

        return match ($template->frequency) {
            'weekly' => date('Y-m-d', strtotime('+7 days', $base)),
            'quarterly' => date('Y-m-d', strtotime('+3 months', $base)),
            'yearly' => date('Y-m-d', strtotime('+1 year', $base)),
            default => date('Y-m-d', strtotime('+1 month', $base)),
        };
    }
}
