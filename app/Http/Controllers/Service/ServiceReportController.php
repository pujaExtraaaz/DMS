<?php

namespace App\Http\Controllers\Service;

use App\Domains\Service\Models\ServiceCharge;
use App\Domains\Service\Models\ServiceTicket;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Service\Concerns\FiltersServiceTickets;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceReportController extends Controller
{
    use FiltersServiceTickets;
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $from = $request->input('date_from', now()->startOfMonth()->toDateString());
        $to = $request->input('date_to', now()->toDateString());
        $request->merge([
            'date_from' => $from,
            'date_to' => $to,
        ]);

        $base = ServiceTicket::query();
        $this->applyTicketFilters($base, $request);

        $openBase = (clone $base)->whereNotIn('status', ['closed', 'cancelled']);
        $total = (clone $base)->count();
        $openCount = (clone $openBase)->count();
        $overdueCount = (clone $openBase)->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->count();
        $closedCount = (clone $base)->where('status', 'closed')->count();
        $avgRating = (clone $base)->whereNotNull('feedback_rating')->avg('feedback_rating');

        $chargesQuery = ServiceCharge::query()
            ->whereHas('ticket', function ($q) use ($request) {
                $this->applyTicketFilters($q, $request);
            });

        $incomeCharges = (float) (clone $chargesQuery)->sum('grand_total');
        $incomePaid = (float) (clone $chargesQuery)->sum('paid_amount');

        $brandWise = (clone $base)->select('brand', DB::raw('count(*) as c'))->groupBy('brand')->orderByDesc('c')->limit(20)->get();
        $modelWise = (clone $base)->select('model_number', DB::raw('count(*) as c'))->groupBy('model_number')->orderByDesc('c')->limit(20)->get();
        $partyWise = (clone $base)->select('party_type', DB::raw('count(*) as c'))->groupBy('party_type')->get();
        $typeWise = (clone $base)->select('request_type', DB::raw('count(*) as c'))->groupBy('request_type')->get();
        $statusWise = (clone $base)->select('status', DB::raw('count(*) as c'))->groupBy('status')->orderByDesc('c')->get();
        $priorityWise = (clone $base)->select('priority', DB::raw('count(*) as c'))->groupBy('priority')->get();
        $warrantyVsOut = (clone $base)->select('warranty_status', DB::raw('count(*) as c'))->groupBy('warranty_status')->get();

        $techWise = ServiceTicket::query()
            ->whereNotNull('assigned_employee_id');
        $this->applyTicketFilters($techWise, $request);
        $techWise = $techWise
            ->select('assigned_employee_id', DB::raw('count(*) as c'))
            ->groupBy('assigned_employee_id')
            ->with('assignedEmployee')
            ->orderByDesc('c')
            ->limit(20)
            ->get();

        $installTypes = DB::table('service_installations')
            ->join('service_tickets', 'service_installations.service_ticket_id', '=', 'service_tickets.id')
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('service_tickets.created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('service_tickets.created_at', '<=', $request->date_to))
            ->when($request->filled('brand'), fn ($q) => $q->where('service_tickets.brand', 'like', '%'.$request->brand.'%'))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('service_tickets.assigned_employee_id', $request->employee_id))
            ->when($request->filled('party_type'), fn ($q) => $q->where('service_tickets.party_type', $request->party_type))
            ->select('installation_type', DB::raw('count(*) as c'))
            ->groupBy('installation_type')
            ->get();

        $pendingPayments = ServiceCharge::query()
            ->with('ticket')
            ->where('is_paid_service', true)
            ->whereIn('payment_status', ['pending', 'partial'])
            ->whereHas('ticket', function ($q) use ($request) {
                $this->applyTicketFilters($q, $request);
            })
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $repeatSerials = (clone $base)
            ->whereNotNull('serial_no')
            ->where('serial_no', '!=', '')
            ->select('serial_no', DB::raw('count(*) as c'))
            ->groupBy('serial_no')
            ->having('c', '>', 1)
            ->orderByDesc('c')
            ->limit(30)
            ->get();

        return view('service.reports', array_merge($this->ticketFilterOptions(), [
            'from' => $from,
            'to' => $to,
            'filters' => $this->ticketFiltersFrom($request),
            'kpis' => [
                'total' => $total,
                'open' => $openCount,
                'overdue' => $overdueCount,
                'closed' => $closedCount,
                'avg_rating' => $avgRating !== null ? round((float) $avgRating, 1) : null,
                'charges' => $incomeCharges,
                'paid' => $incomePaid,
                'outstanding' => max(0, $incomeCharges - $incomePaid),
            ],
            'brandWise' => $brandWise,
            'modelWise' => $modelWise,
            'techWise' => $techWise,
            'partyWise' => $partyWise,
            'typeWise' => $typeWise,
            'statusWise' => $statusWise,
            'priorityWise' => $priorityWise,
            'warrantyVsOut' => $warrantyVsOut,
            'installTypes' => $installTypes,
            'pendingPayments' => $pendingPayments,
            'repeatSerials' => $repeatSerials,
        ]));
    }

    public function warrantyRegister(Request $request): View
    {
        $query = ServiceTicket::query()
            ->with(['warranty.oemCentre', 'customer', 'assignedEmployee', 'oemCentre'])
            ->where(function ($q) {
                $q->whereHas('warranty')
                    ->orWhere('warranty_status', 'in_warranty');
            });

        $this->applyTicketFilters($query, $request);
        $this->applySearch(
            $query,
            $request->input('search'),
            ['ticket_no', 'customer_name', 'serial_no', 'brand', 'model_number'],
            ['warranty' => ['warranty_claim_no', 'oem_ticket_no'], 'oemCentre' => ['name']]
        );

        return view('service.warranty-register', array_merge($this->ticketFilterOptions(), [
            'tickets' => $query->orderByDesc('id')->paginate(30)->withQueryString(),
            'filters' => $this->ticketFiltersFrom($request),
        ]));
    }

    public function quotations(Request $request): View
    {
        $query = ServiceTicket::query()
            ->with(['installation', 'charges', 'customer', 'assignedEmployee'])
            ->where(function ($q) {
                $q->whereHas('installation')
                    ->orWhereHas('charges', fn ($c) => $c->where('is_paid_service', true));
            });

        $this->applyTicketFilters($query, $request);
        $this->applySearch(
            $query,
            $request->input('search'),
            ['ticket_no', 'customer_name', 'serial_no', 'brand', 'model_number'],
            ['customer' => ['name']]
        );

        return view('service.quotations', array_merge($this->ticketFilterOptions(), [
            'tickets' => $query->orderByDesc('id')->paginate(30)->withQueryString(),
            'filters' => $this->ticketFiltersFrom($request),
        ]));
    }

    public function feedbackList(Request $request): View
    {
        $query = ServiceTicket::query()
            ->with(['assignedEmployee'])
            ->whereNotNull('feedback_rating');

        $this->applyTicketFilters($query, $request);
        $this->applySearch(
            $query,
            $request->input('search'),
            ['ticket_no', 'customer_name', 'feedback_comments', 'serial_no'],
            []
        );

        return view('service.feedback', array_merge($this->ticketFilterOptions(), [
            'tickets' => $query->orderByDesc('feedback_at')->paginate(30)->withQueryString(),
            'filters' => $this->ticketFiltersFrom($request),
        ]));
    }
}
