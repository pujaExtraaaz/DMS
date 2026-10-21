<?php

namespace Tally\Invoicing;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherStatus;
use Tally\Inventory\InventoryPosting;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\GstRegistration;
use Tally\Models\Invoice;
use App\Models\User;
use Tally\Payables\DuplicateBillDetector;
use Tally\Selling\SalesOrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        private readonly InvoiceNumberer $numbers,
        private readonly InvoiceMath $math,
        private readonly InvoiceAccounting $accounting,
        private readonly VoucherEngine $vouchers,
        private readonly PartyDirectory $parties,
        private readonly TradingAccounts $accounts,
        private readonly InventoryEffect $inventory,
        private readonly InvoiceLinePrep $linePrep,
        private readonly InvoiceTax $invoiceTax,
        private readonly InventoryPosting $inventoryPosting,
        private readonly DuplicateBillDetector $duplicateBills,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(
        Company $company,
        Branch $branch,
        FinancialYear $financialYear,
        User $user,
        InvoiceKind $kind,
        array $input,
        bool $post,
        ?Invoice $invoice = null,
    ): Invoice {
        return DB::transaction(function () use ($company, $branch, $financialYear, $user, $kind, $input, $post, $invoice) {
            $this->assertContext($company, $branch, $financialYear);

            if ($invoice) {
                $invoice = $this->lock($invoice);
                $this->assertDraft($invoice);
                $this->assertSameContext($invoice, $company, $branch, $financialYear);
                $kind = $invoice->kind;
            }

            $date = $this->date($financialYear, (string) ($input['invoice_date'] ?? ''));
            $party = $this->parties->find($company, $kind, (int) ($input['party_ledger_id'] ?? 0));
            $account = $this->accounts->find($company, $kind, (int) ($input['account_ledger_id'] ?? 0));
            $this->assertDifferentLedgers($party->id, $account->id, $kind);
            $built = $this->invoiceTax->apply(
                $company,
                $party,
                $this->math->compile($this->linePrep->normalize($company, $input['lines'] ?? []), $post),
            );

            $category = (string) ($input['invoice_category'] ?? 'tax_invoice');
            if (! in_array($category, ['tax_invoice', 'bill_of_supply'], true)) {
                $category = 'tax_invoice';
            }
            if ($category === 'bill_of_supply') {
                $built['tax_total'] = '0.00';
                $built['grand_total'] = Money::format(
                    Money::cents($built['subtotal']) - Money::cents($built['discount_total'])
                );
                foreach ($built['lines'] as $i => $line) {
                    $built['lines'][$i]['tax_amount'] = '0.00';
                    $built['lines'][$i]['tax_rate'] = $built['lines'][$i]['tax_rate'] ?? 0;
                }
            }

            $built = $this->applyRoundOff($company, $built);

            $attributes = [
                'invoice_date' => $date,
                'party_ledger_id' => $party->id,
                'account_ledger_id' => $account->id,
                'reference_number' => $this->blank($input['reference_number'] ?? null),
                'narration' => $this->blank($input['narration'] ?? null),
                'supply_type' => $built['supply_type'],
                'place_of_supply' => $built['place_of_supply'],
                'subtotal' => $built['subtotal'],
                'discount_total' => $built['discount_total'],
                'tax_total' => $built['tax_total'],
                'grand_total' => $built['grand_total'],
                'round_off' => $built['round_off'] ?? '0.00',
                'invoice_category' => $category,
                'billing_mode' => in_array(($input['billing_mode'] ?? 'goods'), ['goods', 'advance', 'milestone'], true)
                    ? ($input['billing_mode'] ?? 'goods')
                    : 'goods',
                'billing_address_id' => ($input['billing_address_id'] ?? null) ?: null,
                'shipping_address_id' => ($input['shipping_address_id'] ?? null) ?: null,
                'sales_order_id' => ($input['sales_order_id'] ?? null) ?: null,
                'goods_receipt_id' => ($input['goods_receipt_id'] ?? null) ?: null,
                'against_invoice_id' => ($input['against_invoice_id'] ?? null) ?: null,
                'converted_from_invoice_id' => ($input['converted_from_invoice_id'] ?? null) ?: null,
                'retention_percent' => ($input['retention_percent'] ?? null) !== '' && ($input['retention_percent'] ?? null) !== null
                    ? $input['retention_percent']
                    : null,
                'project_milestone_id' => ($input['project_milestone_id'] ?? null) ?: null,
                'reverse_charge' => (bool) ($input['reverse_charge'] ?? false),
                'gst_registration_id' => $this->registrationId($company, $branch, $date),
            ];

            if ($attributes['retention_percent'] !== null) {
                $attributes['retention_amount'] = Money::format(
                    (int) round(Money::cents($built['grand_total']) * ((float) $attributes['retention_percent'] / 100))
                );
            }

            if ($invoice) {
                $invoice->lines()->delete();
                $invoice->update($attributes);
            } else {
                $invoice = Invoice::query()->create($attributes + [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'financial_year_id' => $financialYear->id,
                    'kind' => $kind,
                    'invoice_number' => $this->numbers->allocate($company, $branch, $financialYear, $kind),
                    'status' => VoucherStatus::Draft,
                    'created_by' => $user->id,
                ]);
            }

            $invoice->lines()->createMany($built['lines']);

            if ($post) {
                $invoice = $this->postLocked(
                    $invoice->fresh(['lines', 'company', 'branch', 'financialYear']),
                    $user,
                    (bool) ($input['force_duplicate'] ?? false),
                );
            }

            return $invoice->fresh(['lines.invoice', 'party', 'account', 'voucher.entries.ledger', 'creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function alter(Invoice $invoice, User $user, array $input, bool $post): Invoice
    {
        return DB::transaction(function () use ($invoice, $user, $input, $post) {
            $invoice = $this->lock($invoice)->load(['company', 'branch', 'financialYear', 'voucher']);

            if (! $invoice->isPosted() || ! $invoice->voucher) {
                throw ValidationException::withMessages([
                    'status' => 'Only a posted invoice can be altered.',
                ]);
            }

            $this->inventory->reverse($invoice);
            $this->inventoryPosting->cancelInvoice($invoice);
            app(SalesOrderService::class)->release($invoice);
            $invoice->voucher->update([
                'status' => VoucherStatus::Draft,
                'posted_at' => null,
            ]);
            $invoice->update([
                'status' => VoucherStatus::Draft,
                'posted_at' => null,
            ]);

            return $this->save(
                $invoice->company,
                $invoice->branch,
                $invoice->financialYear,
                $user,
                $invoice->kind,
                $input,
                $post,
                $invoice->fresh(),
            );
        });
    }

    public function post(Invoice $invoice, User $user, bool $forceDuplicate = false): Invoice
    {
        return DB::transaction(function () use ($invoice, $user, $forceDuplicate) {
            $invoice = $this->lock($invoice)->load(['lines', 'company', 'branch', 'financialYear']);

            return $this->postLocked($invoice, $user, $forceDuplicate)->fresh(['lines', 'party', 'account', 'voucher.entries.ledger', 'creator']);
        });
    }

    public function cancel(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = $this->lock($invoice);

            if ($invoice->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => 'This invoice is already cancelled.',
                ]);
            }

            if (! $invoice->isPosted() || ! $invoice->voucher) {
                throw ValidationException::withMessages([
                    'status' => 'Only a posted invoice can be cancelled.',
                ]);
            }

            $this->vouchers->cancel($invoice->voucher, true);
            app(SalesOrderService::class)->release($invoice);
            $this->inventory->reverse($invoice);
            $this->inventoryPosting->cancelInvoice($invoice);

            $invoice->update([
                'status' => VoucherStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $invoice->fresh(['lines', 'party', 'account', 'voucher.entries.ledger', 'creator']);
        });
    }

    private function postLocked(Invoice $invoice, User $user, bool $forceDuplicate = false): Invoice
    {
        if ($invoice->isCancelled()) {
            throw ValidationException::withMessages([
                'status' => 'A cancelled invoice cannot be posted again.',
            ]);
        }

        if (! $invoice->kind->postsToBooks()) {
            throw ValidationException::withMessages([
                'status' => 'Pro forma invoices do not post to books. Convert them to a tax invoice first.',
            ]);
        }

        $this->assertDraft($invoice);
        $invoice->loadMissing(['company', 'branch', 'financialYear', 'lines']);
        $this->assertContext($invoice->company, $invoice->branch, $invoice->financialYear);
        $this->date($invoice->financialYear, $invoice->invoice_date->toDateString());
        $this->assertNoDuplicatePurchaseBill($invoice, $forceDuplicate);

        $kind = $invoice->kind;
        $party = $this->parties->find($invoice->company, $kind, $invoice->party_ledger_id);
        app(\Tally\Parties\PartyCredit::class)->assertWithinLimit($invoice, $party);
        $account = $this->accounts->find($invoice->company, $kind, $invoice->account_ledger_id);
        $this->assertDifferentLedgers($party->id, $account->id, $kind);

        $stored = $invoice->lines->map(fn ($line) => [
            'item_name' => $line->item_name,
            'product_id' => $line->product_id,
            'godown_id' => $line->godown_id,
            'tax_rate_id' => $line->tax_rate_id,
            'hsn_sac_id' => $line->hsn_sac_id,
            'quantity' => $line->quantity,
            'rate' => $line->rate,
            'discount' => $line->discount,
            'tax_amount' => $line->tax_amount,
        ])->all();
        $built = $this->invoiceTax->apply(
            $invoice->company,
            $party,
            $this->math->compile($stored, true),
        );
        if ($invoice->invoice_category === 'bill_of_supply') {
            $built['tax_total'] = '0.00';
            $built['grand_total'] = Money::format(
                Money::cents($built['subtotal']) - Money::cents($built['discount_total'])
            );
        }
        $built = $this->applyRoundOff($invoice->company, $built);

        if (Money::cents($built['grand_total']) !== Money::cents((string) $invoice->grand_total)) {
            throw ValidationException::withMessages([
                'lines' => 'Invoice totals do not match the line items.',
            ]);
        }

        $existing = $invoice->voucher_id ? $invoice->voucher : null;

        if ($existing && ! $existing->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Posted and cancelled invoices cannot be changed.',
            ]);
        }

        $voucher = $this->vouchers->save(
            $invoice->company,
            $invoice->branch,
            $invoice->financialYear,
            $user,
            $kind->voucherType(),
            [
                'voucher_date' => $invoice->invoice_date->toDateString(),
                'reference_number' => $invoice->reference_number,
                'narration' => $invoice->narration,
                'entries' => $this->accounting->entries($invoice),
            ],
            true,
            $existing,
            $invoice->invoice_number,
        );

        $invoice->update([
            'status' => VoucherStatus::Posted,
            'voucher_id' => $voucher->id,
            'posted_at' => now(),
        ]);

        $posted = $invoice->fresh(['lines', 'voucher', 'company', 'branch', 'financialYear']);
        app(SalesOrderService::class)->fulfil($posted);
        $this->inventory->apply($posted);
        $this->inventoryPosting->syncInvoice($posted->fresh(), $user);
        app(\Tally\Receivables\CommissionService::class)->accrueOnPostedInvoice($posted->fresh(), $user);
        app(\Tally\Receivables\CreditNoteAllocation::class)->apply($posted->fresh(['voucher']));

        return $invoice;
    }

    /**
     * Nearest-rupee rounding when the company has named a round-off ledger.
     * The stored difference is posted to that ledger when the invoice is posted.
     *
     * @param  array<string, mixed>  $built
     * @return array<string, mixed>
     */
    private function applyRoundOff(Company $company, array $built): array
    {
        $built['round_off'] = '0.00';

        if (! $company->round_off_ledger_id) {
            return $built;
        }

        $cents = Money::cents((string) $built['grand_total']);
        $rounded = (int) (round($cents / 100) * 100);
        $built['round_off'] = Money::format($rounded - $cents);
        $built['grand_total'] = Money::format($rounded);

        return $built;
    }

    private function registrationId(Company $company, Branch $branch, string $date): ?int
    {
        $registration = GstRegistration::query()
            ->where('company_id', $company->id)
            ->where(function ($query) use ($branch) {
                $query->whereNull('branch_id')->orWhere('branch_id', $branch->id);
            })
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderByRaw('case when branch_id is null then 1 else 0 end')
            ->first();

        return $registration?->id;
    }

    private function lock(Invoice $invoice): Invoice
    {
        return Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
    }

    private function assertDifferentLedgers(int $partyId, int $accountId, InvoiceKind $kind): void
    {
        if ($partyId === $accountId) {
            throw ValidationException::withMessages([
                'account_ledger_id' => 'The '.$kind->partyLabel().' and the '.$kind->accountLabel().' must be different ledgers.',
            ]);
        }
    }

    private function date(FinancialYear $financialYear, string $value): string
    {
        if ($value === '' || ! strtotime($value)) {
            throw ValidationException::withMessages([
                'invoice_date' => 'Enter an invoice date.',
            ]);
        }

        $date = date('Y-m-d', strtotime($value));
        $start = $financialYear->start_date->toDateString();
        $end = $financialYear->end_date->toDateString();

        if ($date < $start || $date > $end) {
            throw ValidationException::withMessages([
                'invoice_date' => 'The invoice date must fall in '.$financialYear->name.' ('.$financialYear->rangeLabel().').',
            ]);
        }

        return $date;
    }

    private function assertContext(Company $company, Branch $branch, FinancialYear $financialYear): void
    {
        if (! $company->is_active || $branch->company_id !== $company->id || ! $branch->is_active) {
            throw ValidationException::withMessages([
                'branch_id' => 'Select an active branch of the current company.',
            ]);
        }

        if ($financialYear->company_id !== $company->id || ! $financialYear->is_active) {
            throw ValidationException::withMessages([
                'financial_year_id' => 'Select an active financial year of the current company.',
            ]);
        }
    }

    private function assertSameContext(Invoice $invoice, Company $company, Branch $branch, FinancialYear $financialYear): void
    {
        if ($invoice->company_id !== $company->id || $invoice->branch_id !== $branch->id || $invoice->financial_year_id !== $financialYear->id) {
            throw ValidationException::withMessages([
                'invoice' => 'This invoice belongs to a different company, branch, or financial year.',
            ]);
        }
    }

    private function assertDraft(Invoice $invoice): void
    {
        if (! $invoice->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Posted and cancelled invoices cannot be changed.',
            ]);
        }
    }

    private function assertNoDuplicatePurchaseBill(Invoice $invoice, bool $forceDuplicate): void
    {
        if ($invoice->kind !== InvoiceKind::Purchase || $forceDuplicate) {
            return;
        }

        $billNumber = trim((string) ($invoice->reference_number ?: $invoice->invoice_number));
        $matches = $this->duplicateBills->matches(
            $invoice->company,
            (int) $invoice->party_ledger_id,
            $billNumber,
            (string) $invoice->grand_total,
            $invoice->id,
        );

        if ($matches->isEmpty()) {
            return;
        }

        $this->duplicateBills->flag(
            $invoice->company,
            (int) $invoice->party_ledger_id,
            $billNumber,
            (string) $invoice->grand_total,
            $invoice->invoice_date?->toDateString(),
            $invoice->id,
        );

        $numbers = $matches->pluck('invoice_number')->filter()->unique()->values()->all();

        throw ValidationException::withMessages([
            'reference_number' => 'Possible duplicate bill. Matching invoices: '.implode(', ', $numbers)
                .'. Resubmit with force_duplicate=1 to post anyway.',
        ]);
    }

    private function blank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
