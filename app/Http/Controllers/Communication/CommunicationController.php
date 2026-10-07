<?php

namespace App\Http\Controllers\Communication;

use App\Domains\Communication\Models\CommunicationLog;
use App\Domains\Communication\Services\CommunicationService;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommunicationController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected CommunicationService $communicationService,
    ) {}

    public function index(Request $request): View
    {
        $query = CommunicationLog::query()
            ->with(['customer', 'invoice', 'sender'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to));

        $this->applySearch($query, $request->input('search'), [
            'type',
            'recipient',
            'status',
            'error_message',
        ], [
            'customer' => ['name', 'phone'],
            'invoice' => ['invoice_no'],
        ]);

        [$sort, $direction] = $this->applySorting(
            $query,
            $request,
            [
                'created_at' => 'created_at',
                'date' => 'created_at',
                'type' => 'type',
                'customer' => function ($q, $dir) {
                    $q->leftJoin('customers as comm_cust', 'communication_logs.customer_id', '=', 'comm_cust.id')
                        ->orderBy('comm_cust.name', $dir)
                        ->select('communication_logs.*');
                },
                'recipient' => 'recipient',
                'status' => 'status',
            ],
            'created_at',
            'desc'
        );

        $logs = $query->paginate(20)->withQueryString();

        return view('communications.index', compact('logs', 'sort', 'direction'));
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
