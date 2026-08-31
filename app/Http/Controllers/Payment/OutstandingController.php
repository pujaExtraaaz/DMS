<?php

namespace App\Http\Controllers\Payment;

use App\Domains\Master\Models\Customer;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutstandingController extends Controller
{
    public function __construct(
        protected OutstandingLedgerService $outstandingLedgerService,
    ) {}

    public function index(Request $request): View
    {
        $customerId = $request->integer('customer_id') ?: null;

        $ledger = OutstandingLedger::query()
            ->with('customer')
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate(25)
            ->withQueryString();

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
        ]);
    }
}
