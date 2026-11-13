<?php

namespace App\Http\Controllers\Master;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\LeadActivity;
use App\Domains\Crm\Models\LeadConversion;
use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\CustomCity;
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
use App\Support\NameCase;
use App\Support\TitleCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

use App\Support\Traits\ExportsListing;
use App\Support\Traits\SortableAndSearchable;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    use ExportsListing, SortableAndSearchable;

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
        // Auto-create default party_address if customer has address on customer table but 0 in party_addresses
        if ($customer->activeAddresses()->count() === 0 && filled($customer->address)) {
            $customer->addresses()->create([
                'type' => 'both',
                'label' => 'Main Office',
                'name' => $customer->name,
                'contact_person' => $customer->name,
                'contact_phone' => $customer->phone,
                'address_line_1' => $customer->address,
                'address' => $customer->address,
                'city' => $customer->city,
                'state' => $customer->state,
                'pincode' => $customer->pincode,
                'gstin' => $customer->gstin,
                'is_default' => true,
                'is_default_billing' => true,
                'is_default_delivery' => true,
                'is_active' => true,
            ]);
        }

        $addresses = $customer->activeAddresses()->get()->map(function ($addr) use ($customer) {
            return [
                'id' => $addr->id,
                'label' => $addr->label ?? 'Address #' . $addr->id,
                'name' => $addr->name ?? $customer->name,
                'type' => $addr->type ?? 'both',
                'contact_person' => $addr->contact_person ?: ($addr->name ?: $customer->name),
                'contact_phone' => $addr->contact_phone ?: $customer->phone,
                'address_line_1' => $addr->address_line_1 ?? $addr->address,
                'address_line_2' => $addr->address_line_2,
                'city' => $addr->city ?: $customer->city,
                'state' => $addr->state ?: $customer->state,
                'pincode' => $addr->pincode ?: $customer->pincode,
                'gstin' => $addr->gstin ?: $customer->gstin,
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
                'credit_days' => $customer->credit_days,
            ],
            'addresses' => $addresses,
            'default_billing_id' => $defaultBilling ? $defaultBilling['id'] : null,
            'default_delivery_id' => $defaultDelivery ? $defaultDelivery['id'] : null,
        ]);
    }

    /**
     * Quickly store a delivery/party address for a customer.
     */
    public function storeAddress(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'type' => 'nullable|in:billing,delivery,both,shipping,warehouse',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'gstin' => 'nullable|string|max:20',
            'is_default_delivery' => 'nullable|boolean',
            'is_default_billing' => 'nullable|boolean',
        ]);

        $type = $validated['type'] ?? 'delivery';
        $isDefaultDelivery = (bool) ($validated['is_default_delivery'] ?? false);
        $isDefaultBilling = (bool) ($validated['is_default_billing'] ?? false);

        if ($isDefaultDelivery) {
            $customer->addresses()->update(['is_default_delivery' => false]);
        }
        if ($isDefaultBilling) {
            $customer->addresses()->update(['is_default_billing' => false, 'is_default' => false]);
        }

        $addressLines = array_filter([$validated['address_line_1'] ?? null, $validated['address_line_2'] ?? null]);
        $addressText = implode(', ', $addressLines);

        $address = $customer->addresses()->create([
            'label' => (!empty($validated['label'])) ? $validated['label'] : 'Delivery Branch',
            'name' => (!empty($validated['name'])) ? $validated['name'] : $customer->name,
            'type' => $type,
            'contact_person' => (!empty($validated['contact_person'])) ? $validated['contact_person'] : ($customer->contact_person ?: $customer->name),
            'contact_phone' => (!empty($validated['contact_phone'])) ? $validated['contact_phone'] : $customer->phone,
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => $validated['address_line_2'] ?? null,
            'address' => $addressText,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'pincode' => $validated['pincode'] ?? null,
            'gstin' => (!empty($validated['gstin'])) ? $validated['gstin'] : $customer->gstin,
            'is_default_delivery' => $isDefaultDelivery,
            'is_default_billing' => $isDefaultBilling,
            'is_default' => $isDefaultBilling,
            'is_active' => true,
        ]);

        $formatted = [
            'id' => $address->id,
            'label' => $address->label,
            'name' => $address->name ?? $customer->name,
            'type' => $address->type,
            'contact_person' => $address->contact_person ?: ($address->name ?: $customer->name),
            'contact_phone' => $address->contact_phone ?: $customer->phone,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'city' => $address->city,
            'state' => $address->state,
            'pincode' => $address->pincode,
            'gstin' => $address->gstin,
            'is_default_billing' => (bool) $address->is_default_billing,
            'is_default_delivery' => (bool) $address->is_default_delivery,
            'display_text' => $address->display_text,
            'full_address' => $address->full_address,
            'snapshot' => $address->formatSnapshot(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Delivery address added successfully.',
            'address' => $formatted,
        ], 201);
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

    public function storeCity(Request $request): JsonResponse
    {
        $state = trim((string) $request->input('state', ''));
        $name = NameCase::format(trim((string) $request->input('name', ''))) ?? '';

        $knownState = null;
        foreach (IndianStates::namesAlphabetical() as $stateName) {
            if (strcasecmp($stateName, $state) === 0) {
                $knownState = $stateName;
                break;
            }
        }

        if ($knownState === null) {
            return response()->json([
                'success' => false,
                'message' => 'Select a state before adding a city.',
            ], 422);
        }

        if ($name === '') {
            return response()->json([
                'success' => false,
                'message' => 'City name is required.',
            ], 422);
        }

        if (mb_strlen($name) > 255) {
            return response()->json([
                'success' => false,
                'message' => 'City name cannot exceed 255 characters.',
            ], 422);
        }

        $existing = IndianCities::getCitiesForState($knownState);
        foreach ($existing as $city) {
            if (strcasecmp($city, $name) === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'This city is already in the list.',
                    'item' => ['state' => $knownState, 'name' => $city],
                ]);
            }
        }

        if (! IndianCities::isValidCityForState($name, $knownState)) {
            return response()->json([
                'success' => false,
                'message' => 'This city belongs to another state.',
            ], 422);
        }

        $city = CustomCity::query()->firstOrCreate([
            'state' => $knownState,
            'name' => $name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'City added. It will stay available in this state.',
            'item' => ['state' => $city->state, 'name' => $city->name],
        ], 201);
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

    public function index(Request $request): View|Response
    {
        if ($request->wantsJson() && $request->has('autocomplete')) {
            return $this->autocomplete($request);
        }

        $selectedPartyId = $request->input('party_id');
        $selectedParty = null;
        if ($selectedPartyId) {
            $selectedParty = Customer::with('area')->find($selectedPartyId);
        }

        $query = Customer::query()
            ->with([
                'customerType', 'area', 'route', 'salesperson', 'salesManager', 'businessGroup',
                'salesTargets' => fn ($q) => $q->where('status', 'active')->with('achievement'),
            ])
            ->when($selectedPartyId, function ($q) use ($selectedPartyId) {
                $q->where('id', $selectedPartyId);
            })
            ->when(! $selectedPartyId && $request->filled('search'), function ($q) use ($request) {
                $this->applySearch($q, $request->input('search'), ['name', 'code', 'phone', 'gstin', 'email']);
            })
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
            })
            ->when($request->filled('target_status'), function ($q) use ($request) {
                $status = strtolower(str_replace(' ', '_', (string) $request->target_status));
                if ($status === 'assigned') {
                    $q->where(function ($sub) {
                        $sub->whereNotNull('target_amount')->where('target_amount', '>', 0)
                            ->orWhereHas('salesTargets');
                    });
                } elseif ($status === 'unassigned') {
                    $q->where(function ($sub) {
                        $sub->whereNull('target_amount')->orWhere('target_amount', '<=', 0);
                    })->whereDoesntHave('salesTargets');
                } elseif ($status === 'achieved') {
                    $q->whereHas('salesTargets', function ($tq) {
                        $tq->whereHas('achievement', fn ($aq) => $aq->where('is_achieved', true)->orWhereRaw('achieved_amount >= sales_targets.target_amount'));
                    });
                } elseif ($status === 'in_progress') {
                    $today = now()->toDateString();
                    $q->whereHas('salesTargets', function ($tq) use ($today) {
                        $tq->where('status', 'active')
                            ->where('starts_on', '<=', $today)
                            ->where('ends_on', '>=', $today)
                            ->whereHas('achievement', fn ($aq) => $aq->where('is_achieved', false)->whereRaw('achieved_amount < sales_targets.target_amount'));
                    });
                } elseif ($status === 'period_expired' || $status === 'expired') {
                    $today = now()->toDateString();
                    $q->whereHas('salesTargets', function ($tq) use ($today) {
                        $tq->where('ends_on', '<', $today)
                            ->whereHas('achievement', fn ($aq) => $aq->where('is_achieved', false)->whereRaw('achieved_amount < sales_targets.target_amount'));
                    });
                } elseif ($status === 'not_started') {
                    $today = now()->toDateString();
                    $q->whereHas('salesTargets', function ($tq) use ($today) {
                        $tq->where('starts_on', '>', $today)
                            ->whereHas('achievement', fn ($aq) => $aq->where('is_achieved', false));
                    });
                }
            })
            ->when($request->filled('target_start_date'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('target_start_date', '>=', $request->target_start_date)
                        ->orWhereHas('salesTargets', fn ($tq) => $tq->where('starts_on', '>=', $request->target_start_date));
                });
            })
            ->when($request->filled('target_end_date'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('target_end_date', '<=', $request->target_end_date)
                        ->orWhereHas('salesTargets', fn ($tq) => $tq->where('ends_on', '<=', $request->target_end_date));
                });
            });

        $exportHeaders = [
            'Party Name', 'Code', 'Party Type', 'Phone', 'GSTIN', 'Area',
            'Target Amount (₹)', 'Target Period', 'Achieved Sales (₹)', 'Shortfall (₹)', 'Achievement %', 'Remaining Days', 'Target Status', 'Status'
        ];
        if ($export = $this->exportIfRequested($request, $query, 'Parties', 'Parties', $exportHeaders, function ($item) {
            $target = $item->activeSalesTarget() ?? $item->latestSalesTarget();
            $targetAmount = (float) ($target?->target_amount ?? ($item->target_amount ?? 0));
            $achieved = (float) ($target?->achievement?->achieved_amount ?? 0);
            $shortfall = $targetAmount > 0 ? max(0, $targetAmount - $achieved) : 0;
            $percent = $targetAmount > 0 ? round(($achieved / $targetAmount) * 100, 1) : 0;
            $period = $target ? ($target->starts_on?->format('d-m-Y') . ' to ' . $target->ends_on?->format('d-m-Y')) : '—';
            $status = $target ? $target->progress_status : ($targetAmount > 0 ? 'Assigned' : 'Unassigned');
            $remDays = $target ? $target->remaining_days_label : '—';

            return [
                $item->name,
                $item->code,
                $item->party_type_label,
                $item->phone ?: '',
                $item->gstin ?: '',
                $item->area?->name ?? '',
                $targetAmount > 0 ? number_format($targetAmount, 2) : '—',
                $period,
                $targetAmount > 0 ? number_format($achieved, 2) : '—',
                $targetAmount > 0 ? number_format($shortfall, 2) : '—',
                $targetAmount > 0 ? "{$percent}%" : '—',
                $remDays,
                $status,
                $item->is_active ? 'Active' : 'Inactive',
            ];
        })) {
            return $export;
        }

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
            'selectedPartyId' => $selectedPartyId,
            'selectedParty' => $selectedParty,
            'partyType' => $request->string('party_type'),
            'status' => $request->string('status'),
            'targetStatus' => $request->string('target_status'),
            'targetStartDate' => $request->string('target_start_date'),
            'targetEndDate' => $request->string('target_end_date'),
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    /**
     * Autocomplete search for Parties / Customers listing.
     * Matches on name, code, phone, gstin, email, trade_name.
     * Respects currently selected filters (party_type, area_id, customer_type_id, status).
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q', $request->input('search', $request->input('query', ''))));

        $query = Customer::query()
            ->with(['customerType', 'area'])
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

        // Search party name, trade name, code, phone, gstin, email, and area name/code
        if ($term !== '') {
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('trade_name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('gstin', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhereHas('area', function ($aq) use ($term) {
                        $aq->where('name', 'like', "%{$term}%")
                           ->orWhere('code', 'like', "%{$term}%");
                    });
            });
        }

        $limit = min(25, max(1, (int) $request->input('limit', 10)));

        $parties = $query
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get();

        $results = $parties->map(function (Customer $party) {
            return [
                'id' => $party->id,
                'name' => $party->name,
                'code' => $party->code,
                'party_type' => $party->party_type,
                'party_type_label' => $party->party_type_label,
                'party_type_key' => $party->party_type_key,
                'phone' => $party->phone,
                'email' => $party->email,
                'gstin' => $party->gstin,
                'pan' => $party->pan,
                'city' => $party->city,
                'state' => $party->state,
                'area_name' => $party->area?->name,
                'category_name' => $party->customerType?->name,
                'is_active' => (bool) $party->is_active,
                'view_url' => route('masters.customers.show', $party),
                'edit_url' => route('masters.customers.edit', $party),
            ];
        });

        return response()->json($results);
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

        $requestedPartyType = (string) $request->query('party_type', Customer::PARTY_TYPE_SUNDRY_DEBTORS);
        $allowedPartyTypes = [
            Customer::PARTY_TYPE_SUNDRY_DEBTORS,
            Customer::PARTY_TYPE_SUNDRY_CREDITORS,
            Customer::PARTY_TYPE_BOTH,
        ];
        if (! in_array($requestedPartyType, $allowedPartyTypes, true)) {
            $requestedPartyType = Customer::PARTY_TYPE_SUNDRY_DEBTORS;
        }

        $customer = new Customer([
            'code' => $code,
            'party_type' => $requestedPartyType,
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
            'returnTo' => $request->query('return_to', old('return_to')),
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

        $returnTo = $request->input('return_to');
        if ($returnTo === 'orders.create') {
            return redirect()->route('orders.create', ['customer_id' => $customer->id])
                ->with('status', TitleCase::format("Party '{$customer->name}' created successfully."))
                ->with('success', "Customer '{$customer->name}' created successfully.");
        }

        $returnUrl = $this->safeInternalReturnUrl($request->input('return_url'));
        if ($returnUrl) {
            $separator = str_contains($returnUrl, '?') ? '&' : '?';
            $isPurchasingOrder = str_contains($returnUrl, '/purchasing');
            $isSalesOrder = str_contains($returnUrl, '/orders') && ! $isPurchasingOrder;

            if ($isPurchasingOrder) {
                $target = $returnUrl.$separator.'supplier_id='.$customer->id;
                $message = "Party '{$customer->name}' created successfully. Continue with the purchase document.";
            } elseif ($isSalesOrder) {
                $target = $returnUrl.$separator.'customer_id='.$customer->id;
                $message = "Party '{$customer->name}' created successfully.";
            } else {
                $target = $returnUrl;
                $message = "Party '{$customer->name}' created successfully.";
            }

            return redirect()
                ->to($target)
                ->with('status', TitleCase::format($message))
                ->with('success', "Customer '{$customer->name}' created successfully.");
        }

        return $this->flashSuccess('Party created successfully.', 'masters.customers.index');
    }

    /**
     * Allow redirect only to same-app relative or absolute URLs (open-redirect safe).
     */
    protected function safeInternalReturnUrl(mixed $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);
        if ($url === '' || str_starts_with($url, '//')) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && str_starts_with($url, $appUrl.'/')) {
            return $url;
        }

        if ($appUrl !== '' && $url === $appUrl) {
            return $url;
        }

        return null;
    }

    public function edit(Customer $customer): View
    {
        $customer->load(['contacts', 'addresses', 'bankAccounts', 'creditCheques', 'businessGroup', 'gemAuthorizations.documents', 'gemAuthorizations.brand']);

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

    public function gemDocument(Customer $customer, \App\Domains\Master\Models\PartyGemDocument $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $document->loadMissing('authorization');
        abort_unless(
            $document->authorization && (int) $document->authorization->customer_id === (int) $customer->id,
            404
        );

        $download = request()->boolean('download');

        return Storage::disk($document->disk ?: 'public')->response(
            $document->path,
            $document->original_name,
            [],
            $download ? 'attachment' : 'inline'
        );
    }

    public function show(Request $request, Customer $customer): View|JsonResponse
    {
        $customer->load([
            'company',
            'branch',
            'businessGroup',
            'customerType',
            'area',
            'route',
            'salesperson',
            'salesManager',
            'salesTargets' => fn ($q) => $q->latest('starts_on')->with(['achievement', 'creator']),
            'contacts' => fn ($q) => $q->orderBy('is_primary', 'desc')->orderBy('name'),
            'addresses' => fn ($q) => $q->orderBy('is_default_billing', 'desc')->orderBy('is_default_delivery', 'desc'),
            'bankAccounts' => fn ($q) => $q->orderBy('is_primary', 'desc'),
            'gemAuthorizations.brand',
            'gemAuthorizations.documents',
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'party' => $customer,
            ]);
        }

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
            'trade_name' => 'nullable|string|max:255',
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
            'return_to' => 'nullable|string|max:100',
            'lead_id' => 'nullable|integer|exists:leads,id',
            'name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
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
            'opened_on' => 'nullable|date',
            'reference_by' => 'nullable|string|max:255',
            'udyam_number' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'location_link' => 'nullable|string|max:500',
            'gem_authorizations' => 'nullable|array',
            'gem_authorizations.*.id' => 'nullable|integer',
            'gem_authorizations.*.brand_id' => 'nullable|integer|exists:brands,id',
            'gem_authorizations.*.gem_registered' => 'nullable|in:0,1',
            'gem_authorizations.*.gem_authorized' => 'nullable|in:0,1',
            'gem_authorizations.*.authorization_number' => 'nullable|string|max:100',
            'gem_authorizations.*.remove_document_ids' => 'nullable|array',
            'gem_authorizations.*.remove_document_ids.*' => 'integer',
            'gem_authorizations.*.documents' => 'nullable|array',
            'gem_authorizations.*.documents.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'gstin' => 'nullable|string|max:20',
            'pan' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:12',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'credit_status' => 'nullable|in:open,hold,blocked,restricted,frozen,active',
            'credit_period_basis' => 'nullable|in:cumulative,invoice_date,inward_date,receipt_date,monthly,quarterly,yearly',
            'interest_rate' => 'nullable|numeric|min:0',
            'target_amount' => 'nullable|numeric|min:0',
            'target_start_date' => 'nullable|date',
            'target_end_date' => 'nullable|date|after_or_equal:target_start_date',
            'is_active' => 'boolean',
            'contacts' => 'nullable|array',
            'contacts.*.name' => 'nullable|string|max:255',
            'contacts.*.role' => 'nullable|string|max:100',
            'contacts.*.designation' => 'nullable|string|max:100',
            'contacts.*.custom_role' => 'nullable|string|max:100',
            'contacts.*.other_designation' => 'nullable|string|max:100',
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

            $seenBrands = [];
            foreach ($request->input('gem_authorizations', []) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $registered = ($row['gem_registered'] ?? '0') === '1';
                $active = filled($row['brand_id'] ?? null)
                    || $registered
                    || filled($row['authorization_number'] ?? null)
                    || $request->hasFile("gem_authorizations.{$index}.documents");

                if (! $active) {
                    continue;
                }

                $brandId = (string) ($row['brand_id'] ?? '');
                if ($brandId === '') {
                    $validator->errors()->add("gem_authorizations.{$index}.brand_id", 'Select a Brand/OEM.');
                } elseif (isset($seenBrands[$brandId])) {
                    $validator->errors()->add("gem_authorizations.{$index}.brand_id", 'This Brand/OEM is already added for this party.');
                } else {
                    $seenBrands[$brandId] = true;
                }

                if ($registered && ($row['gem_authorized'] ?? '0') === '1' && blank($row['authorization_number'] ?? null)) {
                    $validator->errors()->add(
                        "gem_authorizations.{$index}.authorization_number",
                        'GEM Authorization Number is required when this party is authorized for the OEM on GEM.'
                    );
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
        $data['customer_type_id'] = filled($data['customer_type_id'] ?? null) ? $data['customer_type_id'] : null;
        $data['udyam_number'] = filled($data['udyam_number'] ?? null) ? trim($data['udyam_number']) : null;
        $data['opened_on'] = filled($data['opened_on'] ?? null) ? $data['opened_on'] : null;
        $data['reference_by'] = filled($data['reference_by'] ?? null) ? trim($data['reference_by']) : null;
        $data['website'] = filled($data['website'] ?? null) ? trim($data['website']) : null;
        $data['location_link'] = filled($data['location_link'] ?? null) ? trim($data['location_link']) : null;
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

        unset($data['contacts'], $data['addresses'], $data['bank_accounts'], $data['gem_authorizations'], $data['pan'], $data['lead_id'], $data['return_to']);

        return $data;
    }

    protected function syncChildRows(Request $request, Customer $customer): void
    {
        // 1. Contacts
        $contacts = collect($request->input('contacts', []))
            ->filter(fn ($row) => filled($row['name'] ?? null))
            ->map(function ($row) {
                $rawRole = trim((string) ($row['role'] ?? $row['designation'] ?? ''));
                $customRole = trim((string) ($row['custom_role'] ?? $row['other_designation'] ?? $row['other_role'] ?? ''));

                if ($rawRole === 'Other') {
                    $finalRole = $customRole !== '' ? $customRole : 'Other';
                } elseif ($rawRole !== '') {
                    $finalRole = $rawRole;
                } elseif ($customRole !== '') {
                    $finalRole = $customRole;
                } else {
                    $finalRole = null;
                }

                return [
                    'level' => in_array($row['level'] ?? null, ['party', 'branch', 'site', 'warehouse', 'transporter', 'driver', 'site_incharge'], true) ? $row['level'] : 'party',
                    'name' => $row['name'] ?? null,
                    'role' => $finalRole,
                    'phone' => $row['phone'] ?? null,
                    'alternate_phone' => $row['alternate_phone'] ?? null,
                    'email' => $row['email'] ?? null,
                    'location' => $row['location'] ?? null,
                    'is_primary' => (bool) ($row['is_primary'] ?? false),
                    'is_active' => true,
                ];
            })
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
            $row = $bankAccounts->get(0, []);
            $row['is_primary'] = true;
            $bankAccounts->put(0, $row);
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

        $this->syncGemAuthorizations($request, $customer);
        $this->syncSalesTarget($request, $customer);
    }

    protected function syncAddresses(Request $request, Customer $customer): void
    {
        $rawAddresses = $request->input('addresses', []);
        $submittedAddresses = collect($rawAddresses)
            ->filter(function ($row) {
                // Keep only rows that have real address content (label alone is not enough).
                return filled($row['address_line_1'] ?? null)
                    || filled($row['address'] ?? null)
                    || filled($row['address_line_2'] ?? null)
                    || filled($row['city'] ?? null)
                    || filled($row['state'] ?? null)
                    || filled($row['pincode'] ?? null);
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
            $row = $submittedAddresses->get($idx, []);
            $row['is_default_billing'] = true;
            $submittedAddresses->put($idx, $row);
        }

        if (!$hasDefaultDelivery && $submittedAddresses->isNotEmpty()) {
            $firstDeliveryIdx = $submittedAddresses->search(fn ($r) => in_array($r['type'] ?? '', ['delivery', 'both', 'shipping'], true));
            $idx = $firstDeliveryIdx !== false ? $firstDeliveryIdx : 0;
            $row = $submittedAddresses->get($idx, []);
            $row['is_default_delivery'] = true;
            $submittedAddresses->put($idx, $row);
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

    protected function syncGemAuthorizations(Request $request, Customer $customer): void
    {
        $keptIds = [];

        foreach ($request->input('gem_authorizations', []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $registered = ($row['gem_registered'] ?? '0') === '1';
            $active = filled($row['brand_id'] ?? null)
                || $registered
                || filled($row['authorization_number'] ?? null)
                || $request->hasFile("gem_authorizations.{$index}.documents");

            if (! $active || blank($row['brand_id'] ?? null)) {
                continue;
            }

            $payload = [
                'brand_id' => $row['brand_id'],
                'gem_registered' => $registered,
                'gem_authorized' => $registered && ($row['gem_authorized'] ?? '0') === '1',
                'authorization_number' => $registered && ($row['gem_authorized'] ?? '0') === '1'
                    ? (trim((string) ($row['authorization_number'] ?? '')) ?: null)
                    : null,
            ];

            $record = null;
            if (filled($row['id'] ?? null)) {
                $record = $customer->gemAuthorizations()->whereKey($row['id'])->first();
            }

            if ($record) {
                $record->update($payload);
            } else {
                $record = $customer->gemAuthorizations()->create($payload);
            }

            $keptIds[] = $record->id;
            $this->syncGemDocuments($request, $record, $index, $registered);
        }

        $customer->gemAuthorizations()
            ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
            ->with('documents')
            ->get()
            ->each(function ($authorization) {
                $this->deleteGemDocuments($authorization->documents);
                $authorization->delete();
            });
    }

    protected function syncGemDocuments(Request $request, \App\Domains\Master\Models\PartyGemAuthorization $authorization, int $index, bool $registered): void
    {
        $removeIds = array_map('intval', (array) ($request->input("gem_authorizations.{$index}.remove_document_ids") ?? []));
        if (! $registered) {
            $this->deleteGemDocuments($authorization->documents()->get());

            return;
        }

        if ($removeIds !== []) {
            $this->deleteGemDocuments($authorization->documents()->whereIn('id', $removeIds)->get());
        }

        foreach ($request->file("gem_authorizations.{$index}.documents", []) as $file) {
            if (! $file) {
                continue;
            }

            $authorization->documents()->create([
                'disk' => 'public',
                'path' => $file->store('party-gem/'.$authorization->customer_id, 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
            ]);
        }
    }

    protected function deleteGemDocuments(iterable $documents): void
    {
        foreach ($documents as $document) {
            Storage::disk($document->disk ?: 'public')->delete($document->path);
            $document->delete();
        }
    }

    protected function syncSalesTarget(Request $request, Customer $customer): void
    {
        $targetAmount = $request->input('target_amount');
        $hasTargetAmount = filled($targetAmount) && (float) $targetAmount > 0;

        $startDate = $request->input('target_start_date');
        $endDate = $request->input('target_end_date');

        if (! $hasTargetAmount) {
            if ($request->has('target_amount') && (float) $targetAmount == 0) {
                $customer->updateQuietly([
                    'target_amount' => null,
                    'target_start_date' => null,
                    'target_end_date' => null,
                ]);
            }
            return;
        }

        $startDate = $startDate ?: now()->startOfMonth()->toDateString();
        $endDate = $endDate ?: now()->endOfMonth()->toDateString();
        $numericAmount = round((float) $targetAmount, 2);

        $customer->updateQuietly([
            'target_amount' => $numericAmount,
            'target_start_date' => $startDate,
            'target_end_date' => $endDate,
        ]);

        // Find existing target for this customer covering this exact period
        $target = $customer->salesTargets()
            ->where('starts_on', $startDate)
            ->where('ends_on', $endDate)
            ->first();

        $activeTarget = $customer->activeSalesTarget() ?? $customer->latestSalesTarget();

        if ($target) {
            $oldValues = $target->toArray();
            if ((float) $target->target_amount !== $numericAmount || $target->status !== 'active') {
                $target->update([
                    'target_amount' => $numericAmount,
                    'status' => 'active',
                    'revision_number' => (int) $target->revision_number + 1,
                    'revision_reason' => 'Target amount revised from Party Master',
                ]);

                \App\Domains\Target\Models\SalesTargetAudit::create([
                    'sales_target_id' => $target->id,
                    'user_id' => auth()->id(),
                    'action' => 'revised',
                    'reason' => 'Target amount revised from Party Master',
                    'old_values' => $oldValues,
                    'new_values' => $target->fresh()->toArray(),
                ]);
            }
        } elseif ($activeTarget) {
            // New target period / revised target: preserve historical record!
            $target = \App\Domains\Target\Models\SalesTarget::create([
                'name' => "{$customer->name} Target",
                'target_type' => 'value',
                'owner_type' => 'party',
                'customer_id' => $customer->id,
                'company_id' => $customer->company_id,
                'branch_id' => $customer->branch_id,
                'financial_year_id' => \App\Domains\Organization\Models\FinancialYear::current($customer->company_id)?->id,
                'period_type' => 'custom',
                'starts_on' => $startDate,
                'ends_on' => $endDate,
                'target_amount' => $numericAmount,
                'status' => 'active',
                'revision_number' => (int) $activeTarget->revision_number + 1,
                'revised_from_id' => $activeTarget->id,
                'revision_reason' => 'Target period revised from Party Master',
                'created_by' => auth()->id(),
            ]);

            \App\Domains\Target\Models\SalesTargetAudit::create([
                'sales_target_id' => $target->id,
                'user_id' => auth()->id(),
                'action' => 'revised',
                'reason' => 'New target period created from Party Master',
                'old_values' => ['previous_target_id' => $activeTarget->id, 'previous_amount' => $activeTarget->target_amount],
                'new_values' => $target->toArray(),
            ]);
        } else {
            // Initial target creation
            $target = \App\Domains\Target\Models\SalesTarget::create([
                'name' => "{$customer->name} Target",
                'target_type' => 'value',
                'owner_type' => 'party',
                'customer_id' => $customer->id,
                'company_id' => $customer->company_id,
                'branch_id' => $customer->branch_id,
                'financial_year_id' => \App\Domains\Organization\Models\FinancialYear::current($customer->company_id)?->id,
                'period_type' => 'custom',
                'starts_on' => $startDate,
                'ends_on' => $endDate,
                'target_amount' => $numericAmount,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            \App\Domains\Target\Models\SalesTargetAudit::create([
                'sales_target_id' => $target->id,
                'user_id' => auth()->id(),
                'action' => 'created',
                'reason' => 'Initial target assigned from Party Master',
                'new_values' => $target->toArray(),
            ]);
        }

        // Calculate achievement immediately from eligible posted invoices
        app(\App\Domains\Target\Services\SalesTargetCalculationService::class)->calculateTarget($target);
    }

    public function targetReport(Request $request): View|Response
    {
        $query = Customer::query()
            ->with([
                'customerType', 'area', 'branch',
                'salesTargets' => fn ($q) => $q->latest('starts_on')->with('achievement'),
            ])
            ->where(function ($q) {
                $q->whereNotNull('target_amount')->where('target_amount', '>', 0)
                    ->orWhereHas('salesTargets');
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim($request->search);
                $q->where(fn ($sq) => $sq->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"));
            })
            ->when($request->filled('target_status'), function ($q) use ($request) {
                $status = strtolower(str_replace(' ', '_', (string) $request->target_status));
                if ($status === 'achieved') {
                    $q->whereHas('salesTargets', function ($tq) {
                        $tq->whereHas('achievement', fn ($aq) => $aq->where('is_achieved', true)->orWhereRaw('achieved_amount >= sales_targets.target_amount'));
                    });
                } elseif ($status === 'in_progress') {
                    $today = now()->toDateString();
                    $q->whereHas('salesTargets', function ($tq) use ($today) {
                        $tq->where('status', 'active')
                            ->where('starts_on', '<=', $today)
                            ->where('ends_on', '>=', $today)
                            ->whereHas('achievement', fn ($aq) => $aq->where('is_achieved', false)->whereRaw('achieved_amount < sales_targets.target_amount'));
                    });
                } elseif ($status === 'period_expired' || $status === 'expired') {
                    $today = now()->toDateString();
                    $q->whereHas('salesTargets', function ($tq) use ($today) {
                        $tq->where('ends_on', '<', $today)
                            ->whereHas('achievement', fn ($aq) => $aq->where('is_achieved', false)->whereRaw('achieved_amount < sales_targets.target_amount'));
                    });
                } elseif ($status === 'not_started') {
                    $today = now()->toDateString();
                    $q->whereHas('salesTargets', function ($tq) use ($today) {
                        $tq->where('starts_on', '>', $today)
                            ->whereHas('achievement', fn ($aq) => $aq->where('is_achieved', false));
                    });
                }
            })
            ->when($request->filled('start_date'), function ($q) use ($request) {
                $q->whereHas('salesTargets', fn ($tq) => $tq->where('starts_on', '>=', $request->start_date));
            })
            ->when($request->filled('end_date'), function ($q) use ($request) {
                $q->whereHas('salesTargets', fn ($tq) => $tq->where('ends_on', '<=', $request->end_date));
            });

        $exportHeaders = [
            'Party Name',
            'Code',
            'Target Amount (₹)',
            'Achieved Sales (₹)',
            'Shortfall (₹)',
            'Achievement %',
            'Target Period',
            'Target Status',
        ];

        if ($export = $this->exportIfRequested($request, $query, 'Party_Wise_Target_Report', 'Party-Wise Target Report', $exportHeaders, function ($item) {
            $target = $item->activeSalesTarget() ?? $item->latestSalesTarget();
            $targetAmount = (float) ($target?->target_amount ?? ($item->target_amount ?? 0));
            $achieved = (float) ($target?->achievement?->achieved_amount ?? 0);
            $shortfall = $targetAmount > 0 ? max(0, $targetAmount - $achieved) : 0;
            $percent = $targetAmount > 0 ? round(($achieved / $targetAmount) * 100, 1) : 0;
            $period = $target ? ($target->starts_on?->format('d-m-Y') . ' to ' . $target->ends_on?->format('d-m-Y')) : '—';
            $status = $target ? $target->progress_status : 'Not Started';

            return [
                $item->name,
                $item->code,
                number_format($targetAmount, 2),
                number_format($achieved, 2),
                number_format($shortfall, 2),
                "{$percent}%",
                $period,
                $status,
            ];
        })) {
            return $export;
        }

        $items = $query->paginate(20)->withQueryString();

        // Calculate summary statistics
        $allTargets = \App\Domains\Target\Models\SalesTarget::where('owner_type', 'party')->with('achievement')->get();
        $totalTargetAssigned = (float) $allTargets->sum('target_amount');
        $totalAchieved = (float) $allTargets->sum(fn ($t) => (float) ($t->achievement?->achieved_amount ?? 0));
        $totalShortfall = max(0, $totalTargetAssigned - $totalAchieved);
        $overallPercent = $totalTargetAssigned > 0 ? round(($totalAchieved / $totalTargetAssigned) * 100, 1) : 0;

        return view('masters.customers.target-report', [
            'items' => $items,
            'search' => $request->string('search'),
            'targetStatus' => $request->string('target_status'),
            'startDate' => $request->string('start_date'),
            'endDate' => $request->string('end_date'),
            'totalTargetAssigned' => $totalTargetAssigned,
            'totalAchieved' => $totalAchieved,
            'totalShortfall' => $totalShortfall,
            'overallPercent' => $overallPercent,
        ]);
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

        $brandQuery = Brand::query()->where('is_active', true);
        if ($customer?->exists) {
            $selectedBrandIds = $customer->gemAuthorizations()->pluck('brand_id')->filter();
            if ($selectedBrandIds->isNotEmpty()) {
                $brandQuery->orWhereIn('id', $selectedBrandIds);
            }
        }

        return [
            'businessGroups' => $businessGroups,
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'brands' => $brandQuery->orderBy('name')->get(['id', 'name']),
            'companies' => \App\Domains\Organization\Models\Company::query()->orderBy('name')->get(['id', 'name']),
            'defaultCompanyId' => auth()->user()?->company_id,
            'areas' => Area::query()
                ->where(function ($query) use ($customer) {
                    $query->where('is_active', true);
                    if ($customer?->area_id) {
                        $query->orWhere('id', $customer->area_id);
                    }
                })
                ->orderBy('name')
                ->get(),
            'routes' => Route::query()
                ->where(function ($query) use ($customer) {
                    $query->where('is_active', true);
                    if ($customer?->route_id) {
                        $query->orWhere('id', $customer->route_id);
                    }
                })
                ->orderBy('name')
                ->get(),
            'salesManagers' => $salesManagers,
            'salespersons' => $salespersons,
            'states' => IndianStates::allAlphabetical(),
            'stateOptions' => IndianStates::options(),
            'stateCities' => IndianCities::all(),
            'contactDesignations' => \App\Support\ContactDesignations::all(),
        ];
    }
}