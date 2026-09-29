<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JSON endpoint used by "Quick Add Supplier / Party" modals across
 * Purchase Order, Sales, and Inward flows so users can create vendors/parties
 * without leaving the document they are building.
 */
class QuickAddPartyController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'party_type' => 'nullable|in:sundry_creditor,sundry_debtors,both,supplier,customer,Sundry Creditor,Sundry Debtors,Both',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'gstin' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:12',
            'address' => 'nullable|string',
        ]);

        $companyId = auth()->user()?->company_id ?? Company::query()->value('id');
        $branchId = auth()->user()?->branch_id;
        $nameTrimmed = trim($data['name']);

        // 1. Duplicate Name Validation Check
        $existingName = Customer::query()
            ->where('name', $nameTrimmed)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->first();

        if ($existingName) {
            return response()->json([
                'ok' => false,
                'message' => "Supplier / Party '{$existingName->name}' already exists ({$existingName->code}).",
            ], 422);
        }

        // 2. Duplicate GSTIN Check (if provided)
        $gstin = strtoupper(trim((string) ($data['gstin'] ?? '')));
        if ($gstin !== '') {
            $existingGst = Customer::query()->where('gstin', $gstin)->first();
            if ($existingGst) {
                return response()->json([
                    'ok' => false,
                    'message' => "GSTIN already exists for Party: {$existingGst->name} ({$existingGst->code}).",
                ], 422);
            }
        }

        // 3. Normalize Party Type
        $normalizedType = strtolower(str_replace(' ', '_', (string) ($data['party_type'] ?? 'sundry_creditor')));
        $partyTypeMap = [
            'sundry_debtors'   => 'customer',
            'sundry_debtor'    => 'customer',
            'customer'         => 'customer',
            'sundry_creditor'  => 'supplier',
            'sundry_creditors' => 'supplier',
            'supplier'         => 'supplier',
            'both'             => 'both',
        ];
        $partyType = $partyTypeMap[$normalizedType] ?? 'supplier';

        // 4. Resolve Classification / Customer Type
        $customerTypeId = $data['customer_type_id'] ?? null;
        if (! $customerTypeId) {
            $customerTypeId = CustomerType::query()
                ->where('is_active', true)
                ->where(function ($q) use ($partyType) {
                    if ($partyType === 'supplier') {
                        $q->where('name', 'like', '%Supplier%')
                            ->orWhere('name', 'like', '%Vendor%')
                            ->orWhere('name', 'like', '%Creditor%');
                    } else {
                        $q->where('name', 'like', '%Customer%')
                            ->orWhere('name', 'like', '%Debtor%')
                            ->orWhere('name', 'like', '%Dealer%');
                    }
                })
                ->value('id');

            if (! $customerTypeId) {
                $customerTypeId = CustomerType::query()->where('is_active', true)->value('id');
            }

            if (! $customerTypeId) {
                $defaultType = CustomerType::create([
                    'name' => $partyType === 'supplier' ? 'Supplier' : 'Customer',
                    'code' => $partyType === 'supplier' ? 'SUP' : 'CUST',
                    'is_active' => true,
                ]);
                $customerTypeId = $defaultType->id;
            }
        }

        // 5. Create Party and default contact/address in a single transaction
        $party = DB::transaction(function () use ($data, $companyId, $branchId, $partyType, $customerTypeId, $gstin, $nameTrimmed) {
            $code = CodeGenerator::forCustomer($companyId);

            $customer = Customer::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'name' => $nameTrimmed,
                'code' => $code,
                'party_type' => $partyType,
                'customer_type_id' => $customerTypeId,
                'gstin' => $gstin ?: null,
                'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
                'email' => trim((string) ($data['email'] ?? '')) ?: null,
                'state' => trim((string) ($data['state'] ?? '')) ?: null,
                'pincode' => trim((string) ($data['pincode'] ?? '')) ?: null,
                'address' => trim((string) ($data['address'] ?? '')) ?: null,
                'credit_limit' => 0,
                'credit_days' => 0,
                'interest_rate' => 18,
                'credit_period_basis' => 'cumulative',
                'credit_status' => 'open',
                'is_active' => true,
            ]);

            if (! empty($data['address']) || ! empty($data['state']) || ! empty($data['pincode'])) {
                $customer->addresses()->create([
                    'type' => 'billing',
                    'label' => 'Head Office',
                    'name' => $nameTrimmed,
                    'address' => trim((string) ($data['address'] ?? '')) ?: null,
                    'state' => trim((string) ($data['state'] ?? '')) ?: null,
                    'pincode' => trim((string) ($data['pincode'] ?? '')) ?: null,
                    'gstin' => $gstin ?: null,
                    'is_default' => true,
                ]);
            }

            if (! empty($data['phone']) || ! empty($data['email'])) {
                $customer->contacts()->create([
                    'level' => 'primary',
                    'name' => $nameTrimmed,
                    'role' => 'Main Contact',
                    'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
                    'email' => trim((string) ($data['email'] ?? '')) ?: null,
                    'is_primary' => true,
                    'is_active' => true,
                ]);
            }

            return $customer;
        });

        return response()->json([
            'ok' => true,
            'message' => 'Supplier created successfully.',
            'supplier' => [
                'id' => $party->id,
                'name' => $party->name,
                'code' => $party->code,
                'party_type' => $party->party_type,
                'gstin' => $party->gstin,
                'phone' => $party->phone,
                'email' => $party->email,
                'state' => $party->state,
            ],
        ]);
    }
}