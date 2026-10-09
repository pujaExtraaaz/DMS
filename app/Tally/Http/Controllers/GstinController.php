<?php

namespace Tally\Http\Controllers;

use Tally\Tax\Gstin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GstinController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $found = Gstin::lookup($request->string('gstin')->toString());

        if ($found === null) {
            return response()->json([
                'message' => 'Enter a valid 15-character GSTIN. The state code and check digit must match.',
            ], 422);
        }

        return response()->json($found);
    }
}
