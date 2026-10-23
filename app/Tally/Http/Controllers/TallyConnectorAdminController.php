<?php

namespace Tally\Http\Controllers;

use App\Domains\Tally\Models\TallySyncQueue;
use App\Domains\TallySync\Support\ConnectorStatus;
use App\Domains\TallySync\Support\SyncGate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TallyConnectorAdminController extends Controller
{
    public function show(): View
    {
        $status = ConnectorStatus::snapshot();
        $recent = collect();
        $conflicts = collect();

        if (Schema::hasTable('tally_sync_queues')) {
            $recent = TallySyncQueue::query()
                ->latest('id')
                ->limit(25)
                ->get(['id', 'document_type', 'document_id', 'status', 'attempts', 'last_error', 'created_at', 'sent_at']);
        }

        if (Schema::hasTable('tally_sync_conflicts')) {
            $conflicts = \Illuminate\Support\Facades\DB::table('tally_sync_conflicts')
                ->where('status', 'open')
                ->latest('id')
                ->limit(25)
                ->get();
        }

        return view('reporting.tally-connector', [
            'status' => $status,
            'recent' => $recent,
            'conflicts' => $conflicts,
            'syncEnabled' => SyncGate::enabled(),
            'exeReady' => is_file(base_path('tools/tally-connector/publish/tally_connector.exe')),
        ]);
    }

    public function download(): BinaryFileResponse
    {
        $dir = base_path('tools/tally-connector/publish');
        $exe = $dir.DIRECTORY_SEPARATOR.'tally_connector.exe';
        abort_unless(is_file($exe), 404, 'Build tally_connector.exe first.');

        $zipPath = storage_path('app/tally-connector-download.zip');
        if (is_file($zipPath)) {
            unlink($zipPath);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            abort(500, 'The Tally Connector folder could not be packed.');
        }

        foreach (['tally_connector.exe', 'config.json', 'config.example.json', 'install-task.ps1'] as $name) {
            $path = $dir.DIRECTORY_SEPARATOR.$name;
            if ($name === 'config.json' && ! is_file($path)) {
                $path = $dir.DIRECTORY_SEPARATOR.'config.example.json';
            }
            if (is_file($path)) {
                $zip->addFile($path, 'tally-connector/'.$name);
            }
        }
        $zip->close();

        return response()->download($zipPath, 'tally-connector.zip')->deleteFileAfterSend(true);
    }
}