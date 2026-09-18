<?php

namespace App\Http\Controllers\Tally;

use App\Domains\Tally\Models\TallySyncQueue;
use App\Domains\Tally\Services\TallyExportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TallySyncQueueController extends Controller
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

        return view('tally.queue', compact('items'));
    }

    public function retry(TallySyncQueue $tally_sync_queue): RedirectResponse
    {
        $this->tallyExportService->retry($tally_sync_queue);

        return $this->flashSuccess('Tally sync queued for retry.', 'tally.queue.index');
    }

    public function show(TallySyncQueue $tally_sync_queue): View
    {
        return view('tally.show', ['item' => $tally_sync_queue]);
    }
}
