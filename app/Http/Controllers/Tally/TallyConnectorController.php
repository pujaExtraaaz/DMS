<?php

namespace App\Http\Controllers\Tally;

use App\Domains\Tally\Models\TallySyncQueue;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TallyConnectorController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'service' => 'dms-tally-connector',
            'use_connector' => (bool) config('services.tally.use_connector'),
            'company' => config('services.tally.company'),
            'pending' => TallySyncQueue::query()->whereIn('status', ['pending', 'failed'])->count(),
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $limit = min(50, max(1, (int) $request->integer('limit', 10)));

        $items = TallySyncQueue::query()
            ->whereIn('status', ['pending', 'failed'])
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
            'response' => 'nullable|string|max:5000',
        ]);

        if ($data['status'] === 'sent') {
            $tally_sync_queue->update([
                'status' => 'sent',
                'sent_at' => now(),
                'last_error' => null,
                'attempts' => $tally_sync_queue->attempts + 1,
            ]);
        } else {
            $error = $data['error'] ?? 'Connector reported failure';
            if (! empty($data['response'])) {
                $error .= ' | Tally: '.mb_substr($data['response'], 0, 500);
            }

            $tally_sync_queue->update([
                'status' => 'failed',
                'last_error' => $error,
                'attempts' => $tally_sync_queue->attempts + 1,
            ]);
        }

        return response()->json([
            'ok' => true,
            'id' => $tally_sync_queue->id,
            'status' => $tally_sync_queue->fresh()->status,
        ]);
    }
}
