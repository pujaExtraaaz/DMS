<?php

namespace App\Http\Controllers\Service;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Service\Models\ServiceOemCentre;
use App\Domains\Service\Models\ServiceTicket;
use App\Domains\Service\Services\ServiceTicketService;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Service\Concerns\FiltersServiceTickets;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

class ServiceTicketController extends Controller
{
    use FiltersServiceTickets;
    use SortableAndSearchable;

    public function __construct(protected ServiceTicketService $service) {}

    public function index(Request $request): View
    {
        $query = ServiceTicket::query()->with(['customer', 'assignedEmployee', 'oemCentre']);
        $this->applyTicketFilters($query, $request);

        $this->applySearch(
            $query,
            $request->input('search'),
            ['ticket_no', 'customer_name', 'contact_person', 'contact_mobile', 'serial_no', 'brand', 'model_number', 'problem_description'],
            ['customer' => ['name'], 'assignedEmployee' => ['name', 'employee_code']]
        );

        if ($request->boolean('assignment')) {
            $query->orderByRaw('CASE WHEN assigned_employee_id IS NULL THEN 0 ELSE 1 END')
                ->orderByDesc('created_at');
            $sortData = ['sort' => 'created_at', 'direction' => 'desc'];
        } else {
            $sortData = $this->applySorting($query, $request, [
                'ticket_no' => 'ticket_no',
                'status' => 'status',
                'priority' => 'priority',
                'request_type' => 'request_type',
                'created_at' => 'created_at',
                'sla_due_at' => 'sla_due_at',
            ], defaultSort: 'created_at', defaultDirection: 'desc');
        }

        $title = 'Ticket Register';
        if ($request->boolean('assignment')) {
            $title = 'Technician Assignment';
        } elseif ($request->boolean('unassigned')) {
            $title = 'Unassigned Tickets';
        } elseif ($request->boolean('overdue')) {
            $title = 'Pending / Overdue Tickets';
        } elseif ($request->boolean('pending_only')) {
            $title = 'Open Tickets';
        }

        $openBase = ServiceTicket::query()->whereNotIn('status', ['closed', 'cancelled']);
        $hasExtraFilters = collect($this->ticketFiltersFrom($request))
            ->except(['assignment', 'unassigned', 'overdue', 'pending_only'])
            ->filter(fn ($v) => $v !== null && $v !== '' && $v !== false)
            ->isNotEmpty();
        $showDashboard = ! $request->boolean('assignment')
            && ! $request->boolean('unassigned')
            && ! $request->boolean('overdue')
            && ! $request->boolean('pending_only')
            && ! $hasExtraFilters;

        return view('service.tickets.index', array_merge($this->ticketFilterOptions(), [
            'items' => $query->paginate(20)->withQueryString(),
            'filters' => $this->ticketFiltersFrom($request),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
            'title' => $title,
            'showDashboard' => $showDashboard,
            'openCount' => (clone $openBase)->count(),
            'overdueCount' => (clone $openBase)->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->count(),
            'byType' => ServiceTicket::query()->select('request_type', DB::raw('count(*) as c'))->groupBy('request_type')->pluck('c', 'request_type'),
            'avgResolutionHours' => $this->avgResolutionHours(),
        ]));
    }

    protected function avgResolutionHours(): ?float
    {
        $row = ServiceTicket::query()
            ->whereNotNull('closed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, closed_at)) as avg_hours')
            ->value('avg_hours');

        return $row !== null ? round((float) $row, 1) : null;
    }

    public function create(Request $request): View
    {
        $type = $request->input('request_type', 'complaint');
        if (! in_array($type, ServiceTicket::REQUEST_TYPES, true)) {
            $type = 'complaint';
        }

        return view('service.tickets.form', $this->formData(new ServiceTicket([
            'request_type' => $type,
            'priority' => 'normal',
            'party_type' => 'end_user',
            'warranty_status' => 'unknown',
            'handler_type' => 'avit',
        ]), 'New Ticket'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTicket($request);
        try {
            $ticket = $this->service->create($validated, $request->user());
            if ($request->hasFile('attachments')) {
                foreach ((array) $request->file('attachments') as $file) {
                    if ($file) {
                        $this->service->storeAttachment($ticket, $file, $request->input('attachment_kind', 'other'), $request->user());
                    }
                }
            }
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Service ticket created.', 'service.tickets.show', ['ticket' => $ticket]);
    }

    public function show(ServiceTicket $ticket): View
    {
        $ticket->load([
            'customer', 'assignedEmployee', 'oemCentre', 'product', 'attachments', 'assignments.employee',
            'assignments.oemCentre', 'activities.user', 'visits.employee', 'installation', 'charges.lines',
            'warranty.oemCentre', 'salesInvoice', 'creator',
        ]);

        return view('service.tickets.show', [
            'ticket' => $ticket,
            'employees' => Employee::query()->orderBy('name')->limit(300)->get(),
            'oemCentres' => ServiceOemCentre::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(ServiceTicket $ticket): View
    {
        $ticket->load(['installation', 'charges', 'warranty']);

        return view('service.tickets.form', $this->formData($ticket, 'Edit Ticket '.$ticket->ticket_no));
    }

    public function update(Request $request, ServiceTicket $ticket): RedirectResponse
    {
        $validated = $this->validateTicket($request, updating: true);
        try {
            $this->service->update($ticket, $validated, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Ticket updated.', 'service.tickets.show', ['ticket' => $ticket]);
    }

    public function assign(Request $request, ServiceTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'oem_centre_id' => 'nullable|exists:service_oem_centres,id',
            'expected_visit_at' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->service->assign($ticket, $validated, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Ticket assigned.');
    }

    public function transition(Request $request, ServiceTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', ServiceTicket::STATUSES),
            'notes' => 'nullable|string|max:2000',
            'completion_remarks' => 'nullable|string|max:2000',
        ]);

        if (! empty($validated['completion_remarks'])) {
            $ticket->update([
                'completion_remarks' => $validated['completion_remarks'],
                'completion_status' => 'completed',
            ]);
        }

        try {
            $this->service->transition($ticket->fresh(), $validated['status'], $request->user(), $validated['notes'] ?? $validated['completion_remarks'] ?? null);
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Status updated to '.ucfirst($validated['status']).'.');
    }

    public function storeVisit(Request $request, ServiceTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'visited_at' => 'nullable|date',
            'customer_complaint' => 'nullable|string',
            'problem_identified' => 'nullable|string',
            'action_taken' => 'required|string',
            'parts_replaced' => 'nullable|string',
            'customer_signature' => 'nullable|string',
            'next_visit_required' => 'nullable|boolean',
            'next_visit_at' => 'nullable|date',
            'before_photo' => 'nullable|image|max:5120',
            'after_photo' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('before_photo')) {
            $validated['before_photo_path'] = $request->file('before_photo')->store('service-tickets/'.$ticket->id.'/visits', 'public');
        }
        if ($request->hasFile('after_photo')) {
            $validated['after_photo_path'] = $request->file('after_photo')->store('service-tickets/'.$ticket->id.'/visits', 'public');
        }
        $validated['next_visit_required'] = $request->boolean('next_visit_required');

        try {
            $this->service->addVisit($ticket, $validated, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Visit report saved.');
    }

    public function updateInstallation(Request $request, ServiceTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'installation_type' => 'required|in:free,chargeable,oem,avit_digital',
            'project_name' => 'nullable|string|max:255',
            'site_address' => 'nullable|string',
            'quotation_amount' => 'nullable|numeric|min:0',
            'installation_charges' => 'nullable|numeric|min:0',
            'material_consumed' => 'nullable|string',
            'customer_approved' => 'nullable|boolean',
            'customer_acknowledged' => 'nullable|boolean',
            'customer_signature' => 'nullable|string',
            'completion_certificate' => 'nullable|file|max:8192',
        ]);

        if ($request->hasFile('completion_certificate')) {
            $validated['completion_certificate_path'] = $request->file('completion_certificate')
                ->store('service-tickets/'.$ticket->id.'/install', 'public');
        }
        $validated['customer_approved'] = $request->boolean('customer_approved');
        $validated['customer_acknowledged'] = $request->boolean('customer_acknowledged');

        try {
            $this->service->updateInstallation($ticket, $validated, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Installation details saved.');
    }

    public function updateCharges(Request $request, ServiceTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'is_paid_service' => 'nullable|boolean',
            'technician_charges' => 'nullable|numeric|min:0',
            'transportation_charges' => 'nullable|numeric|min:0',
            'payment_status' => 'nullable|in:pending,partial,paid,waived',
            'paid_amount' => 'nullable|numeric|min:0',
            'customer_approved' => 'nullable|boolean',
            'lines' => 'nullable|array',
            'lines.*.line_type' => 'nullable|in:spare,labour,other',
            'lines.*.item_name' => 'nullable|string|max:255',
            'lines.*.quantity' => 'nullable|numeric|min:0',
            'lines.*.rate' => 'nullable|numeric|min:0',
        ]);
        $validated['is_paid_service'] = $request->boolean('is_paid_service');
        $validated['customer_approved'] = $request->boolean('customer_approved');

        try {
            $this->service->updateCharges($ticket, $validated, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Charges updated.');
    }

    public function updateWarranty(Request $request, ServiceTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'oem_centre_id' => 'nullable|exists:service_oem_centres,id',
            'warranty_claim_no' => 'nullable|string|max:80',
            'oem_ticket_no' => 'nullable|string|max:80',
            'oem_status' => 'nullable|in:pending,approved,rejected,closed',
            'parts_covered' => 'nullable|string',
            'chargeable' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);
        $validated['chargeable'] = $request->boolean('chargeable');

        try {
            $this->service->updateWarranty($ticket, $validated, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Warranty / OEM details saved.');
    }

    public function feedback(Request $request, ServiceTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'feedback_rating' => 'required|integer|min:1|max:5',
            'feedback_comments' => 'nullable|string|max:2000',
        ]);

        try {
            $this->service->submitFeedback($ticket, (int) $validated['feedback_rating'], $validated['feedback_comments'] ?? null);
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Feedback recorded.');
    }

    public function createInvoice(Request $request, ServiceTicket $ticket): RedirectResponse
    {
        try {
            $invoice = $this->service->createServiceInvoice($ticket, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return $this->flashError('Could not create invoice: '.$e->getMessage());
        }

        return $this->flashSuccess('Draft service invoice created.', 'invoices.show', ['invoice' => $invoice]);
    }

    public function storeAttachment(Request $request, ServiceTicket $ticket): RedirectResponse
    {
        $request->validate([
            'attachment' => 'required|file|max:10240',
            'kind' => 'nullable|string|max:40',
        ]);

        $this->service->storeAttachment($ticket, $request->file('attachment'), $request->input('kind', 'other'), $request->user());

        return $this->flashSuccess('Attachment uploaded.');
    }

    public function serialHistory(Request $request): View
    {
        $serial = trim((string) $request->input('serial_no', ''));
        $tickets = collect();
        if ($serial !== '') {
            $query = ServiceTicket::query()->with(['assignedEmployee', 'customer', 'oemCentre']);
            $this->applyTicketFilters($query, $request, applyDates: true);
            $query->where('serial_no', 'like', '%'.$serial.'%');
            $tickets = $query->orderByDesc('id')->get();
        }

        return view('service.serial-history', array_merge($this->ticketFilterOptions(), [
            'serial' => $serial,
            'tickets' => $tickets,
            'filters' => $this->ticketFiltersFrom($request),
        ]));
    }

    public function schedule(Request $request): View
    {
        $date = $request->input('date', now()->toDateString());

        $tickets = ServiceTicket::query()
            ->with(['assignedEmployee', 'customer', 'oemCentre'])
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('assigned_employee_id', $request->employee_id))
            ->when($request->filled('request_type'), fn ($q) => $q->where('request_type', $request->request_type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->priority))
            ->when($request->filled('party_type'), fn ($q) => $q->where('party_type', $request->party_type))
            ->when($request->filled('oem_centre_id'), fn ($q) => $q->where('oem_centre_id', $request->oem_centre_id))
            ->when($request->filled('brand'), fn ($q) => $q->where('brand', 'like', '%'.$request->brand.'%'))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('ticket_no', 'like', $term)
                        ->orWhere('customer_name', 'like', $term)
                        ->orWhere('serial_no', 'like', $term);
                });
            })
            ->where(function ($q) use ($date) {
                $q->whereDate('expected_visit_at', $date)
                    ->orWhereHas('visits', fn ($v) => $v->whereDate('visited_at', $date)->orWhereDate('next_visit_at', $date));
            })
            ->orderBy('expected_visit_at')
            ->get();

        return view('service.schedule', array_merge($this->ticketFilterOptions(), [
            'tickets' => $tickets,
            'date' => $date,
            'filters' => array_merge($this->ticketFiltersFrom($request), ['date' => $date]),
        ]));
    }

    protected function validateTicket(Request $request, bool $updating = false): array
    {
        $rules = [
            'request_type' => ($updating ? 'sometimes|' : '').'required|string|max:40',
            'party_type' => 'required|in:dealer,end_user,company',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_mobile' => 'nullable|string|max:20',
            'alt_contact_person' => 'nullable|string|max:255',
            'alt_contact_mobile' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string',
            'site_address' => 'nullable|string',
            'brand' => 'nullable|string|max:100',
            'product_category' => 'nullable|string|max:100',
            'model_number' => 'nullable|string|max:100',
            'product_id' => 'nullable|exists:products,id',
            'serial_no' => 'nullable|string|max:100',
            'invoice_no' => 'nullable|string|max:80',
            'invoice_date' => 'nullable|date',
            'warranty_status' => 'required|in:in_warranty,out_of_warranty,unknown',
            'warranty_start' => 'nullable|date',
            'warranty_expiry' => 'nullable|date',
            'problem_description' => 'nullable|string',
            'priority' => 'required|in:critical,high,normal,low',
            'handler_type' => 'nullable|string|max:40',
            'oem_centre_id' => 'nullable|exists:service_oem_centres,id',
            'expected_visit_at' => 'nullable|date',
            'company_id' => 'nullable|exists:companies,id',
            'is_paid_service' => 'nullable|boolean',
            'installation_type' => 'nullable|in:free,chargeable,oem,avit_digital',
            'project_name' => 'nullable|string|max:255',
            'quotation_amount' => 'nullable|numeric|min:0',
            'installation_charges' => 'nullable|numeric|min:0',
            'material_consumed' => 'nullable|string',
            'warranty_claim_no' => 'nullable|string|max:80',
            'oem_ticket_no' => 'nullable|string|max:80',
            'parts_covered' => 'nullable|string',
            'warranty_notes' => 'nullable|string',
            'attachments.*' => 'nullable|file|max:10240',
        ];

        $validated = $request->validate($rules);
        $validated['is_paid_service'] = $request->boolean('is_paid_service');
        $validated['request_type'] = strtolower(str_replace(' ', '_', trim((string) ($validated['request_type'] ?? 'complaint'))));
        $validated['handler_type'] = strtolower(str_replace(' ', '_', trim((string) ($validated['handler_type'] ?? 'avit'))));
        $validated['priority'] = strtolower(trim((string) ($validated['priority'] ?? 'normal')));

        return $validated;
    }

    public function quickStoreOem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'mobile' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:255',
        ]);

        $existing = ServiceOemCentre::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($validated['name']))])
            ->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'OEM centre already exists.',
                'item' => ['id' => (string) $existing->id, 'name' => $existing->name],
            ]);
        }

        $centre = ServiceOemCentre::create([
            'name' => $validated['name'],
            'brand' => $validated['brand'] ?? null,
            'mobile' => $validated['mobile'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OEM centre created.',
            'item' => ['id' => (string) $centre->id, 'name' => $centre->name],
        ]);
    }

    public function quickStoreTechnician(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
        ]);

        $existing = Employee::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($validated['name']))])
            ->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Technician already exists.',
                'item' => ['id' => (string) $existing->id, 'name' => $existing->name],
            ]);
        }

        $companyId = $request->user()?->company_id
            ?? \App\Domains\Organization\Models\Company::query()->value('id');
        $code = \App\Support\CodeGenerator::forEmployee($companyId);
        while (Employee::query()->where('employee_code', $code)->exists()) {
            $code = \App\Support\CodeGenerator::forEmployee($companyId);
        }

        $employee = Employee::create([
            'company_id' => $companyId,
            'branch_id' => $request->user()?->branch_id,
            'employee_code' => $code,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Technician created.',
            'item' => ['id' => (string) $employee->id, 'name' => $employee->name],
        ]);
    }

    public function quickDestroyOem(ServiceOemCentre $oemCentre): JsonResponse
    {
        ServiceTicket::query()->where('oem_centre_id', $oemCentre->id)->update(['oem_centre_id' => null]);
        DB::table('service_ticket_assignments')->where('oem_centre_id', $oemCentre->id)->update(['oem_centre_id' => null]);
        if (\Illuminate\Support\Facades\Schema::hasTable('service_warranties')
            && \Illuminate\Support\Facades\Schema::hasColumn('service_warranties', 'oem_centre_id')) {
            DB::table('service_warranties')->where('oem_centre_id', $oemCentre->id)->update(['oem_centre_id' => null]);
        }

        $name = $oemCentre->name;
        $oemCentre->delete();

        return response()->json([
            'success' => true,
            'message' => "OEM centre '{$name}' deleted.",
            'item' => ['id' => (string) $oemCentre->id, 'name' => $name],
        ]);
    }

    public function quickDestroyTechnician(Employee $employee): JsonResponse
    {
        ServiceTicket::query()->where('assigned_employee_id', $employee->id)->update(['assigned_employee_id' => null]);
        if (\Illuminate\Support\Facades\Schema::hasTable('service_ticket_assignments')
            && \Illuminate\Support\Facades\Schema::hasColumn('service_ticket_assignments', 'employee_id')) {
            DB::table('service_ticket_assignments')->where('employee_id', $employee->id)->update(['employee_id' => null]);
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('service_visits')
            && \Illuminate\Support\Facades\Schema::hasColumn('service_visits', 'employee_id')) {
            DB::table('service_visits')->where('employee_id', $employee->id)->update(['employee_id' => null]);
        }

        $name = $employee->name;
        $id = (string) $employee->id;

        try {
            $employee->delete();
        } catch (\Throwable $e) {
            $employee->update(['status' => 'inactive']);

            return response()->json([
                'success' => true,
                'message' => "Technician '{$name}' deactivated (linked HR records prevent full delete).",
                'item' => ['id' => $id, 'name' => $name, 'deactivated' => true],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Technician '{$name}' deleted.",
            'item' => ['id' => $id, 'name' => $name],
        ]);
    }

    protected function formData(ServiceTicket $ticket, string $title): array
    {
        $products = Product::query()
            ->where('is_active', true)
            ->where('is_hidden', false)
            ->orderBy('name')
            ->limit(800)
            ->get(['id', 'name', 'brand_id', 'category_id', 'model']);

        return [
            'ticket' => $ticket,
            'title' => $title,
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->limit(500)->get(),
            'products' => $products,
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'brand_id']),
            'employees' => Employee::query()->orderBy('name')->limit(300)->get(),
            'oemCentres' => ServiceOemCentre::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
