<?php

namespace App\Http\Controllers\Tally;

use App\Domains\Tally\Models\TallySyncQueue;
use App\Domains\Tally\Services\TallyExportService;
use App\Domains\TallySync\Support\ConnectorStatus;
use App\Domains\TallySync\Support\SyncGate;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TallyConnectorController extends Controller
{
    public function health(): JsonResponse
    {
        $status = ConnectorStatus::snapshot();

        return response()->json([
            'ok' => SyncGate::enabled(),
            'service' => 'dms-tally-connector',
            'enabled' => SyncGate::enabled(),
            'use_connector' => (bool) config('tally_sync.use_connector', config('services.tally.use_connector')),
            'company' => config('tally_sync.company', config('services.tally.company')),
            'pending' => $status['pending'],
            'failed' => $status['failed'],
            'message' => SyncGate::enabled()
                ? 'Connector ready'
                : 'Native Books is live. Set TALLY_SYNC_ENABLED=true to activate this connector.',
        ], SyncGate::enabled() ? 200 : 503);
    }

    /**
     * Builds the Tally XML for a DMS record without queueing it, so the payload
     * can be inspected or posted to Tally by hand. Without an id it lists recent records.
     */
    public function preview(TallyExportService $service, string $type, ?int $id = null): Response|JsonResponse
    {
        $class = TallyExportService::DOCUMENT_CLASSES[$type] ?? null;
        if (! $class) {
            return response()->json([
                'ok' => false,
                'message' => "Unknown type [{$type}]",
                'types' => array_keys(TallyExportService::DOCUMENT_CLASSES),
            ], 404);
        }

        if ($id === null) {
            return response()->json([
                'ok' => true,
                'type' => $type,
                'items' => $class::query()->latest('id')->limit(20)->get()->map(fn ($m) => [
                    'id' => $m->getKey(),
                    'label' => $m->invoice_no ?? $m->po_no ?? $m->payment_no ?? $m->credit_note_no ?? $m->name ?? null,
                    'status' => $m->status ?? null,
                ]),
            ]);
        }

        $document = $class::query()->find($id);
        if (! $document) {
            return response()->json(['ok' => false, 'message' => "{$type} #{$id} not found"], 404);
        }

        return response($service->buildPayload($type, $document), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function pending(Request $request): JsonResponse
    {
        $limit = min(50, max(1, (int) $request->integer('limit', 10)));

        $items = TallySyncQueue::query()
            ->where('status', 'pending')
            ->whereNotNull('payload')
            ->where('payload', '!=', '')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'document_type', 'document_id', 'payload', 'status', 'attempts', 'created_at']);

        return response()->json([
            'ok' => true,
            'count' => $items->count(),
            'items' => $items,
        ]);
    }

    public function result(Request $request, TallySyncQueue $tally_sync_queue): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:sent,failed',
            'error' => 'nullable|string|max:2000',
            'response' => 'nullable|string|max:60000',
        ]);

        $response = $data['response'] ?? null;
        $error = $data['error'] ?? null;
        $parsed = TallyExportService::parseTallyResponse($response ?: $error);
        // Duplicate/already-exists is only a soft success on Create.
        // Alter + Duplicate Original Name means the rename/symbol write failed (0 altered).
        $isCreate = str_contains((string) $tally_sync_queue->payload, 'ACTION="Create"')
            || str_contains((string) $tally_sync_queue->payload, "ACTION='Create'");
        $alreadyExists = $isCreate && $this->isAlreadyExistsFailure($parsed['line_errors']);

        if ($data['status'] === 'sent' || $alreadyExists) {
            $tally_sync_queue->update([
                'status' => 'sent',
                'sent_at' => now(),
                'last_error' => null,
                'last_response' => $response ?? $error,
                'attempts' => $tally_sync_queue->attempts + 1,
            ]);

            try {
                app(TallyExportService::class)->recordSuccess($tally_sync_queue, $response ?? $error);
                app(\App\Domains\TallySync\Queue\BooksSyncQueue::class)->recordLink($tally_sync_queue, $response ?? $error);
            } catch (\Throwable $e) {
                Log::warning('Tally mapping update failed', ['id' => $tally_sync_queue->id, 'error' => $e->getMessage()]);
            }
        } else {
            $tally_sync_queue->update([
                'status' => 'failed',
                'last_error' => $error ?? 'Connector reported failure',
                'last_response' => $response,
                'attempts' => $tally_sync_queue->attempts + 1,
            ]);

            try {
                app(TallyExportService::class)->healMissingAndRequeue(
                    $tally_sync_queue->fresh(),
                    $parsed['line_errors']
                );
            } catch (\Throwable $e) {
                Log::warning('Tally missing-master heal failed', [
                    'id' => $tally_sync_queue->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'ok' => true,
            'id' => $tally_sync_queue->id,
            'status' => $tally_sync_queue->fresh()->status,
        ]);
    }

    /**
     * @param  list<string>  $lineErrors
     */
    protected function isAlreadyExistsFailure(array $lineErrors): bool
    {
        foreach ($lineErrors as $error) {
            $text = mb_strtolower($error);
            if (
                str_contains($text, 'duplicate')
                || str_contains($text, 'already exists')
                || str_contains($text, 'already exist')
            ) {
                return true;
            }
        }

        return false;
    }

    public function exports(): JsonResponse
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('tally_sync_cursors')) {
            return response()->json(['ok' => false, 'message' => 'Connector tables are not migrated.'], 503);
        }

        return response()->json([
            'ok' => true,
            'items' => app(\App\Domains\TallySync\Bridge\TallyExportBuilder::class)->pending(),
        ]);
    }

    public function imports(Request $request): JsonResponse
    {
        // post_max_size exceeded → PHP empties the body; surface that clearly instead of a vague validation error.
        if ($request->header('Content-Length') && empty($request->all()) && (int) $request->header('Content-Length') > 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Import body was empty — PHP post_max_size/memory likely rejected the payload. Raise limits or shrink the export window.',
            ], 413);
        }

        $data = $request->validate([
            'key' => 'required|string|max:40',
            // Chunked exports stay well under this; keep headroom for dense voucher windows.
            'xml' => 'required|string|max:120000000',
        ]);

        return response()->json([
            'ok' => true,
        ] + app(\App\Domains\TallySync\Bridge\TallyInbound::class)->apply($data['key'], $data['xml']));
    }
}
