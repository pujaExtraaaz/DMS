<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Http\Controllers\Controller;
use Tally\Integration\ExternalReferenceService;
use Tally\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected function page(LengthAwarePaginator $page, callable $transform): JsonResponse
    {
        return response()->json([
            'data' => $page->getCollection()->map($transform)->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    protected function data(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $payload], $status);
    }

    protected function perPage(Request $request): int
    {
        return min(100, max(1, (int) $request->integer('per_page', 25)));
    }

    /**
     * @return array<string, mixed>
     */
    protected function withIntegration(Model $model, array $payload): array
    {
        $payload['created_at'] = $model->created_at?->toIso8601String();
        $payload['updated_at'] = $model->updated_at?->toIso8601String();
        $payload['integration'] = app(ExternalReferenceService::class)->present($model);

        return $payload;
    }

    protected function rememberReference(Request $request, Model $model, ?Company $company): void
    {
        app(ExternalReferenceService::class)->attach(
            $model,
            $company?->id ?? ($model instanceof Company ? $model->id : null),
            $request->input('source'),
            $request->input('external_reference_id'),
            $request->input('sync_status'),
        );
        $model->load('integrationReference');
    }
}
