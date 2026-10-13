<?php

namespace Tally\Purchasing;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\PurchaseRequisition;
use Tally\Models\RequestForQuotation;

class PurchaseRequisitionService
{
    public function __construct(
        private readonly RfqService $rfqs,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, Branch $branch, FinancialYear $year, User $user, array $input, ?PurchaseRequisition $requisition = null): PurchaseRequisition
    {
        return DB::transaction(function () use ($company, $branch, $year, $user, $input, $requisition) {
            $payload = [
                'requisition_date' => (string) ($input['requisition_date'] ?? now()->toDateString()),
                'requested_by' => ($input['requested_by'] ?? null) ?: $user->id,
                'department_id' => ($input['department_id'] ?? null) ?: null,
                'priority' => ($input['priority'] ?? null) ?: 'normal',
                'status' => ($input['status'] ?? null) ?: ($requisition?->status ?? 'draft'),
                'narration' => ($input['narration'] ?? null) ?: null,
            ];

            if ($requisition) {
                $requisition->update($payload);
                $requisition->lines()->delete();
            } else {
                $count = PurchaseRequisition::query()
                    ->where('company_id', $company->id)
                    ->where('financial_year_id', $year->id)
                    ->count() + 1;
                $requisition = PurchaseRequisition::query()->create($payload + [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'financial_year_id' => $year->id,
                    'number' => 'PR-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT),
                ]);
            }

            $lineNumber = 1;
            foreach ($input['lines'] ?? [] as $line) {
                if (! is_array($line) || trim((string) ($line['item_name'] ?? '')) === '') {
                    continue;
                }
                $requisition->lines()->create([
                    'line_number' => $lineNumber++,
                    'product_id' => ($line['product_id'] ?? null) ?: null,
                    'item_name' => trim((string) $line['item_name']),
                    'quantity' => $line['quantity'] ?? 1,
                    'unit' => ($line['unit'] ?? null) ?: null,
                    'required_by' => ($line['required_by'] ?? null) ?: null,
                    'notes' => ($line['notes'] ?? null) ?: null,
                ]);
            }

            return $requisition->fresh(['lines']);
        });
    }

    public function convertToRfq(
        Company $company,
        Branch $branch,
        FinancialYear $year,
        User $user,
        PurchaseRequisition $requisition,
    ): RequestForQuotation {
        abort_unless($requisition->company_id === $company->id, 404);
        $requisition->loadMissing('lines');

        if ($requisition->lines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'Add lines before creating an RFQ.',
            ]);
        }

        if (in_array($requisition->status, ['cancelled', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Cannot create an RFQ from a '.$requisition->status.' requisition.',
            ]);
        }

        $rfq = $this->rfqs->save($company, $branch, $year, $user, [
            'rfq_date' => now()->toDateString(),
            'purchase_requisition_id' => $requisition->id,
            'status' => 'open',
            'narration' => trim('From '.$requisition->number.($requisition->narration ? ' — '.$requisition->narration : '')),
            'lines' => $requisition->lines->map(fn ($line) => [
                'product_id' => $line->product_id,
                'item_name' => $line->item_name,
                'quantity' => $line->quantity,
                'unit' => $line->unit,
            ])->all(),
        ]);

        if ($requisition->status === 'draft') {
            $requisition->update(['status' => 'approved']);
        }

        return $rfq;
    }
}
