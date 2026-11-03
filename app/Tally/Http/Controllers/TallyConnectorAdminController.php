<?php

namespace Tally\Http\Controllers;

use App\Domains\Tally\Models\TallySyncQueue;
use App\Domains\Tally\Services\TallyExportService;
use App\Domains\TallySync\Bridge\TallyExportBuilder;
use App\Domains\TallySync\Support\ConnectorStatus;
use App\Domains\TallySync\Support\SyncCoverage;
use App\Domains\TallySync\Support\SyncGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TallyConnectorAdminController extends Controller
{
    public function show(): View
    {
        $status = ConnectorStatus::snapshot();
        $outbound = collect();
        $inbound = collect();
        $inboundCursors = collect();
        $conflicts = collect();
        $queueCounts = [
            'pending' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        if (Schema::hasTable('tally_sync_queues')) {
            $outbound = TallySyncQueue::query()
                ->latest('id')
                ->limit(25)
                ->get(['id', 'document_type', 'document_id', 'status', 'attempts', 'last_error', 'created_at', 'sent_at'])
                ->map(function (TallySyncQueue $row) {
                    $typeLabel = SyncCoverage::labelForQueueType($row->document_type);
                    $name = $this->resolveDocumentName((string) $row->document_type, (int) $row->document_id);

                    return (object) [
                        'id' => $row->id,
                        'document_type' => $row->document_type,
                        'document_id' => $row->document_id,
                        'synced_item' => SyncCoverage::formatSyncItem($typeLabel, $name),
                        'status' => $row->status,
                        'attempts' => $row->attempts,
                        'last_error' => $row->last_error,
                        'created_at' => $row->created_at,
                        'sent_at' => $row->sent_at,
                    ];
                });

            $rawCounts = TallySyncQueue::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');
            foreach ($queueCounts as $key => $_) {
                $queueCounts[$key] = (int) ($rawCounts[$key] ?? 0);
            }
        }

        if (Schema::hasTable('tally_inbound_logs')) {
            $inbound = DB::table('tally_inbound_logs')
                ->latest('id')
                ->limit(25)
                ->get();
        } elseif (Schema::hasTable('tally_sync_mappings')) {
            // Fallback before migration runs: recent mapping touches from Tally pulls.
            $inbound = DB::table('tally_sync_mappings')
                ->whereNotNull('last_synced_at')
                ->latest('last_synced_at')
                ->limit(25)
                ->get(['tally_type as collection', 'tally_name as item', 'sync_status as action', 'last_synced_at as created_at']);
        }

        if (Schema::hasTable('tally_sync_cursors')) {
            $inboundCursors = DB::table('tally_sync_cursors')
                ->whereIn('collection', TallyExportBuilder::ORDER)
                ->orderBy('collection')
                ->get(['collection', 'alter_id', 'updated_at']);
        }

        if (Schema::hasTable('tally_sync_conflicts')) {
            $conflicts = DB::table('tally_sync_conflicts')
                ->where('status', 'open')
                ->latest('id')
                ->limit(25)
                ->get();
        }

        $reports = collect();
        if (Schema::hasTable('tally_report_snapshots')) {
            $reports = DB::table('tally_report_snapshots')->orderBy('title')->get();
        }

        $coverage = SyncCoverage::rows();

        return view('reporting.tally-connector', [
            'status' => $status,
            'outbound' => $outbound,
            'inbound' => $inbound,
            'inboundCursors' => $inboundCursors,
            'recent' => $outbound,
            'conflicts' => $conflicts,
            'reports' => $reports,
            'syncEnabled' => SyncGate::enabled(),
            'exeReady' => is_file(base_path('tools/tally-connector/publish/tally_connector.exe')),
            'coverage' => $coverage,
            'coverageOutbound' => collect($coverage)->where('direction', 'outbound')->values(),
            'coverageInbound' => collect($coverage)->where('direction', 'inbound')->values(),
            'coverageSkipped' => collect($coverage)->where('direction', 'skip')->values(),
            'queueCounts' => $queueCounts,
            'queueLabels' => SyncCoverage::queueTypeLabels(),
        ]);
    }

    /**
     * Reset import cursors so the next connector polls re-fetch every Tally collection
     * into DMS (stock groups, units, godowns, ledgers, stock items, vouchers, …).
     */
    public function pullFromTally(): RedirectResponse
    {
        abort_unless(SyncGate::enabled(), 503, 'Tally sync is disabled.');

        if (! Schema::hasTable('tally_sync_cursors')) {
            return back()->with('error', 'Connector tables are not migrated.');
        }

        $now = now();
        foreach (TallyExportBuilder::ORDER as $collection) {
            DB::table('tally_sync_cursors')->updateOrInsert(
                ['collection' => $collection],
                ['alter_id' => 0, 'updated_at' => $now]
            );
        }

        // Clear stale Title_Case cursors from older Books-path polls.
        DB::table('tally_sync_cursors')
            ->whereNotIn('collection', TallyExportBuilder::ORDER)
            ->whereNotIn('collection', array_keys(TallyExportBuilder::REPORTS))
            ->delete();

        if (Schema::hasTable('tally_sync_conflicts')) {
            DB::table('tally_sync_conflicts')->where('status', 'open')->update([
                'status' => 'resolved',
                'updated_at' => $now,
            ]);
        }

        return back()->with(
            'success',
            'Full Tally pull armed. Keep tally_connector.exe running — next polls will import all masters and vouchers into DMS (company '.config('tally_sync.company').').'
        );
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

        $root = base_path('tools/tally-connector');
        $files = [
            'tally_connector.exe' => $dir.DIRECTORY_SEPARATOR.'tally_connector.exe',
            'config.json' => $dir.DIRECTORY_SEPARATOR.'config.json',
            'config.example.json' => $root.DIRECTORY_SEPARATOR.'config.example.json',
            'install-task.ps1' => $root.DIRECTORY_SEPARATOR.'install-task.ps1',
        ];
        if (! is_file($files['config.example.json'])) {
            $files['config.example.json'] = $dir.DIRECTORY_SEPARATOR.'config.example.json';
        }
        if (! is_file($files['install-task.ps1'])) {
            $files['install-task.ps1'] = $dir.DIRECTORY_SEPARATOR.'install-task.ps1';
        }
        foreach ($files as $name => $path) {
            if ($name === 'config.json') {
                $zip->addFromString('tally-connector/config.json', $this->connectorConfig());
                continue;
            }
            if (! is_file($path)) {
                continue;
            }
            $contents = file_get_contents($path);
            if ($contents === false) {
                abort(500, $name.' could not be read for the download.');
            }
            $zip->addFromString('tally-connector/'.$name, $contents);
        }
        $zip->close();

        return response()->download($zipPath, 'tally-connector.zip')->deleteFileAfterSend(true);
    }

    private function connectorConfig(): string
    {
        $saved = base_path('tools/tally-connector/publish/config.json');
        if (is_file($saved)) {
            $contents = file_get_contents($saved);
            if ($contents !== false && trim($contents) !== '') {
                return $contents;
            }
        }

        return json_encode([
            'dms_url' => rtrim((string) config('app.url'), '/'),
            'connector_token' => (string) config('tally_sync.connector_token', ''),
            'tally_url' => 'http://127.0.0.1:9000',
            'company' => (string) config('tally_sync.company', ''),
            'poll_seconds' => 8,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    }

    private function resolveDocumentName(string $type, int $id): ?string
    {
        if ($id < 1) {
            return null;
        }

        $class = TallyExportService::DOCUMENT_CLASSES[$type] ?? null;
        if (! is_string($class) || ! class_exists($class)) {
            return null;
        }

        try {
            $model = $class::query()->find($id);
        } catch (\Throwable) {
            return null;
        }

        if (! $model) {
            return null;
        }

        foreach ([
            'name', 'invoice_no', 'payment_no', 'order_no', 'po_no', 'quotation_no',
            'credit_note_no', 'inward_no', 'grn_no', 'transfer_no', 'adjustment_no', 'code', 'sku',
        ] as $field) {
            $value = trim((string) ($model->{$field} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '#'.$id;
    }
}
