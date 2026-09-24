<?php

namespace App\Http\Controllers\Tally;

use App\Domains\Tally\Services\TallyImportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TallyImportController extends Controller
{
    public function __construct(
        protected TallyImportService $tallyImportService
    ) {
    }

    public function uoms(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.name' => ['required', 'string', 'max:100'],
            'items.*.original_name' => ['nullable', 'string', 'max:100'],
            'items.*.decimal_places' => ['nullable', 'integer', 'min:0', 'max:6'],
        ]);

        $result = $this->tallyImportService->syncUoms(
            $data['items']
        );

        return response()->json([
            'ok' => true,
            'message' => 'Tally UOMs synchronized successfully.',
            'result' => $result,
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],

            'items' => ['required', 'array'],

            'items.*.name' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.guid' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.base_units' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $result = $this->tallyImportService->syncProducts(
            $data['items'],
            (int) $data['company_id']
        );

        return response()->json([
            'ok' => true,
            'message' => 'Tally products synchronized successfully.',
            'result' => $result,
        ]);
    }

    public function godowns(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'items' => ['required', 'array'],
            'items.*.name' => [
                'required',
                'string',
                'max:255',
            ],
            'items.*.parent' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $result = $this->tallyImportService->syncGodowns(
            $data['items'],
            (int) $data['company_id'],
            (int) $data['branch_id']
        );

        return response()->json([
            'ok' => true,
            'message' => 'Tally godowns synchronized successfully.',
            'result' => $result,
        ]);
    }
}