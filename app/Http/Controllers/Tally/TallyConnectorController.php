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
            'pending' => TallySyncQueue::query()->where('status', 'pending')->count(),
        ]);
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
            'response' => 'nullable|string|max:5000',
        ]);

        if ($data['status'] === 'sent') {
            $tally_sync_queue->update([
                'status' => 'sent',
                'sent_at' => now(),
                'last_error' => null,
                'last_response' => $data['response'] ?? null,
                'attempts' => $tally_sync_queue->attempts + 1,
            ]);

            if (!empty($data['response'])) {
                try {
                    $xml = simplexml_load_string($data['response']);
                    $lastVchId = (string) ($xml->BODY->DATA->IMPORTRESULT->LASTVCHID ?? '');
                    if ($lastVchId !== '') {
                        $documentClass = null;
                        switch ($tally_sync_queue->document_type) {
                            case 'invoice': $documentClass = \App\Domains\Sales\Models\Invoice::class; break;
                            case 'payment': $documentClass = \App\Domains\Payment\Models\Payment::class; break;
                            case 'credit_note': $documentClass = \App\Domains\Payment\Models\CreditNote::class; break;
                            case 'purchase_invoice': $documentClass = \App\Domains\Purchasing\Models\PurchaseInvoice::class; break;
                            case 'purchase_order': $documentClass = \App\Domains\Purchasing\Models\PurchaseOrder::class; break;
                        }
                        
                        if ($documentClass) {
                            $doc = $documentClass::find($tally_sync_queue->document_id);
                            if ($doc) {
                                app(\App\Domains\Tally\Services\TallySyncMappingService::class)->createOrUpdate(
                                    $doc,
                                    $documentClass,
                                    'voucher',
                                    $lastVchId,
                                    $tally_sync_queue->document_type . '_' . $tally_sync_queue->document_id,
                                    'synced'
                                );
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore XML parse errors
                }
            }
        } else {
            $tally_sync_queue->update([
                'status' => 'failed',
                'last_error' => $data['error'] ?? 'Connector reported failure',
                'last_response' => $data['response'] ?? null,
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
