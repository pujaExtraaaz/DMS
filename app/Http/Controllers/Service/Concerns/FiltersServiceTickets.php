<?php

namespace App\Http\Controllers\Service\Concerns;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Service\Models\ServiceOemCentre;
use App\Domains\Service\Models\ServiceTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait FiltersServiceTickets
{
    /**
     * @return list<string>
     */
    protected function ticketFilterKeys(): array
    {
        return [
            'search', 'date_from', 'date_to', 'request_type', 'status', 'priority', 'party_type',
            'employee_id', 'oem_centre_id', 'brand', 'model_number', 'serial_no', 'warranty_status',
            'handler_type', 'overdue', 'pending_only', 'unassigned', 'assignment', 'payment_status',
            'oem_status', 'rating', 'approved',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function ticketFiltersFrom(Request $request): array
    {
        return $request->only($this->ticketFilterKeys());
    }

    protected function applyTicketFilters(Builder $query, Request $request, bool $applyDates = true): Builder
    {
        if ($applyDates) {
            $query
                ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
                ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to));
        }

        return $query
            ->when($request->filled('request_type'), fn ($q) => $q->where('request_type', $request->request_type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->priority))
            ->when($request->filled('party_type'), fn ($q) => $q->where('party_type', $request->party_type))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('assigned_employee_id', $request->employee_id))
            ->when($request->filled('oem_centre_id'), fn ($q) => $q->where('oem_centre_id', $request->oem_centre_id))
            ->when($request->filled('brand'), fn ($q) => $q->where('brand', 'like', '%'.$request->brand.'%'))
            ->when($request->filled('model_number'), fn ($q) => $q->where('model_number', 'like', '%'.$request->model_number.'%'))
            ->when($request->filled('serial_no'), fn ($q) => $q->where('serial_no', 'like', '%'.$request->serial_no.'%'))
            ->when($request->filled('warranty_status'), fn ($q) => $q->where('warranty_status', $request->warranty_status))
            ->when($request->filled('handler_type'), fn ($q) => $q->where('handler_type', $request->handler_type))
            ->when($request->boolean('overdue'), fn ($q) => $q->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->whereNotIn('status', ['closed', 'cancelled']))
            ->when($request->boolean('pending_only') && ! $request->boolean('assignment'), fn ($q) => $q->whereIn('status', ['new', 'assigned', 'visit', 'pending']))
            ->when($request->boolean('assignment'), fn ($q) => $q->whereIn('status', ['new', 'assigned', 'visit', 'pending']))
            ->when($request->boolean('unassigned') && ! $request->boolean('assignment'), fn ($q) => $q->whereNull('assigned_employee_id')->whereNotIn('status', ['closed', 'cancelled']))
            ->when($request->filled('rating'), fn ($q) => $q->where('feedback_rating', (int) $request->rating))
            ->when($request->filled('oem_status'), function ($q) use ($request) {
                $q->whereHas('warranty', fn ($w) => $w->where('oem_status', $request->oem_status));
            })
            ->when($request->filled('payment_status'), function ($q) use ($request) {
                $q->whereHas('charges', fn ($c) => $c->where('payment_status', $request->payment_status));
            })
            ->when($request->filled('approved'), function ($q) use ($request) {
                if ($request->approved === '1') {
                    $q->where(function ($inner) {
                        $inner->whereHas('charges', fn ($c) => $c->where('customer_approved', true))
                            ->orWhereHas('installation', fn ($i) => $i->where('customer_approved', true));
                    });
                } elseif ($request->approved === '0') {
                    $q->where(function ($inner) {
                        $inner->whereDoesntHave('charges', fn ($c) => $c->where('customer_approved', true))
                            ->whereDoesntHave('installation', fn ($i) => $i->where('customer_approved', true));
                    });
                }
            });
    }

    /**
     * @return array<string, mixed>
     */
    protected function ticketFilterOptions(): array
    {
        return [
            'employees' => Employee::query()->orderBy('name')->limit(300)->get(['id', 'name']),
            'oemCentres' => ServiceOemCentre::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'brands' => ServiceTicket::query()
                ->whereNotNull('brand')
                ->where('brand', '!=', '')
                ->distinct()
                ->orderBy('brand')
                ->limit(100)
                ->pluck('brand'),
            'models' => ServiceTicket::query()
                ->whereNotNull('model_number')
                ->where('model_number', '!=', '')
                ->distinct()
                ->orderBy('model_number')
                ->limit(150)
                ->pluck('model_number'),
            'statuses' => ServiceTicket::STATUSES,
            'priorities' => ServiceTicket::PRIORITIES,
            'requestTypes' => ServiceTicket::REQUEST_TYPES,
            'partyTypes' => ['dealer', 'end_user', 'company'],
            'warrantyStatuses' => ['in_warranty', 'out_of_warranty', 'unknown'],
            'handlerTypes' => ['avit', 'oem', 'both'],
            'paymentStatuses' => ['pending', 'partial', 'paid', 'waived'],
            'oemStatuses' => ['pending', 'submitted', 'approved', 'rejected', 'closed', 'in_progress'],
        ];
    }

    protected function ticketRegisterUrl(array $params = []): string
    {
        return route('service.tickets.index', array_filter($params, fn ($v) => $v !== null && $v !== ''));
    }
}
