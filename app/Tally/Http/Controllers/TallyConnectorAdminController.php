<?php

namespace Tally\Http\Controllers;

use App\Domains\Tally\Models\TallySyncQueue;
use App\Domains\TallySync\Support\ConnectorStatus;
use App\Domains\TallySync\Support\SyncGate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

/**
 * Future TallyPrime connector admin. Safe while sync is disabled — read-only status.
 */
class TallyConnectorAdminController extends Controller
{
    public function show(): View
    {
        $status = ConnectorStatus::snapshot();
        $recent = collect();

        if (Schema::hasTable('tally_sync_queues')) {
            $recent = TallySyncQueue::query()
                ->latest('id')
                ->limit(25)
                ->get(['id', 'document_type', 'document_id', 'status', 'attempts', 'last_error', 'created_at', 'sent_at']);
        }

        return view('tally::connector.show', [
            'status' => $status,
            'recent' => $recent,
            'syncEnabled' => SyncGate::enabled(),
        ]);
    }
}
