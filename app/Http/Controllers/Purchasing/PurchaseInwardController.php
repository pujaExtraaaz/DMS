<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PurchaseInwardController extends Controller
{
    /**
     * The Goods Receipts (Purchase Inwards) module has been removed from the DMS ERP.
     * Direct URL access is blocked and returns HTTP 404.
     */
    public function index(Request $request)
    {
        abort(404);
    }

    /**
     * Direct URL access to individual Goods Receipts is blocked and returns HTTP 404.
     */
    public function show(Request $request, $inward = null)
    {
        abort(404);
    }
}
