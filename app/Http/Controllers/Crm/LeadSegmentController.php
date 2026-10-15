<?php

namespace App\Http\Controllers\Crm;

use App\Domains\Crm\Models\LeadSegment;
use App\Domains\Crm\Services\LeadSegmentService;
use App\Http\Controllers\Controller;
use App\Support\AuditLogService;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadSegmentController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected LeadSegmentService $segmentService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $query = LeadSegment::query()
            ->with(['creator', 'branch'])
            ->forUserBranch()
            ->when($companyId && ! $user->hasRole('super-admin'), fn ($q) => $q->where('company_id', $companyId));

        $this->applySearch($query, $request->input('search'), [
            'name',
            'description',
        ]);

        [$sort, $direction] = $this->applySorting(
            $query,
            $request,
            [
                'name' => 'name',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ],
            'name',
            'asc'
        );

        $segments = $query->paginate(15)->withQueryString();

        return view('crm.leads.segments.index', compact('segments', 'sort', 'direction'));
    }

    public function create(): View
    {
        $companyId = auth()->user()?->company_id;
        $segment = new LeadSegment(['criteria' => []]);
        $options = $this->segmentService->getFilterOptions($companyId);

        return view('crm.leads.segments.form', [
            'segment' => $segment,
            'options' => $options,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'criteria' => 'required|array',
        ]);

        $cleanedCriteria = $this->cleanCriteria($validated['criteria']);

        if (empty($cleanedCriteria)) {
            return back()->withInput()->withErrors([
                'criteria' => 'Please select at least one segmentation condition before saving.',
            ]);
        }

        $segment = LeadSegment::create([
            'company_id' => $user?->company_id,
            'branch_id' => $user?->branch_id,
            'name' => trim($validated['name']),
            'description' => $validated['description'] ? trim($validated['description']) : null,
            'criteria' => $cleanedCriteria,
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);

        $this->auditLogService->record($segment, 'created');

        return redirect()->route('crm.leads.segments.index')
            ->with('success', "Segment '{$segment->name}' created successfully with {$segment->matchingLeadsCount()} matching leads.");
    }

    public function show(Request $request, LeadSegment $segment): View
    {
        $this->authorizeAccess($segment);

        $matchingQuery = $segment->matchingLeadsQuery();

        // Allow searching and sorting within the segment's matching audience
        $this->applySearch($matchingQuery, $request->input('search'), [
            'name',
            'company_name',
            'organization',
            'email',
            'mobile',
            'city',
            'state',
            'tag',
            'industry',
        ]);

        [$sort, $direction] = $this->applySorting(
            $matchingQuery,
            $request,
            [
                'created_at' => 'created_at',
                'name' => 'name',
                'status' => 'status',
                'priority' => 'priority',
            ],
            'name',
            'asc'
        );

        $leads = $matchingQuery->paginate(15)->withQueryString();

        $companyId = $segment->company_id ?? auth()->user()?->company_id;
        $stats = $this->segmentService->preview($segment->criteria, $companyId, $segment->branch_id, 1);

        return view('crm.leads.segments.show', [
            'segment' => $segment,
            'leads' => $leads,
            'stats' => $stats,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function edit(LeadSegment $segment): View
    {
        $this->authorizeAccess($segment);

        $companyId = $segment->company_id ?? auth()->user()?->company_id;
        $options = $this->segmentService->getFilterOptions($companyId);

        return view('crm.leads.segments.form', [
            'segment' => $segment,
            'options' => $options,
        ]);
    }

    public function update(Request $request, LeadSegment $segment): RedirectResponse
    {
        $this->authorizeAccess($segment);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'criteria' => 'required|array',
        ]);

        $cleanedCriteria = $this->cleanCriteria($validated['criteria']);

        if (empty($cleanedCriteria)) {
            return back()->withInput()->withErrors([
                'criteria' => 'Please select at least one segmentation condition before saving.',
            ]);
        }

        $segment->update([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ? trim($validated['description']) : null,
            'criteria' => $cleanedCriteria,
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogService->record($segment, 'updated');

        return redirect()->route('crm.leads.segments.index')
            ->with('success', "Segment '{$segment->name}' updated successfully.");
    }

    public function destroy(Request $request, LeadSegment $segment)
    {
        $this->authorizeAccess($segment);

        $name = $segment->name;

        // Deleting a segment definition explicitly NEVER deletes any leads
        $segment->delete();

        $this->auditLogService->record($segment, 'deleted');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Segment '{$name}' deleted successfully.",
            ]);
        }

        return redirect()->route('crm.leads.segments.index')
            ->with('success', "Segment '{$name}' deleted successfully. Matching leads remain unchanged in the system.");
    }

    /**
     * AJAX endpoint to preview criteria dynamically.
     */
    public function preview(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $user?->company_id;
        $criteria = $this->cleanCriteria($request->input('criteria', []));

        $preview = $this->segmentService->preview($criteria, $companyId, $user?->branch_id, 8);

        $formattedLeads = $preview['sample_leads']->map(function ($lead) {
            $purchase = $lead->purchase_summary;
            $engagement = $lead->engagement_summary;

            return [
                'id' => $lead->id,
                'name' => $lead->name,
                'company' => $lead->company_name ?: ($lead->organization ?: '—'),
                'category' => $lead->category_label,
                'category_raw' => $lead->category,
                'status' => ucfirst($lead->status),
                'location' => trim(($lead->city ? $lead->city . ', ' : '') . ($lead->state ?? '')) ?: '—',
                'industry' => $lead->industry ?: ($lead->tag ?: '—'),
                'purchase_status' => $purchase['status_label'],
                'purchase_badge' => $purchase['badge_variant'],
                'engagement_level' => $engagement['level_label'],
                'engagement_badge' => $engagement['badge_variant'],
                'mobile' => $lead->mobile ?: ($lead->secondary_mobile ?: ($lead->phone ?: '—')),
                'email' => $lead->email ?: ($lead->secondary_email ?: '—'),
            ];
        });

        return response()->json([
            'success' => true,
            'total_count' => $preview['total_count'],
            'with_mobile_count' => $preview['with_mobile_count'],
            'with_email_count' => $preview['with_email_count'],
            'summary' => $this->segmentService->summarizeCriteria($criteria),
            'sample_leads' => $formattedLeads,
        ]);
    }

    /**
     * Streamed export of matching leads for targeted campaigns.
     */
    public function export(LeadSegment $segment): StreamedResponse
    {
        $this->authorizeAccess($segment);

        $query = $segment->matchingLeadsQuery()->orderBy('leads.name');
        $filename = 'segment-' . \Illuminate\Support\Str::slug($segment->name) . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($out, [
                'Lead ID',
                'Contact Name',
                'Company Name',
                'Category',
                'Status',
                'Priority',
                'Mobile',
                'Secondary Mobile',
                'Phone',
                'Email',
                'Secondary Email',
                'City',
                'State',
                'Pincode',
                'Street Address',
                'Industry / Tag',
                'Sub Category',
                'Salesperson',
                'Lead Source',
                'Campaign',
                'Purchase Status',
                'Orders Count',
                'Total Purchase Value',
                'Last Purchase Date',
                'Engagement Level',
                'Follow-ups Count',
                'Activities Count',
                'Last Contact Date',
                'Created At',
            ]);

            $query->chunk(500, function ($leads) use ($out) {
                foreach ($leads as $lead) {
                    $purchase = $lead->purchase_summary;
                    $engagement = $lead->engagement_summary;

                    fputcsv($out, [
                        $lead->id,
                        $lead->name,
                        $lead->company_name ?: $lead->organization,
                        $lead->category_label,
                        ucfirst($lead->status),
                        ucfirst($lead->priority ?? 'normal'),
                        $lead->mobile,
                        $lead->secondary_mobile,
                        $lead->phone,
                        $lead->email,
                        $lead->secondary_email,
                        $lead->city,
                        $lead->state,
                        $lead->zip,
                        $lead->street,
                        $lead->industry ?: $lead->tag,
                        $lead->subCategory?->name ?: $lead->sub_category,
                        $lead->assignee?->name,
                        $lead->source?->name,
                        $lead->campaign?->name,
                        $purchase['status_label'],
                        $purchase['orders_count'],
                        number_format($purchase['total_value'], 2, '.', ''),
                        $purchase['last_order_date'],
                        $engagement['level_label'],
                        $engagement['followups_count'],
                        $engagement['activities_count'],
                        $engagement['last_activity_at'],
                        $lead->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Clean criteria array removing empty strings and nulls.
     */
    protected function cleanCriteria(array $raw): array
    {
        $clean = [];

        foreach ($raw as $key => $val) {
            if ($val === null || $val === '') {
                continue;
            }

            if (is_array($val)) {
                $filtered = array_values(array_filter($val, fn ($v) => $v !== null && $v !== ''));
                if (! empty($filtered)) {
                    $clean[$key] = $filtered;
                }
            } else {
                $clean[$key] = is_string($val) ? trim($val) : $val;
            }
        }

        return $clean;
    }

    /**
     * Verify organization and authorization access to the segment.
     */
    protected function authorizeAccess(LeadSegment $segment): void
    {
        $user = auth()->user();

        if ($user && ! $user->hasRole('super-admin')) {
            if ($user->company_id && $segment->company_id && (int) $segment->company_id !== (int) $user->company_id) {
                abort(403, 'Unauthorized access to this company segment.');
            }

            $allowedBranchIds = $user->allowedBranchIds();
            if ($allowedBranchIds !== [] && $segment->branch_id && ! in_array($segment->branch_id, $allowedBranchIds)) {
                abort(403, 'Unauthorized access to this branch segment.');
            }
        }
    }
}

