<?php

namespace App\Http\Controllers\Master;

use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\LeadActivity;
use App\Domains\Crm\Models\LeadConversion;
use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Route;
use App\Domains\Master\Services\GstLookupService;
use App\Domains\Master\Services\IfscLookupService;
use App\Domains\Organization\Models\BusinessGroup;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\CodeGenerator;
use App\Support\IndianCities;
use App\Support\IndianStates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

use App\Support\Traits\SortableAndSearchable;

class CustomerController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected GstLookupService $gstLookupService,
        protected IfscLookupService $ifscLookupService,
        protected AuditLogService $auditLogService
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

    public function cities(Request $request): JsonResponse
    {
        $state = $request->query('state');
        $cities = IndianCities::getCitiesForState($state);

        return response()->json([
            'success' => true,
            'state' => $state,
            'cities' => $cities,
        ]);
    }

    /**
     * Search and list active Sister Concern Business Groups for Party selection.
     */
    public function businessGroups(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', $request->input('q', '')));

        $query = BusinessGroup::query()
            ->where('is_active', true);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('include_id')) {
            $includeId = (int) $request->input('include_id');
            $query->orWhere('id', $includeId);
        }

        $groups = $query->orderBy('name')
            ->get(['id', 'name', 'code', 'logo_emoji']);

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    public function index(Request $request): View
    {
        $query = Customer::query()
            ->with(['customerType', 'area', 'route', 'salesperson', 'salesManager', 'businessGroup'])
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
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        $this->applySearch($query, $request->input('search'), ['name', 'code', 'phone', 'gstin', 'email']);

        $sortData = $this->applySorting(
            $query,
            $request,
            ['name', 'code', 'party_type', 'phone', 'gstin', 'is_active', 'created_at'],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('masters.customers.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'partyType' => $request->string('party_type'),
            'status' => $request->string('status'),
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $companyId = auth()->user()?->company_id;
        $code = CodeGenerator::forParty($companyId);
        $lead = null;

        $leadId = $request->query('lead_id', old('lead_id'));
        if ($leadId) {
            $lead = Lead::forUserBranch()->find($leadId);
            if (! $lead) {
                return redirect()->route('crm.leads.index')->with('error', 'The requested lead was not found or you do not have permission to access it.');
            }

            if ($lead->status === 'converted' || $lead->converted_customer_id) {
                return redirect()->route('crm.leads.index')->with('error', 'This lead has already been converted to a customer.');
            }

            if ($lead->status !== 'qualified') {
                return redirect()->route('crm.leads.index')->with('error', 'Only leads with Qualified status can be converted to a customer.');
            }
        }

        $customer = new Customer([
            'code' => $code,
            'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'is_active' => true,
        ]);

        if ($lead) {
            $customer->name = $lead->company_name ?: ($lead->organization ?: $lead->name);
            $customer->phone = $lead->mobile ?: $lead->phone;
            $customer->email = $lead->email ?: $lead->secondary_email;
            $customer->state = $lead->state;
            $customer->pincode = $lead->zip;
            $customer->address = $lead->street ?: (trim(($lead->city ?? '').' '.($lead->state ?? '')) ?: null);
            $customer->salesperson_id = $lead->assigned_to;
        }

        $initialAddresses = [
            [
                'id' => null,
                'label' => $lead ? 'Primary Office' : 'Head Office / Billing',
                'contact_person' => $lead ? ($lead->name ?? '') : '',
                'contact_phone' => $lead ? ($lead->mobile ?: $lead->phone ?: '') : '',
                'address_line_1' => $lead ? ($lead->street ?: ($lead->city ?? '')) : '',
                'address_line_2' => '',
                'city' => $lead ? ($lead->city ?? '') : '',
                'state' => $lead ? ($lead->state ?? '') : '',
                'pincode' => $lead ? ($lead->zip ?? '') : '',
                'type' => 'both',
                'is_default_billing' => true,
                'is_default_delivery' => true,
            ],
        ];

        return view('masters.customers.form', [
            'item' => $customer,
            'initialAddresses' => $initialAddresses,
            'lead' => $lead,
            ...$this->formData($customer),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $lead = null;
        if ($request->filled('lead_id')) {
            $lead = Lead::forUserBranch()->find($request->input('lead_id'));
            if (! $lead) {
                return redirect()->route('crm.leads.index')->with('error', 'The referenced lead was not found or you do not have permission to access it.');
            }

            if ($lead->status === 'converted' || $lead->converted_customer_id) {
                return redirect()->route('crm.leads.index')->with('error', 'This lead has already been converted to a customer.');
            }

            if ($lead->status !== 'qualified') {
                return redirect()->route('crm.leads.index')->with('error', 'Only leads with Qualified status can be converted to a customer.');
            }
        }

        $data = $this->validated($request);

        $customer = DB::transaction(function () use ($data, $request, $lead) {
            $companyId = $data['company_id'] ?? auth()->user()?->company_id ?? \App\Domains\Organization\Models\Company::defaultCompanyId();

            if (blank($data['code'] ?? null) || Customer::where('code', $data['code'])->exists()) {
                $data['code'] = CodeGenerator::forParty($companyId);
                while (Customer::where('code', $data['code'])->exists()) {
                    $data['code'] = CodeGenerator::forParty($companyId);
                }
            }

            $customer = Customer::create($data);
            $this->syncChildRows($request, $customer);

            if ($lead) {
                // If no contact rows were entered in form, create primary contact from lead
                if ($customer->contacts()->count() === 0 && filled($lead->name)) {
                    $customer->contacts()->create([
                        'name' => $lead->name,
                        'role' => $lead->title ?: 'Primary Contact',
                        'phone' => $lead->mobile ?: $lead->phone,
                        'alternate_phone' => $lead->secondary_mobile ?: $lead->landline,
                        'email' => $lead->email ?: $lead->secondary_email,
                        'is_primary' => true,
                        'is_active' => true,
                    ]);
                }

                LeadConversion::create([
                    'lead_id' => $lead->id,
                    'customer_id' => $customer->id,
                    'converted_by' => auth()->id(),
                    'converted_at' => now(),
                    'notes' => 'Converted via Add Customer form.',
                ]);

                $lead->update([
                    'status' => 'converted',
                    'category' => Lead::CATEGORY_CUSTOMER,
                    'converted_customer_id' => $customer->id,
                    'converted_at' => now(),
                ]);

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => auth()->id(),
                    'activity_type' => 'status_change',
                    'body' => "Converted to customer {$customer->name} (#{$customer->code})",
                ]);

                $this->auditLogService->record($lead, 'converted');
            }

            return $customer;
        });

        if ($lead) {
            return $this->flashSuccess("Lead '{$lead->name}' converted to Customer '{$customer->name}' ({$customer->code}) successfully.", 'masters.customers.index');
        }

        return $this->flashSuccess('Party created successfully.', 'masters.customers.index');
    }

    public function edit(Customer $customer): View
    {
        $customer->load(['contacts', 'addresses', 'bankAccounts', 'creditCheques', 'businessGroup']);

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
            ...$this->formData($customer),
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

    public function destroy(Request $request, Customer $customer): RedirectResponse|JsonResponse
    {
        $user = $request->user() ?? auth()->user();

        // Enforce authorization
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('masters.manage') &&
            ! $user->can('masters.edit') &&
            ! $user->can('masters.delete') &&
            ! $user->can('customers.manage') &&
            ! $user->can('customers.edit') &&
            ! $user->can('customers.delete')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to delete parties.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: You do not have permission to delete parties.');
        }

        // Multi-tenancy company boundary check
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && $customer->company_id && (int) $customer->company_id !== (int) $user->company_id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This party belongs to another company.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: This party belongs to another company.');
        }

        $partyName = $customer->name;
        $partyCode = $customer->code;

        try {
            // Check for existing transactions & historical records
            $hasOrders = Schema::hasTable('orders') && DB::table('orders')->where('customer_id', $customer->id)->exists();
            $hasInvoices = Schema::hasTable('invoices') && DB::table('invoices')->where('customer_id', $customer->id)->exists();
            $hasPurchaseOrders = Schema::hasTable('purchase_orders') && DB::table('purchase_orders')->where('supplier_id', $customer->id)->exists();
            $hasPurchaseInvoices = Schema::hasTable('purchase_invoices') && DB::table('purchase_invoices')->where('supplier_id', $customer->id)->exists();
            $hasPurchases = Schema::hasTable('purchases') && DB::table('purchases')->where('supplier_party_id', $customer->id)->exists();
            $hasPayments = Schema::hasTable('payments') && DB::table('payments')->where('customer_id', $customer->id)->exists();
            $hasCreditNotes = Schema::hasTable('credit_notes') && DB::table('credit_notes')->where('customer_id', $customer->id)->exists();
            $hasCheques = Schema::hasTable('cheques') && DB::table('cheques')->where('customer_id', $customer->id)->exists();
            $hasQuotations = Schema::hasTable('quotations') && DB::table('quotations')->where('customer_id', $customer->id)->exists();
            $hasDeals = Schema::hasTable('deals') && DB::table('deals')->where('customer_id', $customer->id)->exists();

            $hasTransactions = $hasOrders || $hasInvoices || $hasPurchaseOrders || $hasPurchaseInvoices
                || $hasPurchases || $hasPayments || $hasCreditNotes || $hasCheques || $hasQuotations || $hasDeals;

            if ($hasTransactions) {
                // Safe archival / soft delete:
                // Mark inactive, soft delete, and deactivate child addresses/contacts so that
                // all historical financial and operational transactions remain completely intact.
                DB::transaction(function () use ($customer) {
                    $customer->is_active = false;
                    $customer->save();
                    $customer->delete();

                    $customer->addresses()->update(['is_active' => false]);
                    $customer->contacts()->update(['is_active' => false]);
                });

                $deps = [];
                if ($hasOrders) $deps[] = 'orders';
                if ($hasInvoices) $deps[] = 'sales invoices';
                if ($hasPurchaseOrders) $deps[] = 'purchase orders';
                if ($hasPurchaseInvoices || $hasPurchases) $deps[] = 'purchases';
                if ($hasPayments || $hasCreditNotes || $hasCheques) $deps[] = 'payment records';
                if ($hasQuotations) $deps[] = 'quotations';
                if ($hasDeals) $deps[] = 'deals';
                $depText = implode(', ', $deps);

                $message = "Party '{$partyName}' ({$partyCode}) is linked to historical records ({$depText}) and has been safely archived.";

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'archived' => true,
                    ]);
                }

                return $this->flashSuccess($message, 'masters.customers.index')
                    ->with('success', $message);
            }

            // No historical transactions: permanent deletion is safe
            DB::transaction(function () use ($customer) {
                $customer->addresses()->delete();
                $customer->contacts()->delete();
                $customer->bankAccounts()->delete();
                $customer->creditCheques()->delete();

                if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'converted_customer_id')) {
                    DB::table('leads')->where('converted_customer_id', $customer->id)->update(['converted_customer_id' => null]);
                }
                if (Schema::hasTable('target_periods') && Schema::hasColumn('target_periods', 'supplier_id')) {
                    DB::table('target_periods')->where('supplier_id', $customer->id)->update(['supplier_id' => null]);
                }
                if (Schema::hasTable('party_targets')) {
                    DB::table('party_targets')->where('customer_id', $customer->id)->delete();
                }

                $customer->forceDelete();
            });

            $message = "Party '{$partyName}' ({$partyCode}) deleted successfully.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return $this->flashSuccess($message, 'masters.customers.index')
                ->with('success', $message);
        } catch (\Throwable $e) {
            report($e);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete party: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to delete party: ' . $e->getMessage());
        }
    }

    public function show(Customer $customer): View|RedirectResponse
{
    $customer->load([
        'customerType',
        'area',
        'route',
        'salesperson',
        'salesManager',
        'contacts',
        'addresses',
        'bankAccounts',
    ]);

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
        $status = ! empty($result['success']) ? 200 : ($result['status_code'] ?? 422);

        return response()->json($result, $status);
    }

    /**
     * Look up bank / branch details from an IFSC code.
     */
    public function ifscLookup(Request $request): JsonResponse
    {
        $ifsc = $request->input('ifsc', $request->query('ifsc'));

        if (! $ifsc) {
            return response()->json([
                'success' => false,
                'message' => 'IFSC parameter is required.',
            ], 422);
        }

        $result = $this->ifscLookupService->lookup((string) $ifsc);
        $status = ! empty($result['success']) ? 200 : ($result['status_code'] ?? 422);

        return response()->json($result, $status);
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
            'pan' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:12',
            'address' => 'nullable|string',
        ]);

        $validated['party_type'] = match ($validated['party_type']) {
            'customer', 'dealer' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            'supplier' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            default => $validated['party_type'],
        };

        $defaultTypeId = CustomerType::query()->value('id');
        if (! $defaultTypeId) {
            $defaultTypeId = CustomerType::create([
                'name' => 'General',
                'code' => 'GEN',
                'is_active' => true,
            ])->id;
        }
        $companyId = auth()->user()?->company_id ?? \App\Domains\Organization\Models\Company::defaultCompanyId();
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

        $city = $validated['city'] ?? null;
        unset($validated['city'], $validated['pan']);

        $customer = Customer::create($validated);

        // Always create a default address record so address snapshots and shipping/billing pickers work immediately
        $addressLine = $validated['address'] ?? '';
        $customer->addresses()->create([
            'type' => 'both',
            'label' => 'Main Office',
            'name' => $customer->name,
            'address_line_1' => $addressLine,
            'address' => $addressLine,
            'city' => $city,
            'state' => $validated['state'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
            'gstin' => $validated['gstin'] ?? null,
            'is_default' => true,
            'is_default_billing' => true,
            'is_default_delivery' => true,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully.',
            'party' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'code' => $customer->code,
                'party_type' => $customer->party_type,
                'gstin' => $customer->gstin,
                'pan' => $customer->pan,
                'state' => $customer->state,
                'city' => $city,
                'phone' => $customer->phone,
                'email' => $customer->email,
            ],
        ]);
    }

    protected function validated(Request $request, ?Customer $customer = null): array
    {
        $rules = [
            'lead_id' => 'nullable|integer|exists:leads,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30|unique:customers,code'.($customer ? ','.$customer->id : ''),
            'party_type' => 'required|in:sundry_debtors,sundry_creditors,both,dealer,customer,supplier',
            'business_group_id' => 'nullable|integer|exists:business_groups,id',
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
            'contacts.*.level' => 'nullable|in:party,branch,site,warehouse,transporter,driver,site_incharge,primary,secondary,accounts,support',
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
            'bank_accounts.*.id' => 'nullable|integer',
            'bank_accounts.*.account_holder_name' => 'nullable|string|max:255',
            'bank_accounts.*.bank_name' => 'nullable|string|max:255',
            'bank_accounts.*.branch_name' => 'nullable|string|max:255',
            'bank_accounts.*.account_number' => 'nullable|string|max:50',
            'bank_accounts.*.account_type' => 'nullable|in:current,savings',
            'bank_accounts.*.ifsc_code' => 'nullable|string|max:20',
            'bank_accounts.*.is_primary' => 'nullable|boolean',
            'credit_cheques' => 'nullable|array',
            'credit_cheques.*.id' => 'nullable|integer',
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
            if (is_array($addresses)) {
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
            }

            // Cheque validation: if cheque details are provided, cheque_number is required
            $cheques = $request->input('credit_cheques', []);
            if (is_array($cheques)) {
                foreach ($cheques as $index => $chq) {
                    $hasAnyField = filled($chq['bank_name'] ?? null)
                        || filled($chq['branch_name'] ?? null)
                        || filled($chq['cheque_date'] ?? null)
                        || filled($chq['amount'] ?? null)
                        || filled($chq['notes'] ?? null);
                    if ($hasAnyField && blank($chq['cheque_number'] ?? null)) {
                        $validator->errors()->add(
                            "credit_cheques.{$index}.cheque_number",
                            "Cheque number is required when cheque details are provided."
                        );
                    }
                }
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

        if ($request->filled('lead_id') && empty($data['branch_id'])) {
            $leadBranchId = Lead::where('id', $request->lead_id)->value('branch_id');
            if ($leadBranchId) {
                $data['branch_id'] = $leadBranchId;
            }
        }

        unset($data['contacts'], $data['addresses'], $data['bank_accounts'], $data['pan'], $data['lead_id']);

        return $data;
    }

    protected function syncChildRows(Request $request, Customer $customer): void
    {
        // 1. Contacts
        $contacts = collect($request->input('contacts', []))
            ->filter(fn ($row) => filled($row['name'] ?? null))
            ->map(fn ($row) => [
                'level' => in_array($row['level'] ?? null, ['party', 'branch', 'site', 'warehouse', 'transporter', 'driver', 'site_incharge'], true) ? $row['level'] : 'party',
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
            ->filter(function ($row) {
                return filled(trim((string) ($row['account_number'] ?? '')))
                    || filled(trim((string) ($row['bank_name'] ?? '')))
                    || filled(trim((string) ($row['ifsc_code'] ?? '')))
                    || filled(trim((string) ($row['account_holder_name'] ?? '')));
            })
            ->map(fn ($row) => [
                'id' => !empty($row['id']) ? (int) $row['id'] : null,
                'account_holder_name' => trim((string) ($row['account_holder_name'] ?? '')) ?: null,
                'bank_name' => trim((string) ($row['bank_name'] ?? '')) ?: null,
                'branch_name' => trim((string) ($row['branch_name'] ?? '')) ?: null,
                'account_number' => trim((string) ($row['account_number'] ?? '')) ?: null,
                'account_type' => in_array($row['account_type'] ?? '', ['current', 'savings'], true) ? $row['account_type'] : 'current',
                'ifsc_code' => strtoupper(trim((string) ($row['ifsc_code'] ?? ''))) ?: null,
                'is_primary' => filter_var($row['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_active' => true,
            ])
            ->values();

        if ($bankAccounts->isNotEmpty() && !$bankAccounts->contains('is_primary', true)) {
            $bankAccounts[0]['is_primary'] = true;
        }

        $existingBankAccounts = $customer->bankAccounts()->get()->keyBy('id');
        $handledBankIds = [];
        foreach ($bankAccounts as $row) {
            $id = $row['id'];
            $payload = collect($row)->except('id')->all();
            if ($id && $existingBankAccounts->has($id)) {
                $existingBankAccounts[$id]->update($payload);
                $handledBankIds[] = $id;
            } else {
                $created = $customer->bankAccounts()->create($payload);
                $handledBankIds[] = $created->id;
            }
        }
        foreach ($existingBankAccounts as $existingId => $acc) {
            if (!in_array($existingId, $handledBankIds, true)) {
                $acc->delete();
            }
        }

        // 4. Credit Cheques (only synchronize if explicitly provided in request)
        if ($request->has('credit_cheques')) {
            $creditCheques = collect($request->input('credit_cheques', []))
                ->filter(fn ($row) => filled($row['cheque_number'] ?? null))
                ->map(fn ($row) => [
                    'id' => !empty($row['id']) ? (int) $row['id'] : null,
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

            $existingCreditCheques = $customer->creditCheques()->get()->keyBy('id');
            $handledChequeIds = [];
            foreach ($creditCheques as $row) {
                $id = $row['id'];
                $payload = collect($row)->except('id')->all();
                if ($id && $existingCreditCheques->has($id)) {
                    $existingCreditCheques[$id]->update($payload);
                    $handledChequeIds[] = $id;
                } else {
                    $created = $customer->creditCheques()->create($payload);
                    $handledChequeIds[] = $created->id;
                }
            }
            foreach ($existingCreditCheques as $existingId => $chq) {
                if (!in_array($existingId, $handledChequeIds, true)) {
                    $chq->delete();
                }
            }
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

    protected function formData(?Customer $customer = null): array
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

        $bgQuery = BusinessGroup::query()->where('is_active', true);
        if ($customer && $customer->business_group_id) {
            $bgQuery->orWhere('id', $customer->business_group_id);
        }
        $businessGroups = $bgQuery->orderBy('name')->get(['id', 'name', 'code', 'logo_emoji']);

        return [
            'businessGroups' => $businessGroups,
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'routes' => Route::where('is_active', true)->orderBy('name')->get(),
            'salesManagers' => $salesManagers,
            'salespersons' => $salespersons,
            'states' => IndianStates::allAlphabetical(),
            'stateOptions' => IndianStates::options(),
            'stateCities' => IndianCities::all(),
        ];
    }
}