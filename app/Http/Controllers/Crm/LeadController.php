<?php

namespace App\Http\Controllers\Crm;

use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\LeadActivity;
use App\Domains\Crm\Models\LeadAssignment;
use App\Domains\Crm\Models\LeadCampaign;
use App\Domains\Crm\Models\LeadConversion;
use App\Domains\Crm\Models\LeadFollowup;
use App\Domains\Crm\Models\LeadSource;
use App\Domains\Crm\Services\LeadDuplicateChecker;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\PartyContact;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\CodeGenerator;
use App\Support\DocumentNumberService;
use App\Support\IndianStates;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeadController extends Controller
{
    use SortableAndSearchable;
    public function __construct(
        protected AuditLogService $auditLogService,
        protected DocumentNumberService $documentNumbers,
    ) {}

    public function create(): View
    {
        return view('crm.leads.form', [
            'lead' => new Lead([
                'status' => 'new',
                'priority' => 'normal',
            ]),
            'users' => User::orderBy('name')->get(),
            'sources' => LeadSource::where('is_active', true)->orderBy('name')->get(),
            'campaigns' => LeadCampaign::where('is_active', true)->orderBy('name')->get(),
            'statuses' => ['new', 'contacted', 'follow_up', 'qualified', 'unqualified', 'lost'],
            'priorities' => ['low', 'normal', 'high', 'urgent'],
            'states' => IndianStates::all(),
            'subCategories' => SubCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = auth()->user()?->company_id;
        $data = $this->validateLead($request);

        // Duplicate validation before creation
        $duplicateErrors = LeadDuplicateChecker::checkManualLead(
            $data['email'] ?? null,
            $data['mobile'] ?? null,
            $companyId
        );

        if (! empty($duplicateErrors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($duplicateErrors);
        }

        $data['company_id'] = $companyId;
        $data['branch_id'] = auth()->user()?->branch_id;

        $lead = DB::transaction(function () use ($data, $companyId) {
            // Re-verify inside transaction to prevent race conditions
            $duplicateErrors = LeadDuplicateChecker::checkManualLead(
                $data['email'] ?? null,
                $data['mobile'] ?? null,
                $companyId
            );

            if (! empty($duplicateErrors)) {
                throw \Illuminate\Validation\ValidationException::withMessages($duplicateErrors);
            }

            $lead = Lead::create($data);

            if (!empty($data['assigned_to'])) {
                LeadAssignment::create([
                    'lead_id' => $lead->id,
                    'assigned_to' => $data['assigned_to'],
                    'assigned_by' => auth()->id(),
                    'method' => 'manual',
                    'notes' => 'Assigned upon lead creation.',
                ]);
            }

            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'activity_type' => 'creation',
                'body' => 'Lead created manually by ' . (auth()->user()?->name ?? 'User'),
            ]);

            return $lead;
        });

        $this->auditLogService->record($lead, 'created');

        return $this->flashSuccess('Lead created.', 'crm.leads.show', ['lead' => $lead]);
    }

    public function edit(Lead $lead): View
    {
        return view('crm.leads.form', [
            'lead' => $lead,
            'users' => User::orderBy('name')->get(),
            'sources' => LeadSource::where('is_active', true)->orderBy('name')->get(),
            'campaigns' => LeadCampaign::where('is_active', true)->orderBy('name')->get(),
            'statuses' => ['new', 'contacted', 'follow_up', 'qualified', 'unqualified', 'lost'],
            'priorities' => ['low', 'normal', 'high', 'urgent'],
            'states' => IndianStates::all(),
            'subCategories' => SubCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $companyId = $lead->company_id ?? auth()->user()?->company_id;
        $data = $this->validateLead($request);

        // Check for duplicates excluding current lead
        $duplicateErrors = LeadDuplicateChecker::checkManualLead(
            $data['email'] ?? null,
            $data['mobile'] ?? null,
            $companyId,
            $lead->id
        );

        if (! empty($duplicateErrors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($duplicateErrors);
        }

        $previousAssigned = $lead->assigned_to;

        DB::transaction(function () use ($lead, $data, $previousAssigned, $companyId) {
            // Re-verify inside transaction
            $duplicateErrors = LeadDuplicateChecker::checkManualLead(
                $data['email'] ?? null,
                $data['mobile'] ?? null,
                $companyId,
                $lead->id
            );

            if (! empty($duplicateErrors)) {
                throw \Illuminate\Validation\ValidationException::withMessages($duplicateErrors);
            }

            $lead->update($data);

            if (!empty($data['assigned_to']) && $data['assigned_to'] != $previousAssigned) {
                LeadAssignment::create([
                    'lead_id' => $lead->id,
                    'assigned_to' => $data['assigned_to'],
                    'assigned_by' => auth()->id(),
                    'method' => 'manual',
                    'notes' => 'Reassigned via edit lead.',
                ]);
            }

            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'activity_type' => 'note',
                'body' => 'Lead details updated by ' . (auth()->user()?->name ?? 'User'),
            ]);
        });

        $this->auditLogService->record($lead, 'updated');

        return $this->flashSuccess('Lead updated successfully.', 'crm.leads.show', ['lead' => $lead]);
    }

    public function destroy(Request $request, Lead $lead)
    {
        $user = auth()->user();

        // 1. Enforce authorization: super-admin or crm.manage or crm.edit
        if ($user && ! $user->hasRole('super-admin') && ! $user->hasAnyPermission(['crm.manage', 'crm.edit'])) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete leads.',
                ], 403);
            }
            abort(403, 'You do not have permission to delete leads.');
        }

        // 2. Company / Organization isolation check
        if ($user && ! $user->hasRole('super-admin')) {
            if ($user->company_id && $lead->company_id && (int) $lead->company_id !== (int) $user->company_id) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized action for this company.',
                    ], 403);
                }
                abort(403, 'Unauthorized action for this company.');
            }

            $allowedBranchIds = $user->allowedBranchIds();
            if ($allowedBranchIds !== [] && $lead->branch_id && ! in_array($lead->branch_id, $allowedBranchIds)) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized action for this branch.',
                    ], 403);
                }
                abort(403, 'Unauthorized action for this branch.');
            }
        }

        // 3. Execution inside transaction
        try {
            DB::transaction(function () use ($lead) {
                // Delete dependent child records
                $lead->conversion()?->delete();
                $lead->activities()->delete();
                $lead->assignments()->delete();
                $lead->followups()->delete();
                $lead->delete();
            });

            $this->auditLogService->record($lead, 'deleted');

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Lead deleted successfully.',
                ]);
            }

            return $this->flashSuccess('Lead deleted successfully.', 'crm.leads.index');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to delete lead #{$lead->id}: " . $e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete lead. The record may have related dependencies.',
                ], 422);
            }

            return back()->with('error', 'Failed to delete lead. The record may have related dependencies.');
        }
    }

    public function checkDuplicate(Request $request): \Illuminate\Http\JsonResponse
    {
        $companyId = auth()->user()?->company_id;
        $excludeId = $request->input('exclude_id') ? (int) $request->input('exclude_id') : null;

        $errors = LeadDuplicateChecker::checkManualLead(
            $request->input('email'),
            $request->input('mobile'),
            $companyId,
            $excludeId
        );

        return response()->json([
            'is_duplicate' => ! empty($errors),
            'duplicate' => ! empty($errors),
            'errors' => $errors,
            'email_duplicate' => isset($errors['email']),
            'email_message' => $errors['email'] ?? null,
            'mobile_duplicate' => isset($errors['mobile']),
            'mobile_message' => $errors['mobile'] ?? null,
        ]);
    }

    protected function validateLead(Request $request): array
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'secondary_email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:50',
            'secondary_mobile' => 'nullable|string|max:50',
            'second_mobile_number' => 'nullable|string|max:50',
            'secnd_mob' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'landline' => 'nullable|string|max:50',
            'sales_person' => 'nullable|exists:users,id',
            'assigned_to' => 'nullable|exists:users,id',
            'tag' => 'nullable|string|max:255',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'sub_category' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:500',
            'mailing_street' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'mailing_city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'mailing_state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:30',
            'mailing_zip' => 'nullable|string|max:30',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'lead_campaign_id' => 'nullable|exists:lead_campaigns,id',
            'priority' => 'required|in:low,normal,high,urgent',
            'status' => 'required|in:new,contacted,follow_up,followup,follow-up,qualified,unqualified,converted,lost',
            'interested_product' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if (in_array($data['status'] ?? null, ['followup', 'follow-up'], true)) {
            $data['status'] = 'follow_up';
        }

        if (empty($data['name']) && !empty($data['contact_name'])) {
            $data['name'] = $data['contact_name'];
        }

        if (empty($data['name'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => 'The Contact Name field is required.',
            ]);
        }

        if (!empty($data['company_name']) && empty($data['organization'])) {
            $data['organization'] = $data['company_name'];
        } elseif (!empty($data['organization']) && empty($data['company_name'])) {
            $data['company_name'] = $data['organization'];
        }

        if (!empty($data['second_mobile_number']) && empty($data['secondary_mobile'])) {
            $data['secondary_mobile'] = $data['second_mobile_number'];
        } elseif (!empty($data['secnd_mob']) && empty($data['secondary_mobile'])) {
            $data['secondary_mobile'] = $data['secnd_mob'];
        }

        if (!empty($data['sales_person']) && empty($data['assigned_to'])) {
            $data['assigned_to'] = $data['sales_person'];
        }

        if (!empty($data['mailing_street']) && empty($data['street'])) {
            $data['street'] = $data['mailing_street'];
        }

        if (!empty($data['mailing_city']) && empty($data['city'])) {
            $data['city'] = $data['mailing_city'];
        }

        if (!empty($data['mailing_state']) && empty($data['state'])) {
            $data['state'] = $data['mailing_state'];
        }

        if (!empty($data['mailing_zip']) && empty($data['zip'])) {
            $data['zip'] = $data['mailing_zip'];
        }

        if (!empty($data['sub_category_id']) && empty($data['sub_category'])) {
            $data['sub_category'] = SubCategory::find($data['sub_category_id'])?->name;
        }

        return $data;
    }

    public function index(Request $request): View
    {
        $query = Lead::query()
            ->with(['source', 'assignee', 'campaign', 'subCategory', 'convertedCustomer'])
            ->forUserBranch()
            ->when($request->filled('status'), function ($q) use ($request) {
                $stat = $request->status;
                if (in_array($stat, ['follow_up', 'followup', 'follow-up'], true)) {
                    $q->whereIn('status', ['follow_up', 'followup', 'follow-up']);
                } else {
                    $q->where('status', $stat);
                }
            });

        $this->applySearch($query, $request->input('search'), [
            'name',
            'company_name',
            'organization',
            'email',
            'secondary_email',
            'mobile',
            'secondary_mobile',
            'phone',
            'landline',
            'title',
            'tag',
            'sub_category',
            'city',
            'state',
            'zip',
            'street',
        ], [
            'source' => ['name'],
            'assignee' => ['name'],
            'subCategory' => ['name'],
        ]);

        [$sort, $direction] = $this->applySorting(
            $query,
            $request,
            [
                'created_at' => 'created_at',
                'name' => 'name',
                'lead' => 'name',
                'status' => 'status',
                'priority' => 'priority',
                'source' => function ($q, $dir) {
                    $q->leftJoin('lead_sources as ls', 'leads.lead_source_id', '=', 'ls.id')
                        ->orderBy('ls.name', $dir)
                        ->select('leads.*');
                },
                'assignee' => function ($q, $dir) {
                    $q->leftJoin('users as u_assignee', 'leads.assigned_to', '=', 'u_assignee.id')
                        ->orderBy('u_assignee.name', $dir)
                        ->select('leads.*');
                },
            ],
            'created_at',
            'desc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('crm.leads.index', compact('items', 'sort', 'direction'));
    }

    public function show(Lead $lead): View
    {
        $lead->load(['source', 'campaign', 'assignee', 'subCategory', 'activities.user', 'followups.user', 'assignments.assignee', 'conversion.customer']);

        return view('crm.leads.show', [
            'lead' => $lead,
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function assign(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        LeadAssignment::create([
            'lead_id' => $lead->id,
            'assigned_to' => $data['assigned_to'],
            'assigned_by' => auth()->id(),
            'method' => 'manual',
            'notes' => $data['notes'] ?? null,
        ]);

        $lead->update(['assigned_to' => $data['assigned_to']]);

        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'activity_type' => 'status_change',
            'body' => 'Lead assigned to user #'.$data['assigned_to'],
        ]);

        $this->auditLogService->record($lead, 'updated');

        return $this->flashSuccess('Lead assigned.', 'crm.leads.show', ['lead' => $lead]);
    }

    public function followup(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'due_at' => 'required|date',
            'channel' => 'nullable|string|max:40',
            'notes' => 'nullable|string',
        ]);

        LeadFollowup::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'due_at' => $data['due_at'],
            'channel' => $data['channel'] ?? 'call',
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        $lead->update([
            'next_followup_at' => $data['due_at'],
            'last_contacted_at' => now(),
            'status' => $lead->status === 'new' ? 'contacted' : $lead->status,
        ]);

        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'activity_type' => 'note',
            'body' => 'Follow-up scheduled: '.($data['notes'] ?? $data['due_at']),
        ]);

        return $this->flashSuccess('Follow-up scheduled.', 'crm.leads.show', ['lead' => $lead]);
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $user = auth()->user();

        // 1. Authorization: super-admin, crm.manage, or crm.edit
        if ($user && ! $user->hasRole('super-admin') && ! $user->hasAnyPermission(['crm.manage', 'crm.edit'])) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to change lead status.',
                ], 403);
            }
            abort(403, 'You do not have permission to change lead status.');
        }

        // 2. Company / Branch scoping
        if ($user && ! $user->hasRole('super-admin')) {
            if ($user->company_id && $lead->company_id && (int) $lead->company_id !== (int) $user->company_id) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized action for this company.',
                    ], 403);
                }
                abort(403, 'Unauthorized action for this company.');
            }

            $allowedBranchIds = $user->allowedBranchIds();
            if ($allowedBranchIds !== [] && $lead->branch_id && ! in_array($lead->branch_id, $allowedBranchIds)) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized action for this branch.',
                    ], 403);
                }
                abort(403, 'Unauthorized action for this branch.');
            }
        }

        // 3. Validation
        $allowedStatuses = ['new', 'contacted', 'follow_up', 'followup', 'follow-up', 'qualified', 'lost', 'unqualified'];
        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', $allowedStatuses),
        ]);

        $rawStatus = $validated['status'];
        $newStatus = in_array($rawStatus, ['followup', 'follow-up'], true) ? 'follow_up' : $rawStatus;
        $oldStatus = $lead->status;

        if ($oldStatus === $newStatus) {
            $label = self::statusLabel($newStatus);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Lead status is already {$label}.",
                    'status' => $newStatus,
                    'status_label' => $label,
                ]);
            }
            return back()->with('status', "Lead status is already {$label}.");
        }

        // 4. Update inside transaction
        DB::transaction(function () use ($lead, $newStatus, $oldStatus) {
            $lead->update(['status' => $newStatus]);

            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'activity_type' => 'status_change',
                'body' => 'Lead status updated from ' . self::statusLabel($oldStatus) . ' to ' . self::statusLabel($newStatus),
            ]);

            $this->auditLogService->record($lead, 'updated');
        });

        $label = self::statusLabel($newStatus);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Lead status updated to {$label}.",
                'status' => $newStatus,
                'status_label' => $label,
            ]);
        }

        return back()->with('status', "Lead status updated to {$label}.");
    }

    public function convert(Request $request, Lead $lead)
    {
        $user = auth()->user();

        // 1. Authorization: super-admin, crm.manage, or crm.edit
        if ($user && ! $user->hasRole('super-admin') && ! $user->hasAnyPermission(['crm.manage', 'crm.edit'])) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to convert leads.',
                ], 403);
            }
            abort(403, 'You do not have permission to convert leads.');
        }

        // 2. Company / Branch scoping
        if ($user && ! $user->hasRole('super-admin')) {
            if ($user->company_id && $lead->company_id && (int) $lead->company_id !== (int) $user->company_id) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized action for this company.',
                    ], 403);
                }
                abort(403, 'Unauthorized action for this company.');
            }

            $allowedBranchIds = $user->allowedBranchIds();
            if ($allowedBranchIds !== [] && $lead->branch_id && ! in_array($lead->branch_id, $allowedBranchIds)) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized action for this branch.',
                    ], 403);
                }
                abort(403, 'Unauthorized action for this branch.');
            }
        }

        // 3. Eligibility Check: Already converted?
        if ($lead->status === 'converted' || $lead->converted_customer_id || $lead->conversion()->exists()) {
            $msg = 'Lead has already been converted to a customer.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        // 4. Eligibility Check: Must have 'qualified' status
        if ($lead->status !== 'qualified') {
            $msg = 'Only leads with Qualified status can be converted to a customer.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $companyId = $lead->company_id ?? auth()->user()?->company_id;

        // 5. Duplicate Customer Check by normalized phone or email
        $normalizedMobile = preg_replace('/\D+/', '', (string) ($lead->mobile ?: $lead->phone ?: ''));
        $email = trim(strtolower((string) ($lead->email ?: '')));

        $existingCustomer = null;
        if ($normalizedMobile || $email) {
            $existingCustomer = Customer::query()
                ->where('company_id', $companyId)
                ->where(function ($q) use ($normalizedMobile, $email) {
                    if ($normalizedMobile && strlen($normalizedMobile) >= 10) {
                        $last10 = substr($normalizedMobile, -10);
                        $q->whereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+91', '') LIKE ?", ["%{$last10}"]);
                    }
                    if ($email) {
                        $q->orWhere('email', $email);
                    }
                })
                ->first();
        }

        $linkExisting = $request->boolean('link_existing');
        if ($existingCustomer && ! $linkExisting) {
            $msg = "An existing customer was found with matching contact details: {$existingCustomer->name} ({$existingCustomer->code}).";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'duplicate_detected' => true,
                    'message' => $msg,
                    'existing_customer' => [
                        'id' => $existingCustomer->id,
                        'name' => $existingCustomer->name,
                        'code' => $existingCustomer->code,
                        'phone' => $existingCustomer->phone,
                        'email' => $existingCustomer->email,
                    ],
                ], 409);
            }
            return back()->with('error', $msg);
        }

        try {
            $customer = null;

            DB::transaction(function () use ($lead, $companyId, $request, $linkExisting, $existingCustomer, &$customer) {
                if ($linkExisting && $existingCustomer) {
                    $customer = $existingCustomer;
                } else {
                    $partyName = trim($lead->company_name ?: ($lead->organization ?: $lead->name));
                    if (blank($partyName)) {
                        throw new \Exception('Lead is missing both company name and contact name.');
                    }

                    $defaultTypeId = CustomerType::query()->value('id');
                    if (! $defaultTypeId) {
                        $defaultTypeId = CustomerType::create([
                            'name' => 'General',
                            'code' => 'GEN',
                            'is_active' => true,
                        ])->id;
                    }

                    $code = CodeGenerator::forParty($companyId);
                    while (Customer::where('code', $code)->exists()) {
                        $code = CodeGenerator::forParty($companyId);
                    }

                    $customer = Customer::create([
                        'company_id' => $companyId,
                        'branch_id' => $lead->branch_id ?? auth()->user()?->branch_id,
                        'name' => $partyName,
                        'code' => $code,
                        'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
                        'customer_type_id' => $defaultTypeId,
                        'phone' => $lead->mobile ?: $lead->phone,
                        'email' => $lead->email ?: $lead->secondary_email,
                        'address' => $lead->street ?: (trim(($lead->city ?? '').' '.($lead->state ?? '')) ?: null),
                        'state' => $lead->state,
                        'pincode' => $lead->zip,
                        'salesperson_id' => $lead->assigned_to,
                        'is_active' => true,
                        'credit_status' => 'open',
                    ]);

                    // Primary contact person if lead has name
                    if (filled($lead->name)) {
                        PartyContact::create([
                            'customer_id' => $customer->id,
                            'name' => $lead->name,
                            'role' => $lead->title ?: 'Primary Contact',
                            'phone' => $lead->mobile ?: $lead->phone,
                            'alternate_phone' => $lead->secondary_mobile ?: $lead->landline,
                            'email' => $lead->email ?: $lead->secondary_email,
                            'is_primary' => true,
                            'is_active' => true,
                        ]);
                    }

                    // Address entry if lead has street or city
                    if (filled($lead->street) || filled($lead->city) || filled($lead->state) || filled($lead->zip)) {
                        PartyAddress::create([
                            'customer_id' => $customer->id,
                            'type' => 'both',
                            'label' => 'Main Address',
                            'contact_person' => $lead->name,
                            'contact_phone' => $lead->mobile ?: $lead->phone,
                            'address_line_1' => $lead->street ?: ($lead->city ?? ''),
                            'city' => $lead->city,
                            'state' => $lead->state,
                            'pincode' => $lead->zip,
                            'is_default' => true,
                            'is_default_billing' => true,
                            'is_default_delivery' => true,
                            'is_active' => true,
                        ]);
                    }
                }

                LeadConversion::create([
                    'lead_id' => $lead->id,
                    'customer_id' => $customer->id,
                    'converted_by' => auth()->id(),
                    'converted_at' => now(),
                    'notes' => $request->input('notes'),
                ]);

                $lead->update([
                    'status' => 'converted',
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
            });

            $msg = "Lead converted to Customer '{$customer->name}' ({$customer->code}) successfully.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'code' => $customer->code,
                    ],
                ]);
            }

            return $this->flashSuccess($msg, 'crm.leads.index');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to convert lead #{$lead->id}: " . $e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversion failed: ' . $e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Conversion failed: ' . $e->getMessage());
        }
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'new' => 'New',
            'contacted' => 'Contacted',
            'follow_up', 'followup', 'follow-up' => 'Follow-up',
            'qualified' => 'Qualified',
            'unqualified' => 'Unqualified',
            'converted' => 'Converted',
            'lost' => 'Lost',
            default => ucfirst(str_replace('_', ' ', (string) $status)),
        };
    }
}
