<?php

namespace App\Domains\Interest\Services;

use App\Domains\Interest\Models\InterestDocument;
use App\Domains\Interest\Models\InterestLedger;
use App\Domains\Interest\Models\InterestRule;
use App\Domains\Master\Models\Customer;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InterestService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected AuditLogService $auditLogService,
    ) {}

    /**
     * interest = overdue_balance × annual_rate × days / 365
     */
    public function calculate(float $overdueBalance, float $annualRate, int $days): float
    {
        if ($overdueBalance <= 0 || $days <= 0 || $annualRate <= 0) {
            return 0.0;
        }

        return round($overdueBalance * ($annualRate / 100) * ($days / 365), 2);
    }

    public function resolveRule(?Customer $customer = null): InterestRule
    {
        if ($customer) {
            $partyRule = InterestRule::query()
                ->where('customer_id', $customer->id)
                ->where('is_active', true)
                ->latest('id')
                ->first();

            if ($partyRule) {
                return $partyRule;
            }

            if ((float) ($customer->interest_rate ?? 0) > 0) {
                return new InterestRule([
                    'name' => 'Party rate',
                    'customer_id' => $customer->id,
                    'annual_rate' => $customer->interest_rate,
                    'grace_days' => 0,
                    'is_active' => true,
                ]);
            }
        }

        $default = InterestRule::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        return $default ?: new InterestRule([
            'name' => 'System default',
            'annual_rate' => 18,
            'grace_days' => 0,
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    public function previewForInvoice(Invoice $invoice, ?Carbon $asOf = null): ?InterestLedger
    {
        $asOf ??= now();
        $invoice->loadMissing('customer');

        if (in_array($invoice->status, ['cancelled', 'paid', 'draft'], true)) {
            return null;
        }

        $dueDate = $invoice->due_date?->copy() ?? $invoice->invoice_date?->copy();
        if (! $dueDate) {
            return null;
        }

        $rule = $this->resolveRule($invoice->customer);
        $grace = (int) ($rule->grace_days ?? 0);
        $interestStart = $dueDate->copy()->addDays($grace);

        if ($asOf->lt($interestStart)) {
            return null;
        }

        $overdueBalance = max(0, (float) $invoice->grand_total - (float) $invoice->paid_amount);
        if ($overdueBalance <= 0) {
            return null;
        }

        $days = $interestStart->diffInDays($asOf);
        $rate = (float) $rule->annual_rate;
        $amount = $this->calculate($overdueBalance, $rate, $days);

        return InterestLedger::updateOrCreate(
            [
                'invoice_id' => $invoice->id,
                'as_of_date' => $asOf->toDateString(),
                'status' => 'preview',
            ],
            [
                'customer_id' => $invoice->customer_id,
                'interest_rule_id' => $rule->exists ? $rule->id : null,
                'overdue_balance' => round($overdueBalance, 2),
                'overdue_days' => $days,
                'annual_rate' => $rate,
                'interest_amount' => $amount,
                'notes' => 'Daily interest preview',
            ]
        );
    }

    /**
     * @return Collection<int, InterestLedger>
     */
    public function previewOverdue(?Carbon $asOf = null): Collection
    {
        $asOf ??= now();

        $invoices = Invoice::query()
            ->with('customer')
            ->whereNotIn('status', ['cancelled', 'paid', 'draft'])
            ->where(function ($q) use ($asOf) {
                $q->whereNotNull('due_date')->whereDate('due_date', '<', $asOf->toDateString())
                    ->orWhere(function ($q2) use ($asOf) {
                        $q2->whereNull('due_date')->whereDate('invoice_date', '<', $asOf->toDateString());
                    });
            })
            ->whereColumn('paid_amount', '<', 'grand_total')
            ->get();

        return $invoices
            ->map(fn (Invoice $invoice) => $this->previewForInvoice($invoice, $asOf))
            ->filter()
            ->values();
    }

    public function postLedger(InterestLedger $ledger, User $actor): InterestDocument
    {
        return DB::transaction(function () use ($ledger, $actor) {
            $locked = InterestLedger::query()->whereKey($ledger->id)->lockForUpdate()->firstOrFail();

            $document = InterestDocument::create([
                'document_no' => $this->documentNumberService->next('INT', now(), 4),
                'customer_id' => $locked->customer_id,
                'interest_ledger_id' => $locked->id,
                'document_date' => now()->toDateString(),
                'amount' => $locked->interest_amount,
                'status' => 'posted',
                'created_by_name' => $this->auditLogService->actorName($actor->name),
                'posted_at' => now(),
            ]);

            $locked->update(['status' => 'posted']);
            $this->auditLogService->record($document, 'created', $actor->name);

            return $document;
        });
    }
}
