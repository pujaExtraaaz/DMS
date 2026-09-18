<?php

namespace App\Http\Controllers\Tally;

use App\Domains\Sales\Models\Invoice;
use App\Domains\Tally\Models\TallySyncQueue;
use App\Domains\Tally\Services\TallyExportService;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessTallySyncJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TallySyncController extends Controller
{
    public function __construct(protected TallyExportService $tallyExportService) {}

    public function index(Request $request): View
    {
        $items = TallySyncQueue::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('document_type'), fn ($q) => $q->where('document_type', $request->document_type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('tally.index', compact('items'));
    }

    public function retry(TallySyncQueue $tally_sync_queue): RedirectResponse
    {
        if (method_exists($this->tallyExportService, 'retry')) {
            $this->tallyExportService->retry($tally_sync_queue);
        } else {
            $tally_sync_queue->update(['status' => 'pending', 'last_error' => null]);
            ProcessTallySyncJob::dispatch($tally_sync_queue->id);
        }

        return $this->flashSuccess('Tally sync queued for retry.');
    }

    public function pushInvoice(Invoice $invoice): RedirectResponse
    {
        try {
            if (method_exists($this->tallyExportService, 'enqueuePostedDocument')) {
                $queue = $this->tallyExportService->enqueuePostedDocument($invoice);
                if (! $queue) {
                    return $this->flashError('Invoice is not in a posted status for Tally sync.');
                }
            } else {
                $this->tallyExportService->enqueue('invoice', $invoice);
            }
        } catch (\Throwable $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Invoice queued for Tally sync.');
    }
}
