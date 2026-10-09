<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Models\Company;
use Tally\Reporting\OutstandingReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutstandingController extends ApiController
{
    public function index(Request $request, Company $company, OutstandingReport $report): JsonResponse
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:receivable,payable'],
            'as_of' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $asOf = $data['as_of'] ?? now()->toDateString();
        $branchId = isset($data['branch_id']) ? (int) $data['branch_id'] : null;

        if ($branchId && ! $company->branches()->whereKey($branchId)->exists()) {
            return response()->json(['message' => 'The branch does not belong to this company.', 'errors' => ['branch_id' => ['The branch does not belong to this company.']]], 422);
        }

        $type = $data['type'] ?? 'receivable';
        $result = $type === 'payable'
            ? $report->payables($company, $asOf, $branchId)
            : $report->receivables($company, $asOf, $branchId);

        return $this->data([
            'type' => $type,
            'as_of' => $asOf,
            'branch_id' => $branchId,
            'rows' => $result['rows'],
            'total' => $result['total'],
            'advances' => $result['advances'],
            'net' => $result['net'],
        ]);
    }
}
