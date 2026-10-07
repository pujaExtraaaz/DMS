<?php

namespace App\Http\Controllers\Payment;

use App\Domains\Payment\Models\Payment;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReconciliationController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = Payment::query()
            ->with(['customer', 'invoice'])
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('paid_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('paid_at', '<=', $request->date_to))
            ->where('status', 'completed');

        $this->applySearch($query, $request->input('search'), [
            'method',
            'reference',
            'notes',
        ], [
            'customer' => ['name', 'phone'],
            'invoice' => ['invoice_no'],
        ]);

        [$sort, $direction] = $this->applySorting(
            $query,
            $request,
            [
                'paid_at' => 'paid_at',
                'date' => 'paid_at',
                'customer' => function ($q, $dir) {
                    $q->leftJoin('customers as rc_cust', 'payments.customer_id', '=', 'rc_cust.id')
                        ->orderBy('rc_cust.name', $dir)
                        ->select('payments.*');
                },
                'method' => 'method',
                'amount' => 'amount',
            ],
            'paid_at',
            'desc'
        );

        $payments = $query->paginate(20)->withQueryString();

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

        return view('payments.reconciliation', compact('payments', 'summary', 'sort', 'direction'));
    }
}
