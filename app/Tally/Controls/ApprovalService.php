<?php

namespace Tally\Controls;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Tally\Models\ApprovalPolicy;
use Tally\Models\ApprovalRequest;
use Tally\Models\Company;

class ApprovalService
{
    /**
     * @param  array{document_type: string, document_id: int, amount?: mixed, remarks?: ?string}  $data
     */
    public function request(Company $company, User $user, array $data): ApprovalRequest
    {
        $amount = (string) ($data['amount'] ?? 0);
        $policy = ApprovalPolicy::query()
            ->where('company_id', $company->id)
            ->where('document_type', $data['document_type'])
            ->where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->where(function ($q) use ($amount) {
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
            })
            ->orderByDesc('min_amount')
            ->first();

        return ApprovalRequest::query()->create([
            'company_id' => $company->id,
            'approval_policy_id' => $policy?->id,
            'document_type' => $data['document_type'],
            'document_id' => (int) $data['document_id'],
            'amount' => $amount,
            'current_level' => 1,
            'status' => 'pending',
            'requested_by' => $user->id,
            'remarks' => $data['remarks'] ?? null,
        ]);
    }

    public function approve(ApprovalRequest $request, User $user, ?string $remarks = null): ApprovalRequest
    {
        return $this->decide($request, $user, 'approved', $remarks);
    }

    public function reject(ApprovalRequest $request, User $user, ?string $remarks = null): ApprovalRequest
    {
        return $this->decide($request, $user, 'rejected', $remarks);
    }

    private function decide(ApprovalRequest $request, User $user, string $status, ?string $remarks): ApprovalRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending requests can be decided.',
            ]);
        }

        $request->update([
            'status' => $status,
            'decided_by' => $user->id,
            'decided_at' => now(),
            'remarks' => $remarks ?? $request->remarks,
        ]);

        return $request->fresh();
    }
}
