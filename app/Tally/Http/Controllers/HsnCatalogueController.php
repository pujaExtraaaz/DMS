<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Tax\HsnCatalogue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HsnCatalogueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'matches' => HsnCatalogue::search($request->string('q')->toString()),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): JsonResponse
    {
        $company = $context->company();

        if (! $company) {
            return response()->json(['message' => 'Select a company first.'], 422);
        }

        $hsn = HsnCatalogue::adopt($company, $request->string('code')->toString());

        if ($hsn === null) {
            return response()->json([
                'message' => 'This code is not in the GST rate list. Add it under HSN / SAC and choose the tax rate.',
            ], 422);
        }

        return response()->json([
            'id' => $hsn->id,
            'code' => $hsn->code,
            'tax_rate_id' => $hsn->tax_rate_id,
            'description' => $hsn->description,
        ]);
    }
}
