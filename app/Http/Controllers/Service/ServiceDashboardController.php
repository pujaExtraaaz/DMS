<?php

namespace App\Http\Controllers\Service;

use App\Domains\Service\Services\ServiceTicketService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ServiceDashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('service.tickets.index');
    }

    public function escalate(ServiceTicketService $service): RedirectResponse
    {
        $n = $service->escalateOverdue();

        return $this->flashSuccess($n.' overdue ticket(s) escalated.');
    }

}
