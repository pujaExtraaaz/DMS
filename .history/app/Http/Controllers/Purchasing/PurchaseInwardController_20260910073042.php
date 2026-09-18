<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Purchasing\Models\PurchaseInward;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseInwardController extends Controller
{
    public function index(Request $request): View
    {
        $inwards = PurchaseInward::query()
            ->with(['supplier', 'warehouse', 'purchaseOrder', 'creator'])
            ->when($request->filled('search'), fn ($q) => $q->where('inward_no', 'like', '%'.$request->search.'%'))
            ->latest('inward_date')
            ->paginate(15)
            ->withQueryString();

        return view('purchasing.inwards.index', compact('inwards'));
    }

    public function show(PurchaseInward $inward): View
    {
        $inward->load(['items.product', 'items.uom', 'supplier', 'warehouse', 'purchaseOrder', 'creator']);

        return view('purchasing.inwards.show', compact('inward'));
    }
}
