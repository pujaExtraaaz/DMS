<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Route;
use App\Domains\Master\Services\GstLookupService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CodeGenerator;
use App\Support\IndianStates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected GstLookupService $gstLookupService
    ) {}

    public function index(Request $request): View
    {
        $items = Customer::query()
            ->with(['customerType', 'area', 'route', 'salesperson', 'salesManager'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%')
                    ->orWhere('phone', 'like', '%'.$request->search.'%')
                    ->orWhere('gstin', 'like', '%'.$request->search.'%');
            }))
            ->when($request->filled('party_type'), fn ($q) => $q->where('party_type', $request->party_type))
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->area_id))
            ->when($request->filled('customer_type_id'), fn ($q) => $q->where('customer_type_id', $request->customer_type_id))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('masters.customers.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'partyType' => $request->string('party_type'),
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('masters.customers.form', [
            'item' => new Customer,
            ...$this->formData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if (blank($data['code'] ?? null)) {
            $data['code'] = CodeGenerator::forParty();
        }

        $customer = Customer::create($data);
        $this->syncChildRows($request, $customer);

        return $this->flashSuccess('Party created successfully.', 'masters.customers.index');
    }

    public function edit(Customer $customer): View
    {
        $customer->load(['contacts', 'addresses', 'bankAccounts', 'creditCheques']);

        return view('masters.customers.form', [
            'item' => $customer,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validated($request, $customer);
        $customer->update($data);
        $this->syncChildRows($request, $customer);

        return $this->flashSuccess('Party updated successfully.', 'masters.customers.index');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return $this->flashSuccess('Party deleted successfully.', 'masters.customers.index');
    }

    public function show(Customer $customer): View
    {
        $customer->load(['customerType', 'area', 'route', 'salesperson', 'salesManager', 'contacts', 'addresses', 'bankAccounts', 'creditCheques']);

        return view('masters.customers.show', compact('customer'));
    }

    /**
     * Search GSTIN and return parsed vendor details.
     */
    public function gstLookup(Request $request): JsonResponse
    {
        $gstin = $request->input('gstin', $request->query('gstin'));

        if (! $gstin) {
            return response()->json([
                'success' => false,
                'message' => 'GSTIN parameter is required.',
            ], 422);
        }

        $result = $this->gstLookupService->search((string) $gstin);

        return response()->json($result);
    }

    /**
     * Quick add supplier or customer modal endpoint.
     */
    public function quickAdd(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30|unique:customers,code',
            'party_type' => 'required|in:dealer,customer,supplier,both',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gstin' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:12',
            'address' => 'nullable|string',
        ]);

        $defaultTypeId = CustomerType::query()->value('id');
        $validated['customer_type_id'] = $validated['customer_type_id'] ?? $defaultTypeId;
        $validated['code'] = $validated['code'] ?: 'P-'.strtoupper(substr(uniqid(), -6));
        $validated['company_id'] = auth()->user()?->company_id;
        $validated['branch_id'] = auth()->user()?->branch_id;
        $validated['is_active'] = true;

        $customer = Customer::create($validated);

        if (filled($validated['address'] ?? null)) {
            $customer->addresses()->create([
                'type' => 'billing',
                'label' => 'Main Office',
                'name' => $customer->name,
                'address' => $validated['address'],
                'state' => $validated['state'] ?? null,
                'pincode' => $validated['pincode'] ?? null,
                'gstin' => $validated['gstin'] ?? null,
                'is_default' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'party' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'code' => $customer->code,
                'party_type' => $customer->party_type,
                'gstin' => $customer->gstin,
                'state' => $customer->state,
            ],
        ]);
    }

    protected function validated(Request $request, ?Customer $customer = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30|unique:customers,code'.($customer ? ','.$customer->id : ''),
            'party_type' => 'required|in:dealer,customer,supplier,both',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'area_id' => 'nullable|exists:areas,id',
            'route_id' => 'nullable|exists:routes,id',
            'salesperson_id' => 'nullable|exists:users,id',
            'sales_manager_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gstin' => 'nullable|string|max:20',
            'pan' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:12',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'credit_status' => 'nullable|in:open,hold,blocked',
            'credit_period_basis' => 'nullable|in:cumulative,invoice_date,inward_date,receipt_date',
            'interest_rate' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'contacts' => 'nullable|array',
            'contacts.*.name' => 'nullable|string|max:255',
            'contacts.*.role' => 'nullable|string|max:100',
            'contacts.*.phone' => 'nullable|string|max:20',
            'contacts.*.alternate_phone' => 'nullable|string|max:20',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.location' => 'nullable|string|max:100',
            'contacts.*.level' => 'nullable|in:primary,secondary,accounts,support',
            'contacts.*.is_primary' => 'nullable|boolean',
            'addresses' => 'nullable|array',
            'addresses.*.type' => 'nullable|in:billing,shipping,warehouse',
            'addresses.*.label' => 'nullable|string|max:100',
            'addresses.*.name' => 'nullable|string|max:255',
            'addresses.*.address' => 'nullable|string',
            'addresses.*.state' => 'nullable|string|max:100',
            'addresses.*.pincode' => 'nullable|string|max:12',
            'addresses.*.gstin' => 'nullable|string|max:20',
            'addresses.*.is_default' => 'nullable|boolean',
            'bank_accounts' => 'nullable|array',
            'bank_accounts.*.account_holder_name' => 'nullable|string|max:255',
            'bank_accounts.*.bank_name' => 'nullable|string|max:255',
            'bank_accounts.*.branch_name' => 'nullable|string|max:255',
            'bank_accounts.*.account_number' => 'nullable|string|max:50',
            'bank_accounts.*.account_type' => 'nullable|in:current,savings',
            'bank_accounts.*.ifsc_code' => 'nullable|string|max:20',
            'bank_accounts.*.is_primary' => 'nullable|boolean',
            'credit_cheques' => 'nullable|array',
            'credit_cheques.*.cheque_number' => 'nullable|string|max:50',
            'credit_cheques.*.bank_name' => 'nullable|string|max:255',
            'credit_cheques.*.branch_name' => 'nullable|string|max:255',
            'credit_cheques.*.cheque_date' => 'nullable|date',
            'credit_cheques.*.amount' => 'nullable|numeric|min:0',
            'credit_cheques.*.cheque_type' => 'nullable|in:regular,security,pdc',
            'credit_cheques.*.status' => 'nullable|in:pending,received,deposited,cleared,cancelled,bounced',
            'credit_cheques.*.notes' => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['credit_limit'] = $data['credit_limit'] ?? 0;
        $data['credit_days'] = $data['credit_days'] ?? 0;
        $data['interest_rate'] = $data['interest_rate'] ?? 18;
        $data['credit_period_basis'] = $data['credit_period_basis'] ?? 'cumulative';
        $data['credit_status'] = $data['credit_status'] ?? 'open';
        $data['company_id'] = $customer?->company_id ?? auth()->user()?->company_id;
        $data['branch_id'] = $customer?->branch_id ?? auth()->user()?->branch_id;

        unset($data['contacts'], $data['addresses'], $data['bank_accounts'], $data['credit_cheques']);

        return $data;
    }

    protected function syncChildRows(Request $request, Customer $customer): void
    {
        // 1. Contacts
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

        // 2. Addresses
        $addresses = collect($request->input('addresses', []))
            ->filter(fn ($row) => filled($row['address'] ?? null) || filled($row['label'] ?? null) || filled($row['name'] ?? null))
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

        // 3. Bank Accounts
        $bankAccounts = collect($request->input('bank_accounts', []))
            ->filter(fn ($row) => filled($row['account_number'] ?? null) || filled($row['bank_name'] ?? null) || filled($row['ifsc_code'] ?? null))
            ->map(fn ($row) => [
                'account_holder_name' => $row['account_holder_name'] ?? null,
                'bank_name' => $row['bank_name'] ?? null,
                'branch_name' => $row['branch_name'] ?? null,
                'account_number' => $row['account_number'] ?? null,
                'account_type' => in_array($row['account_type'] ?? '', ['current', 'savings'], true) ? $row['account_type'] : 'current',
                'ifsc_code' => strtoupper(trim((string) ($row['ifsc_code'] ?? ''))),
                'is_primary' => (bool) ($row['is_primary'] ?? false),
                'is_active' => true,
            ])
            ->values();

        $customer->bankAccounts()->delete();
        foreach ($bankAccounts as $row) {
            $customer->bankAccounts()->create($row);
        }

        // 4. Credit Cheques
        $creditCheques = collect($request->input('credit_cheques', []))
            ->filter(fn ($row) => filled($row['cheque_number'] ?? null))
            ->map(fn ($row) => [
                'cheque_number' => $row['cheque_number'],
                'bank_name' => $row['bank_name'] ?? null,
                'branch_name' => $row['branch_name'] ?? null,
                'cheque_date' => $row['cheque_date'] ?? null,
                'amount' => (float) ($row['amount'] ?? 0),
                'cheque_type' => $row['cheque_type'] ?? 'regular',
                'status' => in_array($row['status'] ?? '', ['pending', 'received', 'deposited', 'cleared', 'cancelled', 'bounced'], true) ? $row['status'] : 'pending',
                'notes' => $row['notes'] ?? null,
            ])
            ->values();

        $customer->creditCheques()->delete();
        foreach ($creditCheques as $row) {
            $customer->creditCheques()->create($row);
        }
    }

    protected function formData(): array
    {
        $allUsers = User::orderBy('name')->get();

        $salesManagers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['sales-manager', 'sales_manager', 'client-admin', 'admin', 'super-admin']);
        })->orderBy('name')->get();

        if ($salesManagers->isEmpty()) {
            $salesManagers = $allUsers;
        }

        $salespersons = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['salesperson', 'sales_person', 'sales-executive']);
        })->orderBy('name')->get();

        if ($salespersons->isEmpty()) {
            $salespersons = $allUsers;
        }

        return [
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'routes' => Route::where('is_active', true)->orderBy('name')->get(),
            'salesManagers' => $salesManagers,
            'salespersons' => $salespersons,
            'states' => IndianStates::all(),
        ];
    }
}