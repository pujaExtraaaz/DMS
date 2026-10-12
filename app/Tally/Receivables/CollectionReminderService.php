<?php

namespace Tally\Receivables;

use Tally\Accounting\BillAllocationService;
use Tally\Accounting\OpeningBalanceType;
use Tally\Models\Bill;
use Tally\Models\CollectionReminder;
use Tally\Models\Company;

class CollectionReminderService
{
    public function __construct(private readonly BillAllocationService $allocations) {}

    /**
     * @return list<CollectionReminder>
     */
    public function queueDue(?string $asOn = null): array
    {
        $asOn ??= now()->toDateString();
        $queued = [];

        foreach (Company::query()->where('is_active', true)->get() as $company) {
            $before = (int) ($company->collection_reminder_days_before ?? 0);
            $after = (int) ($company->collection_reminder_days_after ?? 7);

            $bills = Bill::query()
                ->with('ledger')
                ->where('company_id', $company->id)
                ->where('side', OpeningBalanceType::Debit)
                ->whereNotNull('due_date')
                ->get();

            foreach ($bills as $bill) {
                if (! $bill->isActive() || ! $bill->ledger?->isCustomer()) {
                    continue;
                }

                $outstanding = $this->allocations->outstandingCents($bill, $asOn);
                if ($outstanding <= 0) {
                    continue;
                }

                $due = $bill->due_date->toDateString();
                $daysToDue = (int) ((strtotime($due) - strtotime($asOn)) / 86400);
                $trigger = ($before > 0 && $daysToDue === $before)
                    || ($after > 0 && $daysToDue === -$after);

                if (! $trigger) {
                    continue;
                }

                $exists = CollectionReminder::query()
                    ->where('bill_id', $bill->id)
                    ->whereDate('created_at', $asOn)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $queued[] = CollectionReminder::query()->create([
                    'company_id' => $company->id,
                    'bill_id' => $bill->id,
                    'ledger_id' => $bill->ledger_id,
                    'channel' => 'queue',
                    'status' => 'queued',
                    'message' => 'Payment reminder for '.$bill->bill_number.' due '.$due,
                    'sent_at' => now(),
                ]);
            }
        }

        return $queued;
    }
}
