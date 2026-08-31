<?php

namespace App\Http\Controllers\Communication;

use App\Domains\Communication\Models\CommunicationLog;
use App\Domains\Communication\Services\CommunicationService;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommunicationController extends Controller
{
    public function __construct(
        protected CommunicationService $communicationService,
    ) {}

    public function index(Request $request): View
    {
        $logs = CommunicationLog::query()
            ->with(['customer', 'invoice', 'sender'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('communications.index', compact('logs'));
    }

    public function sendInvoice(Invoice $invoice): RedirectResponse
    {
        $this->communicationService->sendInvoiceWhatsapp($invoice, auth()->user());

        return $this->flashSuccess('Invoice sent via WhatsApp (stub).');
    }

    public function sendPaymentLink(Invoice $invoice): RedirectResponse
    {
        $this->communicationService->sendPaymentLink($invoice, auth()->user());

        return $this->flashSuccess('Payment link sent (stub).');
    }

    public function sendReminder(Invoice $invoice): RedirectResponse
    {
        $this->communicationService->sendPaymentReminder($invoice, auth()->user());

        return $this->flashSuccess('Payment reminder sent (stub).');
    }
}
