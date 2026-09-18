<?php

namespace App\Domains\Deal\Services;

use App\Domains\Deal\Models\Deal;
use App\Domains\Deal\Models\DealExpense;
use App\Domains\Deal\Models\ExpenseApproval;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DealService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected AuditLogService $auditLogService,
        protected MarginService $marginService,
    ) {}

    public function create(array $data, User $actor): Deal
    {
        return DB::transaction(function () use ($data, $actor) {
            $deal = Deal::create([
                'reference' => $data['reference'] ?? $this->documentNumberService->next('DEAL', now(), 4),
                'customer_id' => $data['customer_id'],
                'invoice_id' => $data['invoice_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'site_name' => $data['site_name'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by_name' => $this->auditLogService->actorName($actor->name),
            ]);

            foreach ($data['expenses'] ?? [] as $expense) {
                $this->addExpense($deal, $expense, $actor, false);
            }

            $this->auditLogService->record($deal, 'created', $actor->name);

            return $this->marginService->refreshDeal($deal);
        });
    }

    public function addExpense(Deal $deal, array $data, User $actor, bool $refresh = true): DealExpense
    {
        $expense = DealExpense::create([
            'deal_id' => $deal->id,
            'expense_type_id' => $data['expense_type_id'],
            'amount' => $data['amount'],
            'party_name' => $data['party_name'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'pending_approval',
            'created_by_name' => $this->auditLogService->actorName($actor->name),
        ]);

        ExpenseApproval::create([
            'deal_expense_id' => $expense->id,
            'decision' => 'pending',
        ]);

        $this->auditLogService->record($expense, 'created', $actor->name);

        if ($refresh) {
            $this->marginService->refreshDeal($deal);
        }

        return $expense;
    }

    public function approveExpense(DealExpense $expense, User $actor, ?string $reason = null): DealExpense
    {
        return DB::transaction(function () use ($expense, $actor, $reason) {
            $locked = DealExpense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, ['draft', 'pending_approval'], true)) {
                throw new InvalidArgumentException('Expense cannot be approved in its current status.');
            }

            $locked->update(['status' => 'approved']);

            ExpenseApproval::create([
                'deal_expense_id' => $locked->id,
                'approver_id' => $actor->id,
                'approver_name' => $this->auditLogService->actorName($actor->name),
                'decision' => 'approved',
                'reason' => $reason,
                'decided_at' => now(),
            ]);

            $this->auditLogService->approval($locked, 'deal_expense_approve', 'approved', $reason);
            $this->marginService->refreshDeal($locked->deal);

            return $locked->fresh(['expenseType', 'approvals']);
        });
    }

    public function rejectExpense(DealExpense $expense, User $actor, ?string $reason = null): DealExpense
    {
        return DB::transaction(function () use ($expense, $actor, $reason) {
            $locked = DealExpense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, ['draft', 'pending_approval'], true)) {
                throw new InvalidArgumentException('Expense cannot be rejected in its current status.');
            }

            $locked->update(['status' => 'rejected']);

            ExpenseApproval::create([
                'deal_expense_id' => $locked->id,
                'approver_id' => $actor->id,
                'approver_name' => $this->auditLogService->actorName($actor->name),
                'decision' => 'rejected',
                'reason' => $reason,
                'decided_at' => now(),
            ]);

            $this->marginService->refreshDeal($locked->deal);

            return $locked->fresh(['expenseType', 'approvals']);
        });
    }
}
