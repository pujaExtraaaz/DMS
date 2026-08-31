<?php

namespace App\Http\Controllers\Payment;

use App\Domains\Payment\Models\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReconciliationController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['customer', 'invoice'])
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('paid_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('paid_at', '<=', $request->date_to))
            ->where('status', 'completed')
            ->latest('paid_at')
            ->paginate(20)
            ->withQueryString();

        $summaryQuery = Payment::query()
            ->where('status', 'completed')
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('paid_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('paid_at', '<=', $request->date_to));

        $summary = [
            'cash' => (clone $summaryQuery)->where('method', 'cash')->sum('amount'),
            'upi' => (clone $summaryQuery)->where('method', 'upi')->sum('amount'),
            'bank' => (clone $summaryQuery)->where('method', 'bank')->sum('amount'),
            'total' => (clone $summaryQuery)->sum('amount'),
        ];

        return view('payments.reconciliation', compact('payments', 'summary'));
    }
}
