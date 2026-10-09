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
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogService;
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
            'statuses' => ['new', 'contacted', 'qualified', 'unqualified', 'lost'],
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
            'statuses' => ['new', 'contacted', 'qualified', 'unqualified', 'lost'],
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
            'status' => 'required|in:new,contacted,qualified,unqualified,lost',
            'interested_product' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

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
            ->with(['source', 'assignee', 'campaign', 'subCategory'])
            ->forUserBranch()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

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

    public function convert(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->status === 'converted') {
            return $this->flashError('Lead already converted.');
        }

        $data = $request->validate([
            'notes' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($lead, $data) {
                $defaultTypeId = \App\Domains\Master\Models\CustomerType::query()->value('id');
                if (! $defaultTypeId) {
                    $defaultTypeId = \App\Domains\Master\Models\CustomerType::create([
                        'name' => 'General',
                        'code' => 'GEN',
                        'is_active' => true,
                    ])->id;
                }

                $customer = Customer::create([
                    'company_id' => $lead->company_id ?? auth()->user()?->company_id,
                    'branch_id' => $lead->branch_id ?? auth()->user()?->branch_id,
                    'name' => $lead->name,
                    'code' => $this->documentNumbers->next('CST'),
                    'party_type' => Customer::PARTY_TYPE_SUNDRY_DEBTORS,
                    'customer_type_id' => $defaultTypeId,
                    'phone' => $lead->mobile,
                    'email' => $lead->email,
                    'address' => trim(($lead->city ?? '').' '.($lead->state ?? '')) ?: null,
                    'state' => $lead->state,
                    'salesperson_id' => $lead->assigned_to,
                    'is_active' => true,
                    'credit_status' => 'open',
                ]);

                LeadConversion::create([
                    'lead_id' => $lead->id,
                    'customer_id' => $customer->id,
                    'converted_by' => auth()->id(),
                    'converted_at' => now(),
                    'notes' => $data['notes'] ?? null,
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
                    'body' => 'Converted to customer #'.$customer->id,
                ]);

                $this->auditLogService->record($lead, 'converted');
            });
        } catch (\Throwable $e) {
            return $this->flashError('Conversion failed: '.$e->getMessage());
        }

        return $this->flashSuccess('Lead converted to customer.', 'crm.leads.show', ['lead' => $lead]);
    }
}
