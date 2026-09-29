<?php

namespace App\Http\Controllers\Master;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\PartyBankAccount;
use App\Domains\Master\Models\PartyContact;
use App\Domains\Master\Models\PartyCreditCheque;
use App\Domains\Master\Models\Route;
use App\Domains\Master\Services\GstLookupService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $items = Customer::query()
            ->with(['customerType', 'area', 'route', 'salesperson', 'salesManager'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%');
            }))
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->area_id))
            ->when($request->filled('customer_type_id'), fn ($q) => $q->where('customer_type_id', $request->customer_type_id))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('masters.customers.index', [
            'items' => $items,
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $companyId = auth()->user()?->company_id;
        $customer = new Customer([
            'code' => CodeGenerator::forCustomer($companyId),
        ]);

        return view('masters.customers.form', array_merge(['item' => $customer], $this->formData()));
    }

    public function store(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $customer = Customer::create($this->validated($request));
            $this->syncChildRows($request, $customer);
        });

        return $this->flashSuccess('Customer created successfully.', 'masters.customers.index');
    }

    public function show(Customer $customer): RedirectResponse
    {
        return redirect()->route('masters.customers.edit', $customer);
    }

    public function edit(Customer $customer): View
    {
        $customer->load(['contacts', 'addresses', 'bankAccounts', 'creditCheques', 'salesperson', 'salesManager']);

        return view('masters.customers.form', array_merge(['item' => $customer], $this->formData()));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        DB::transaction(function () use ($request, $customer) {
            $customer->update($this->validated($request, $customer));
            $this->syncChildRows($request, $customer);
        });

        return $this->flashSuccess('Customer updated successfully.', 'masters.customers.index');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return $this->flashSuccess('Customer deleted successfully.', 'masters.customers.index');
    }

    /**
     * Look up GSTIN registration details via configured GST service.
     */
    public function gstLookup(Request $request, GstLookupService $gstService): JsonResponse
    {
        $gstin = strtoupper(trim((string) $request->input('gstin', '')));

        if (! preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i', $gstin)) {
            return response()->json([
                'ok' => false,
                'message' => 'Please enter a valid 15-digit GSTIN (e.g. 27AAPFU0939F1ZV).',
            ], 422);
        }

        $excludeId = $request->input('exclude_id');
        $existing = Customer::query()
            ->where('gstin', $gstin)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->first();

        if ($existing) {
            return response()->json([
                'ok' => false,
                'duplicate' => true,
                'message' => "GSTIN already exists for Party: {$existing->name} ({$existing->code}).",
                'existing_party' => [
                    'id' => $existing->id,
                    'name' => $existing->name,
                    'code' => $existing->code,
                ],
            ], 422);
        }

        try {
            $data = $gstService->search($gstin);

            return response()->json([
                'ok' => true,
                'data' => $data,
                'message' => 'GST details fetched successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage() ?: 'GST service is temporarily unavailable. Please try again.',
            ], 422);
        }
    }

    protected function validated(Request $request, ?Customer $customer = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30|unique:customers,code'.($customer ? ','.$customer->id : ''),
            'party_type' => 'required|in:sundry_debtors,sundry_creditor,both,Sundry Debtors,Sundry Creditor,Both',
            'customer_type_id' => 'required|exists:customer_types,id',
            'area_id' => 'nullable|exists:areas,id',
            'route_id' => 'nullable|exists:routes,id',
            'sales_manager_id' => 'nullable|exists:users,id',
            'salesperson_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:12',
            'gstin' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'credit_period_basis' => 'nullable|in:monthly,quarterly,yearly,cumulative',
            'credit_status' => 'nullable|in:open,restricted,frozen',
            'credit_notes' => 'nullable|string',
            'is_active' => 'boolean',
            'contacts' => 'nullable|array',
            'contacts.*.name' => 'nullable|string|max:255',
            'contacts.*.level' => 'nullable|in:primary,secondary,finance,operations,site',
            'contacts.*.role' => 'nullable|string|max:100',
            'contacts.*.phone' => 'nullable|string|max:20',
            'contacts.*.alternate_phone' => 'nullable|string|max:20',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.location' => 'nullable|string|max:255',
            'contacts.*.is_primary' => 'nullable|boolean',
            'addresses' => 'nullable|array',
            'addresses.*.type' => 'nullable|in:billing,shipping,site,warehouse',
            'addresses.*.label' => 'nullable|string|max:255',
            'addresses.*.name' => 'nullable|string|max:255',
            'addresses.*.address' => 'nullable|string',
            'addresses.*.state' => 'nullable|string|max:100',
            'addresses.*.pincode' => 'nullable|string|max:12',
            'addresses.*.gstin' => 'nullable|string|max:20',
            'addresses.*.is_default' => 'nullable|boolean',
            'bank_accounts' => 'nullable|array',
            'bank_accounts.*.bank_name' => 'nullable|string|max:255',
            'bank_accounts.*.account_holder_name' => 'nullable|string|max:255',
            'bank_accounts.*.account_number' => 'nullable|string|max:50',
            'bank_accounts.*.account_type' => 'nullable|in:current,savings,od,cc,other,Current,Savings,OD,CC,Other',
            'bank_accounts.*.ifsc_code' => 'nullable|string|max:20',
            'bank_accounts.*.branch_name' => 'nullable|string|max:255',
            'bank_accounts.*.branch_address' => 'nullable|string',
            'bank_accounts.*.upi_id' => 'nullable|string|max:100',
            'bank_accounts.*.is_primary' => 'nullable|boolean',
            'credit_cheques' => 'nullable|array',
            'credit_cheques.*.cheque_number' => 'nullable|string|max:50',
            'credit_cheques.*.bank_name' => 'nullable|string|max:255',
            'credit_cheques.*.account_holder_name' => 'nullable|string|max:255',
            'credit_cheques.*.cheque_date' => 'nullable|date',
            'credit_cheques.*.amount' => 'nullable|numeric|min:0',
            'credit_cheques.*.cheque_type' => 'nullable|in:security_cheque,credit_cheque,post_dated_cheque,other',
            'credit_cheques.*.status' => 'nullable|in:received,pending,deposited,cleared,bounced,cancelled',
            'credit_cheques.*.remarks' => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['credit_limit'] = $data['credit_limit'] ?? 0;
        $data['credit_days'] = $data['credit_days'] ?? 0;
        $data['interest_rate'] = $data['interest_rate'] ?? 18;
        $data['credit_period_basis'] = $data['credit_period_basis'] ?? 'cumulative';
        $data['credit_status'] = $data['credit_status'] ?? 'open';
        $data['company_id'] = $customer?->company_id ?? auth()->user()?->company_id;
        $data['branch_id'] = $customer?->branch_id ?? auth()->user()?->branch_id;

        // Map accounting party type terminology to internal database values
        $normalizedType = strtolower(str_replace(' ', '_', (string) ($data['party_type'] ?? '')));
        $partyTypeMap = [
            'sundry_debtors'  => 'customer',
            'sundry_debtor'   => 'customer',
            'sundry_creditor' => 'supplier',
            'sundry_creditors'=> 'supplier',
            'both'            => 'both',
        ];
        $data['party_type'] = $partyTypeMap[$normalizedType] ?? 'customer';

        if ($customer) {
            $data['code'] = $customer->code;
        } else {
            $data['code'] = CodeGenerator::forCustomer($data['company_id'] ?? auth()->user()?->company_id);
        }

        // Strip repeaters so they aren't mass-assigned to Customer::update
        unset($data['contacts'], $data['addresses'], $data['bank_accounts'], $data['credit_cheques']);

        return $data;
    }

    protected function syncChildRows(Request $request, Customer $customer): void
    {
        $contacts = collect($request->input('contacts', []))
            ->filter(fn ($row) => filled($row['name'] ?? null) || filled($row['phone'] ?? null) || filled($row['email'] ?? null))
            ->map(fn ($row) => [
                'level' => $row['level'] ?? 'secondary',
                'name' => $row['name'] ?? null,
                'role' => $row['role'] ?? null,
                'phone' => $row['phone'] ?? null,
                'alternate_phone' => $row['alternate_phone'] ?? null,
                'email' => $row['email'] ?? null,
                'location' => $row['location'] ?? null,
                'is_primary' => (bool) ($row['is_primary'] ?? false),
                'is_active' => true,
            ])
            ->values();

        $customer->contacts()->delete();
        foreach ($contacts as $row) {
            $customer->contacts()->create($row);
        }

        $addresses = collect($request->input('addresses', []))
            ->filter(fn ($row) => filled($row['address'] ?? null) || filled($row['label'] ?? null))
            ->map(fn ($row) => [
                'type' => $row['type'] ?? 'billing',
                'label' => $row['label'] ?? null,
                'name' => $row['name'] ?? null,
                'address' => $row['address'] ?? null,
                'state' => $row['state'] ?? null,
                'pincode' => $row['pincode'] ?? null,
                'gstin' => $row['gstin'] ?? null,
                'is_default' => (bool) ($row['is_default'] ?? false),
            ])
            ->values();

        $customer->addresses()->delete();
        foreach ($addresses as $row) {
            $customer->addresses()->create($row);
        }

        $bankAccounts = collect($request->input('bank_accounts', []))
            ->filter(fn ($row) => filled($row['bank_name'] ?? null) || filled($row['account_number'] ?? null))
            ->map(function ($row) {
                return [
                    'bank_name' => trim((string) ($row['bank_name'] ?? '')),
                    'account_holder_name' => trim((string) ($row['account_holder_name'] ?? '')) ?: null,
                    'account_number' => trim((string) ($row['account_number'] ?? '')),
                    'account_type' => strtolower((string) ($row['account_type'] ?? 'current')),
                    'ifsc_code' => strtoupper(trim((string) ($row['ifsc_code'] ?? ''))) ?: null,
                    'branch_name' => trim((string) ($row['branch_name'] ?? '')) ?: null,
                    'branch_address' => trim((string) ($row['branch_address'] ?? '')) ?: null,
                    'upi_id' => trim((string) ($row['upi_id'] ?? '')) ?: null,
                    'is_primary' => (bool) ($row['is_primary'] ?? false),
                    'is_active' => true,
                ];
            })
            ->values();

        $hasPrimary = $bankAccounts->contains('is_primary', true);
        if (! $hasPrimary && $bankAccounts->isNotEmpty()) {
            $bankAccounts = $bankAccounts->map(fn ($row, $i) => array_merge($row, ['is_primary' => $i === 0]));
        }

        $customer->bankAccounts()->delete();
        foreach ($bankAccounts as $row) {
            $customer->bankAccounts()->create($row);
        }

        $creditCheques = collect($request->input('credit_cheques', []))
            ->filter(fn ($row) => filled($row['cheque_number'] ?? null) || filled($row['amount'] ?? null) || filled($row['bank_name'] ?? null))
            ->map(function ($row) {
                return [
                    'cheque_number' => trim((string) ($row['cheque_number'] ?? '')),
                    'bank_name' => trim((string) ($row['bank_name'] ?? '')) ?: null,
                    'account_holder_name' => trim((string) ($row['account_holder_name'] ?? '')) ?: null,
                    'cheque_date' => ! empty($row['cheque_date']) ? $row['cheque_date'] : null,
                    'amount' => is_numeric($row['amount'] ?? null) ? (float) $row['amount'] : 0.00,
                    'cheque_type' => (string) ($row['cheque_type'] ?? 'credit_cheque'),
                    'status' => (string) ($row['status'] ?? 'pending'),
                    'remarks' => trim((string) ($row['remarks'] ?? '')) ?: null,
                    'is_active' => true,
                ];
            })
            ->values();

        $customer->creditCheques()->delete();
        foreach ($creditCheques as $row) {
            $customer->creditCheques()->create($row);
        }
    }

    protected function formData(): array
    {
        $salesManagers = User::role('sales-manager')->orderBy('name')->get();
        $salespersons = User::role('salesperson')->orderBy('name')->get();

        if ($salespersons->isEmpty()) {
            $salespersons = User::orderBy('name')->get();
        }

        // Map salespersons to manager_id if linked via Employee records
        $employeeMap = Employee::whereNotNull('user_id')
            ->with('manager')
            ->get()
            ->keyBy('user_id');

        $salespersonsData = $salespersons->map(function ($sp) use ($employeeMap) {
            $emp = $employeeMap->get($sp->id);
            $managerUserId = $emp?->manager?->user_id;

            return [
                'id' => (string) $sp->id,
                'name' => $sp->name,
                'manager_user_id' => $managerUserId ? (string) $managerUserId : null,
            ];
        })->values()->all();

        return [
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'routes' => Route::where('is_active', true)->orderBy('name')->get(),
            'salesManagers' => $salesManagers,
            'salespersons' => $salespersons,
            'salespersonsData' => $salespersonsData,
            'states' => GstLookupService::states(),
        ];
    }
}