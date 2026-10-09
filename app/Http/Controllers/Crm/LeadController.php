<?php

namespace App\Http\Controllers\Crm;

use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\LeadActivity;
use App\Domains\Crm\Models\LeadAssignment;
use App\Domains\Crm\Models\LeadCampaign;
use App\Domains\Crm\Models\LeadConversion;
use App\Domains\Crm\Models\LeadFollowup;
use App\Domains\Crm\Models\LeadSource;
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'organization' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'lead_campaign_id' => 'nullable|exists:lead_campaigns,id',
            'priority' => 'required|in:low,normal,high,urgent',
            'status' => 'required|in:new,contacted,qualified,unqualified,lost',
            'assigned_to' => 'nullable|exists:users,id',
            'interested_product' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $duplicateWarning = null;
        if (!empty($data['mobile']) || !empty($data['email'])) {
            $existing = Lead::query()
                ->when(!empty($data['mobile']), fn ($q) => $q->where('mobile', $data['mobile']))
                ->when(empty($data['mobile']) && !empty($data['email']), fn ($q) => $q->where('email', $data['email']))
                ->first();

            if ($existing) {
                $duplicateWarning = "A lead with matching contact details already exists (#{$existing->id} - {$existing->name}).";
            }
        }

        $data['company_id'] = auth()->user()?->company_id;
        $data['branch_id'] = auth()->user()?->branch_id;

        $lead = DB::transaction(function () use ($data) {
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

        if ($duplicateWarning) {
            return redirect()->route('crm.leads.show', $lead)
                ->with('warning', $duplicateWarning)
                ->with('success', 'Lead created successfully.');
        }

        return $this->flashSuccess('Lead created.', 'crm.leads.show', ['lead' => $lead]);
    }

    public function index(Request $request): View
    {
        $query = Lead::query()
            ->with(['source', 'assignee', 'campaign'])
            ->forUserBranch()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

        $this->applySearch($query, $request->input('search'), [
            'name',
            'mobile',
            'email',
            'organization',
            'city',
            'state',
        ], [
            'source' => ['name'],
            'assignee' => ['name'],
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
        $lead->load(['source', 'campaign', 'assignee', 'activities.user', 'followups.user', 'assignments.assignee', 'conversion.customer']);

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
