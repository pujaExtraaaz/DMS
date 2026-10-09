<?php

namespace Tally\Http\Controllers;

use Tally\Support\Shell\Navigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShellSearchController extends Controller
{
    public function records(Request $request, Navigation $navigation): JsonResponse
    {
        return response()->json($navigation->searchRecords(
            $request->user(),
            $request->string('q')->toString(),
        ));
    }
}
