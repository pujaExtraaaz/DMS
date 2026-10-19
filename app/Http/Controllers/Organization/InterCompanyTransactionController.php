<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\InterCompanyTransaction;
use App\Http\Controllers\Controller;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InterCompanyTransactionController extends Controller
{
    /**
     * Store a newly created inter-company transaction.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $this->validateTransaction($request);

        $transaction = DB::transaction(function () use ($validated, $request) {
            if (empty($validated['transaction_no'])) {
                $validated['transaction_no'] = CodeGenerator::forInterCompanyTransaction();
            }

            if ($request->user()) {
                $validated['created_by'] = $request->user()->id;
            }

            $tx = new InterCompanyTransaction($validated);
            $tx->calculateNetAmount();
            $tx->save();

            return $tx;
        });

        $transaction->load(['fromCompany', 'toCompany', 'approver']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Transaction '{$transaction->transaction_no}' created successfully.",
                'transaction' => $transaction,
            ]);
        }

        return redirect()->route('organization.sister-concerns.transactions')
            ->with('success', "Transaction '{$transaction->transaction_no}' created successfully.");
    }

    /**
     * Display the specified inter-company transaction.
     */
    public function show(Request $request, InterCompanyTransaction $transaction): JsonResponse
    {
        $transaction->load(['fromCompany', 'toCompany', 'approver', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'transaction' => $transaction,
        ]);
    }

    /**
     * Update the specified inter-company transaction.
     */
    public function update(Request $request, InterCompanyTransaction $transaction): JsonResponse|RedirectResponse
    {
        $validated = $this->validateTransaction($request, $transaction);

        DB::transaction(function () use ($transaction, $validated, $request) {
            if ($request->user()) {
                $validated['updated_by'] = $request->user()->id;
            }

            $transaction->fill($validated);
            $transaction->calculateNetAmount();
            $transaction->save();
        });

        $transaction->load(['fromCompany', 'toCompany', 'approver']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Transaction '{$transaction->transaction_no}' updated successfully.",
                'transaction' => $transaction,
            ]);
        }

        return redirect()->route('organization.sister-concerns.transactions')
            ->with('success', "Transaction '{$transaction->transaction_no}' updated successfully.");
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(Request $request, InterCompanyTransaction $transaction): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($user && ! $user->hasRole('super-admin') && ! $user->can('organization.manage') && ! $user->can('organization.delete')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this inter-company transaction.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized to delete this inter-company transaction.');
        }

        $txNo = $transaction->transaction_no;
        $transaction->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Transaction '{$txNo}' deleted successfully.",
            ]);
        }

        return redirect()->route('organization.sister-concerns.transactions')
            ->with('success', "Transaction '{$txNo}' deleted successfully.");
    }

    /**
     * Validate request payload for create and update.
     */
    protected function validateTransaction(Request $request, ?InterCompanyTransaction $existing = null): array
    {
        // Pre-cleaning
        if ($request->has('from_company_id')) {
            $request->merge(['from_company_id' => (int) $request->input('from_company_id')]);
        }
        if ($request->has('to_company_id')) {
            $request->merge(['to_company_id' => (int) $request->input('to_company_id')]);
        }
        if ($request->has('currency')) {
            $request->merge(['currency' => strtoupper(trim((string) $request->input('currency', 'INR')))]);
        }

        $rules = [
            // 1. Transaction Details
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|string|in:Sale,Purchase,Service,Loan Given,Loan Received',
            'from_company_id' => 'required|exists:companies,id',
            'to_company_id' => 'required|exists:companies,id|different:from_company_id',

            // 2. Amount Details
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0.0001',
            'is_gst_applicable' => 'nullable|boolean',
            'igst_amount' => 'nullable|numeric|min:0',
            'is_tds_applicable' => 'nullable|boolean',
            'tds_section' => 'nullable|string|max:50',
            'tds_rate' => 'nullable|numeric|min:0|max:100',
            'tds_amount' => 'nullable|numeric|min:0',

            // 3. References & Settlement
            'reference_invoice' => 'nullable|string|max:100',
            'reference_po' => 'nullable|string|max:100',
            'due_date' => 'nullable|date',
            'status' => 'required|string|in:Pending,Approved,Settled,Cancelled',

            // 4. Additional
            'narration' => 'nullable|string',
            'approved_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ];

        $messages = [
            'transaction_date.required' => 'Transaction Date is required.',
            'transaction_type.required' => 'Transaction Type is required.',
            'from_company_id.required' => 'From Company is required.',
            'to_company_id.required' => 'To Company is required.',
            'to_company_id.different' => 'From Company and To Company cannot be the same entity.',
            'amount.required' => 'Transaction Amount is required.',
            'amount.min' => 'Transaction Amount must be greater than zero.',
            'exchange_rate.required' => 'Exchange Rate is required.',
            'exchange_rate.min' => 'Exchange Rate must be greater than zero.',
            'status.required' => 'Transaction Status is required.',
            'status.in' => 'Selected status is invalid.',
            'tds_rate.max' => 'TDS Rate cannot exceed 100%.',
        ];

        $data = $request->validate($rules, $messages);

        // Normalize booleans and defaults
        $data['is_gst_applicable'] = $request->boolean('is_gst_applicable');
        $data['is_tds_applicable'] = $request->boolean('is_tds_applicable');

        if (! $data['is_gst_applicable']) {
            $data['igst_amount'] = 0.00;
        } else {
            $data['igst_amount'] = isset($data['igst_amount']) ? (float) $data['igst_amount'] : 0.00;
        }

        if (! $data['is_tds_applicable']) {
            $data['tds_section'] = null;
            $data['tds_rate'] = 0.00;
            $data['tds_amount'] = 0.00;
        } else {
            $data['tds_rate'] = isset($data['tds_rate']) ? (float) $data['tds_rate'] : 0.00;
            $baseAmount = (float) $data['amount'] * (float) $data['exchange_rate'];
            if (empty($data['tds_amount']) && $data['tds_rate'] > 0) {
                $data['tds_amount'] = round(($baseAmount * $data['tds_rate']) / 100, 2);
            } else {
                $data['tds_amount'] = isset($data['tds_amount']) ? (float) $data['tds_amount'] : 0.00;
            }
        }

        // Calculate Net Amount: (Amount * Exchange Rate) + IGST - TDS
        $baseConverted = round((float) $data['amount'] * (float) $data['exchange_rate'], 2);
        $data['net_amount'] = round($baseConverted + (float) $data['igst_amount'] - (float) $data['tds_amount'], 2);

        return $data;
    }
}

