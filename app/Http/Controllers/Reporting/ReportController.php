<?php

namespace App\Http\Controllers\Reporting;

use App\Domains\Delivery\Models\Delivery;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Master\Models\Customer;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $invoices = Invoice::query()
            ->with('customer')
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->orderBy('invoice_date')
            ->get();

        $summary = [
            'count' => $invoices->count(),
            'subtotal' => $invoices->sum('subtotal'),
            'tax' => $invoices->sum('tax_amount'),
            'total' => $invoices->sum('grand_total'),
            'collected' => $invoices->sum('paid_amount'),
        ];

        return view('reporting.sales', [
            'invoices' => $invoices,
            'summary' => $summary,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function stock(Request $request): View
    {
        $stockLevels = StockLevel::query()
            ->with(['product', 'uom'])
            ->when($request->boolean('low_only'), fn ($q) => $q->where('quantity', '<', 10))
            ->orderBy('product_id')
            ->get();

        return view('reporting.stock', compact('stockLevels'));
    }

    public function payments(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $payments = Payment::query()
            ->with(['customer', 'invoice'])
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->orderBy('paid_at')
            ->get();

        $byMethod = $payments->groupBy('method')->map->sum('amount');

        return view('reporting.payments', compact('payments', 'byMethod', 'dateFrom', 'dateTo'));
    }

    public function outstanding(Request $request): View
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        $balances = $customers->map(function ($customer) {
            $latest = OutstandingLedger::where('customer_id', $customer->id)->orderByDesc('id')->first();

            return [
                'customer' => $customer,
                'balance' => (float) ($latest?->balance ?? 0),
            ];
        })->filter(fn ($row) => $row['balance'] != 0)->values();

        return view('reporting.outstanding', compact('balances'));
    }

    public function delivery(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $deliveries = Delivery::query()
            ->with(['customer', 'invoice', 'loadSheet'])
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $summary = $deliveries->groupBy('status')->map->count();

        return view('reporting.delivery', compact('deliveries', 'summary', 'dateFrom', 'dateTo'));
    }
}
