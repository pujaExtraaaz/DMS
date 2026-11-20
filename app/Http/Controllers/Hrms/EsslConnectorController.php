<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Services\EsslSyncService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EsslConnectorController extends Controller
{
    public function __construct(protected EsslSyncService $sync) {}

    public function health(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'enabled' => (bool) config('hrms.essl.enabled'),
            'service' => 'essl-attendance',
        ]);
    }

    public function punches(Request $request): JsonResponse
    {
        $data = $request->validate([
            'punches' => 'required|array|min:1',
            'punches.*.biometric_code' => 'required|string|max:50',
            'punches.*.punched_at' => 'required|date',
            'punches.*.direction' => 'nullable|string|max:10',
            'punches.*.device_id' => 'nullable|string|max:80',
            'punches.*.external_id' => 'nullable|string|max:100',
        ]);

        $result = $this->sync->ingestPushedPunches($data['punches']);

        return response()->json(['ok' => true] + $result);
    }
}
