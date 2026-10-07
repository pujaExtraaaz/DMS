<?php

namespace App\Http\Controllers\Payment;

use App\Domains\Master\Models\Customer;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Support\Traits\SortableAndSearchable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutstandingController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected OutstandingLedgerService $outstandingLedgerService,
    ) {}

    public function index(Request $request): View
    {
        $customerId = $request->integer('customer_id') ?: null;

        $query = OutstandingLedger::query()
            ->with('customer')
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['type', 'description'],
            ['customer' => ['name']]
        );

        $allowedSorts = [
            'created_at' => 'created_at',
            'type' => 'type',
            'debit' => 'debit',
            'credit' => 'credit',
            'balance' => 'balance',
            'customer' => function ($q, $dir) {
                $q->join('customers', 'outstanding_ledgers.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $dir)
                  ->select('outstanding_ledgers.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'created_at',
            defaultDirection: 'desc'
        );

        $ledger = $query->paginate(25)->withQueryString();

        $customerBalances = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'customer' => $c,
                'balance' => $this->outstandingLedgerService->getCurrentBalance($c->id),
            ])
            ->filter(fn ($row) => $row['balance'] != 0)
            ->values();

        return view('payments.outstanding', [
            'ledger' => $ledger,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'customerBalances' => $customerBalances,
            'filters' => $request->only(['search', 'customer_id', 'date_from', 'date_to']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }
}
