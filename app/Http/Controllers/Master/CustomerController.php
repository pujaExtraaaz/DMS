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
use App\Support\IndianCities;
use App\Support\IndianStates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected GstLookupService $gstLookupService
    ) {}

    /**
     * Get active addresses for a party/customer.
     */
    public function addresses(Customer $customer): JsonResponse
    {
        $addresses = $customer->activeAddresses()->get()->map(function ($addr) {
            return [
                'id' => $addr->id,
                'label' => $addr->label ?? 'Address #' . $addr->id,
                'type' => $addr->type ?? 'both',
                'contact_person' => $addr->contact_person,
                'contact_phone' => $addr->contact_phone,
                'address_line_1' => $addr->address_line_1 ?? $addr->address,
                'address_line_2' => $addr->address_line_2,
                'city' => $addr->city,
                'state' => $addr->state,
                'pincode' => $addr->pincode,
                'gstin' => $addr->gstin,
                'is_default_billing' => (bool) ($addr->is_default_billing || $addr->is_default),
                'is_default_delivery' => (bool) ($addr->is_default_delivery),
                'display_text' => $addr->display_text,
                'full_address' => $addr->full_address,
                'snapshot' => $addr->formatSnapshot(),
            ];
        });

        $defaultBilling = $addresses->firstWhere('is_default_billing', true)
            ?? $addresses->firstWhere('type', 'billing')
            ?? $addresses->firstWhere('type', 'both')
            ?? $addresses->first();

        $defaultDelivery = $addresses->firstWhere('is_default_delivery', true)
            ?? $addresses->firstWhere('type', 'delivery')
            ?? $addresses->firstWhere('type', 'both')
            ?? $defaultBilling;

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'code' => $customer->code,
                'gstin' => $customer->gstin,
                'state' => $customer->state,
            ],
            'addresses' => $addresses,
            'default_billing_id' => $defaultBilling ? $defaultBilling['id'] : null,
            'default_delivery_id' => $defaultDelivery ? $defaultDelivery['id'] : null,
        ]);
    }

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
            ->when($request->filled('party_type'), function ($q) use ($request) {
                $type = $request->party_type;
                if ($type === Customer::PARTY_TYPE_SUNDRY_DEBTORS) {
                    $q->whereIn('party_type', [Customer::PARTY_TYPE_SUNDRY_DEBTORS, 'customer', 'dealer']);
                } elseif ($type === Customer::PARTY_TYPE_SUNDRY_CREDITORS) {
                    $q->whereIn('party_type', [Customer::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier']);
                } else {
                    $q->where('party_type', $type);
                }
            })
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
        $companyId = auth()->user()?->company_id;
        $code = CodeGenerator::forParty($companyId);

        $customer = new Customer([
            'code' => $code,
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        $initialAddresses = [
            [
                'id' => null,
                'label' => 'Head Office / Billing',
                'contact_person' => '',
                'contact_phone' => '',
                'address_line_1' => '',
                'address_line_2' => '',
                'city' => '',
                'state' => '',
                'pincode' => '',
                'type' => 'both',
                'is_default_billing' => true,
                'is_default_delivery' => true,
            ],
        ];

        return view('masters.customers.form', [
            'item' => $customer,
            'initialAddresses' => $initialAddresses,
            ...$this->formData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $customer = DB::transaction(function () use ($data, $request) {
            $companyId = $data['company_id'] ?? auth()->user()?->company_id;

            if (blank($data['code'] ?? null) || Customer::where('code', $data['code'])->exists()) {
                $data['code'] = CodeGenerator::forParty($companyId);
                while (Customer::where('code', $data['code'])->exists()) {
                    $data['code'] = CodeGenerator::forParty($companyId);
                }
            }

            $customer = Customer::create($data);
            $this->syncChildRows($request, $customer);

            return $customer;
        });

        return $this->flashSuccess('Party created successfully.', 'masters.customers.index');
    }

    public function edit(Customer $customer): View
    {
        $customer->load(['contacts', 'addresses', 'bankAccounts', 'creditCheques']);

        $addresses = $customer->activeAddresses()->get()->map(function ($a) {
            return [
                'id' => $a->id,
                'label' => $a->label ?? 'Office',
                'contact_person' => $a->contact_person ?? '',
                'contact_phone' => $a->contact_phone ?? '',
                'address_line_1' => $a->address_line_1 ?? $a->address ?? '',
                'address_line_2' => $a->address_line_2 ?? '',
                'city' => $a->city ?? '',
                'state' => $a->state ?? '',
                'pincode' => $a->pincode ?? '',
                'type' => $a->type ?? 'both',
                'is_default_billing' => (bool) ($a->is_default_billing || $a->is_default),
                'is_default_delivery' => (bool) ($a->is_default_delivery),
            ];
        })->values()->all();

        if (empty($addresses)) {
            $addresses[] = [
                'id' => null,
                'label' => 'Head Office / Billing',
                'contact_person' => '',
                'contact_phone' => $customer->phone ?? '',
                'address_line_1' => $customer->address ?? '',
                'address_line_2' => '',
                'city' => '',
                'state' => $customer->state ?? '',
                'pincode' => $customer->pincode ?? '',
                'type' => 'both',
                'is_default_billing' => true,
                'is_default_delivery' => true,
            ];
        }

        return view('masters.customers.form', [
            'item' => $customer,
            'initialAddresses' => $addresses,
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validated($request, $customer);

        // Keep the original party code unchanged on edit
        $data['code'] = $customer->code;

        DB::transaction(function () use ($data, $request, $customer) {
            $customer->update($data);
            $this->syncChildRows($request, $customer);
        });

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
            'party_type' => 'required|in:sundry_debtors,sundry_creditors,both,dealer,customer,supplier',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gstin' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:12',
            'address' => 'nullable|string',
        ]);

        $validated['party_type'] = match ($validated['party_type']) {
            'customer', 'dealer' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'supplier' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            default => $validated['party_type'],
        };

        $defaultTypeId = CustomerType::query()->value('id');
        $companyId = auth()->user()?->company_id;
        $validated['customer_type_id'] = $validated['customer_type_id'] ?? $defaultTypeId;
        if (blank($validated['code'] ?? null) || Customer::where('code', $validated['code'])->exists()) {
            $validated['code'] = CodeGenerator::forParty($companyId);
            while (Customer::where('code', $validated['code'])->exists()) {
                $validated['code'] = CodeGenerator::forParty($companyId);
            }
        }
        $validated['company_id'] = $companyId;
        $validated['branch_id'] = auth()->user()?->branch_id;
        $validated['is_active'] = true;

        $customer = Customer::create($validated);

        if (filled($validated['address'] ?? null)) {
            $customer->addresses()->create([
                'type' => 'both',
                'label' => 'Main Office',
                'name' => $customer->name,
                'address_line_1' => $validated['address'],
                'address' => $validated['address'],
                'state' => $validated['state'] ?? null,
                'pincode' => $validated['pincode'] ?? null,
                'gstin' => $validated['gstin'] ?? null,
                'is_default' => true,
                'is_default_billing' => true,
                'is_default_delivery' => true,
                'is_active' => true,
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
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30|unique:customers,code'.($customer ? ','.$customer->id : ''),
            'party_type' => 'required|in:sundry_debtors,sundry_creditors,both,dealer,customer,supplier',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'area_id' => 'nullable|exists:areas,id',
            'route_id' => 'nullable|exists:routes,id',
            'salesperson_id' => 'nullable|exists:users,id',
            'sales_manager_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gstin' => 'nullable|string|max:20',
            'pan' => 'nullable|string|max:20',
            'address' => 'nullable|string',
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
            'addresses.*.id' => 'nullable|integer',
            'addresses.*.type' => 'nullable|in:billing,delivery,both,shipping,warehouse',
            'addresses.*.label' => 'nullable|string|max:100',
            'addresses.*.contact_person' => 'nullable|string|max:255',
            'addresses.*.contact_phone' => 'nullable|string|max:20',
            'addresses.*.address_line_1' => 'nullable|string|max:255',
            'addresses.*.address_line_2' => 'nullable|string|max:255',
            'addresses.*.address' => 'nullable|string',
            'addresses.*.city' => 'nullable|string|max:100',
            'addresses.*.state' => 'nullable|string|max:100',
            'addresses.*.pincode' => 'nullable|string|max:12',
            'addresses.*.gstin' => 'nullable|string|max:20',
            'addresses.*.is_default' => 'nullable',
            'addresses.*.is_default_billing' => 'nullable',
            'addresses.*.is_default_delivery' => 'nullable',
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
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request) {
            $addresses = $request->input('addresses', []);
            if (!is_array($addresses)) {
                return;
            }

            $defaultBillingCount = 0;
            $defaultDeliveryCount = 0;

            foreach ($addresses as $index => $addr) {
                $state = $addr['state'] ?? null;
                $city = $addr['city'] ?? null;

                if (filled($city) && filled($state)) {
                    if (! IndianCities::isValidCityForState($city, $state)) {
                        $validator->errors()->add(
                            "addresses.{$index}.city",
                            "The selected city \"{$city}\" does not belong to the state \"{$state}\"."
                        );
                    }
                }

                $isDefaultBilling = filter_var($addr['is_default_billing'] ?? false, FILTER_VALIDATE_BOOLEAN)
                    || filter_var($addr['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $isDefaultDelivery = filter_var($addr['is_default_delivery'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($isDefaultBilling) {
                    $defaultBillingCount++;
                }
                if ($isDefaultDelivery) {
                    $defaultDeliveryCount++;
                }
            }

            if ($defaultBillingCount > 1) {
                $validator->errors()->add('addresses', 'Only one address can be set as the default billing address.');
            }
            if ($defaultDeliveryCount > 1) {
                $validator->errors()->add('addresses', 'Only one address can be set as the default delivery address.');
            }
        });

        $data = $validator->validate();

        $data['party_type'] = match ($data['party_type']) {
            'customer', 'dealer' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'supplier' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            default => $data['party_type'],
        };
        $data['is_active'] = $request->boolean('is_active');
        $data['customer_type_id'] = $data['customer_type_id'] ?? CustomerType::query()->value('id');
        $data['credit_limit'] = $data['credit_limit'] ?? 0;
        $data['credit_days'] = $data['credit_days'] ?? 0;
        $data['interest_rate'] = $data['interest_rate'] ?? 18;
        $data['credit_period_basis'] = $data['credit_period_basis'] ?? 'cumulative';
        $data['credit_status'] = $data['credit_status'] ?? 'open';
        $data['company_id'] = $customer?->company_id ?? auth()->user()?->company_id;
        $data['branch_id'] = $customer?->branch_id ?? auth()->user()?->branch_id;

        unset($data['contacts'], $data['addresses'], $data['bank_accounts'], $data['credit_cheques'], $data['pan']);

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
        $this->syncAddresses($request, $customer);

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

    protected function syncAddresses(Request $request, Customer $customer): void
    {
        $rawAddresses = $request->input('addresses', []);
        $submittedAddresses = collect($rawAddresses)
            ->filter(function ($row) {
                return filled($row['address_line_1'] ?? null)
                    || filled($row['address'] ?? null)
                    || filled($row['label'] ?? null)
                    || filled($row['city'] ?? null);
            })
            ->values();

        $existingAddresses = $customer->addresses()->get()->keyBy('id');
        $handledIds = [];

        $hasDefaultBilling = $submittedAddresses->contains(function ($row) {
            return filter_var($row['is_default_billing'] ?? false, FILTER_VALIDATE_BOOLEAN)
                || filter_var($row['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN);
        });

        $hasDefaultDelivery = $submittedAddresses->contains(function ($row) {
            return filter_var($row['is_default_delivery'] ?? false, FILTER_VALIDATE_BOOLEAN);
        });

        if (!$hasDefaultBilling && $submittedAddresses->isNotEmpty()) {
            $firstBillingIdx = $submittedAddresses->search(fn ($r) => in_array($r['type'] ?? '', ['billing', 'both'], true));
            $idx = $firstBillingIdx !== false ? $firstBillingIdx : 0;
            $submittedAddresses[$idx]['is_default_billing'] = true;
        }

        if (!$hasDefaultDelivery && $submittedAddresses->isNotEmpty()) {
            $firstDeliveryIdx = $submittedAddresses->search(fn ($r) => in_array($r['type'] ?? '', ['delivery', 'both', 'shipping'], true));
            $idx = $firstDeliveryIdx !== false ? $firstDeliveryIdx : 0;
            $submittedAddresses[$idx]['is_default_delivery'] = true;
        }

        foreach ($submittedAddresses as $row) {
            $id = !empty($row['id']) ? (int) $row['id'] : null;
            $line1 = trim((string) ($row['address_line_1'] ?? $row['address'] ?? ''));
            $line2 = trim((string) ($row['address_line_2'] ?? ''));
            $compositeAddress = trim($line1 . ($line2 ? ', ' . $line2 : ''));

            $isDefaultBilling = filter_var($row['is_default_billing'] ?? false, FILTER_VALIDATE_BOOLEAN)
                || filter_var($row['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $isDefaultDelivery = filter_var($row['is_default_delivery'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $payload = [
                'type' => in_array($row['type'] ?? '', ['billing', 'delivery', 'both', 'shipping', 'warehouse'], true) ? $row['type'] : 'both',
                'label' => $row['label'] ?? 'Office',
                'contact_person' => $row['contact_person'] ?? null,
                'contact_phone' => $row['contact_phone'] ?? null,
                'address_line_1' => $line1 ?: null,
                'address_line_2' => $line2 ?: null,
                'address' => $compositeAddress ?: null,
                'city' => $row['city'] ?? null,
                'state' => $row['state'] ?? null,
                'pincode' => $row['pincode'] ?? null,
                'gstin' => $row['gstin'] ?? null,
                'is_default_billing' => $isDefaultBilling,
                'is_default_delivery' => $isDefaultDelivery,
                'is_default' => $isDefaultBilling,
                'is_active' => true,
            ];

            if ($id && $existingAddresses->has($id)) {
                $existingAddresses[$id]->update($payload);
                $handledIds[] = $id;
            } else {
                $created = $customer->addresses()->create($payload);
                $handledIds[] = $created->id;
            }
        }

        // Deactivate referenced addresses, delete unreferenced ones
        foreach ($existingAddresses as $existingId => $addr) {
            if (!in_array($existingId, $handledIds, true)) {
                if ($addr->isReferencedInTransactions()) {
                    $addr->update(['is_active' => false]);
                } else {
                    $addr->delete();
                }
            }
        }

        // Sync legacy customer fields from default billing address
        $defBilling = $customer->defaultBillingAddress();
        if ($defBilling) {
            $customer->updateQuietly([
                'address' => $defBilling->full_address ?: $customer->address,
                'state' => $defBilling->state ?: $customer->state,
                'pincode' => $defBilling->pincode ?: $customer->pincode,
            ]);
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
            'stateCities' => IndianCities::all(),
        ];
    }
}