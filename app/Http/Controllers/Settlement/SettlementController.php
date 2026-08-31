<?php

namespace App\Http\Controllers\Settlement;

use App\Domains\Logistics\Models\LoadSheet;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Settlement\Models\Settlement;
use App\Domains\Settlement\Models\SettlementLine;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettlementController extends Controller
{
    public function __construct(
        protected OutstandingLedgerService $outstandingLedgerService,
    ) {}

    public function index(Request $request): View
    {
        $loadSheets = LoadSheet::query()
            ->with(['route', 'driver', 'settlement'])
            ->whereIn('status', ['delivered', 'dispatched', 'in_transit'])
            ->when($request->filled('unsettled'), fn ($q) => $q->doesntHave('settlement'))
            ->latest('load_date')
            ->paginate(15)
            ->withQueryString();

        return view('settlements.index', compact('loadSheets'));
    }

    public function create(LoadSheet $loadSheet): View
    {
        $loadSheet->load(['items.invoice.customer', 'deliveries.invoice']);

        $lines = $loadSheet->items->map(fn ($item) => [
            'invoice' => $item->invoice,
            'customer' => $item->invoice->customer,
            'amount' => (float) $item->invoice->grand_total - (float) $item->invoice->paid_amount,
        ]);

        return view('settlements.create', compact('loadSheet', 'lines'));
    }

    public function store(Request $request, LoadSheet $loadSheet): RedirectResponse
    {
        $validated = $request->validate([
            'lines' => 'required|array|min:1',
            'lines.*.invoice_id' => 'required|exists:invoices,id',
            'lines.*.customer_id' => 'required|exists:customers,id',
            'lines.*.cash_amount' => 'nullable|numeric|min:0',
            'lines.*.upi_amount' => 'nullable|numeric|min:0',
            'lines.*.outstanding_amount' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $loadSheet) {
            $cashTotal = 0;
            $upiTotal = 0;
            $outstandingTotal = 0;

            $settlement = Settlement::create([
                'settlement_no' => $this->generateSettlementNo(),
                'load_sheet_id' => $loadSheet->id,
                'status' => 'completed',
                'settled_by' => auth()->id(),
                'settled_at' => now(),
            ]);

            foreach ($validated['lines'] as $line) {
                $cash = (float) ($line['cash_amount'] ?? 0);
                $upi = (float) ($line['upi_amount'] ?? 0);
                $outstanding = (float) ($line['outstanding_amount'] ?? 0);

                SettlementLine::create([
                    'settlement_id' => $settlement->id,
                    'customer_id' => $line['customer_id'],
                    'invoice_id' => $line['invoice_id'],
                    'cash_amount' => $cash,
                    'upi_amount' => $upi,
                    'outstanding_amount' => $outstanding,
                ]);

                $cashTotal += $cash;
                $upiTotal += $upi;
                $outstandingTotal += $outstanding;

                $invoice = Invoice::findOrFail($line['invoice_id']);
                $collected = $cash + $upi;

                if ($collected > 0) {
                    $payment = Payment::create([
                        'payment_no' => $this->generatePaymentNo(),
                        'invoice_id' => $invoice->id,
                        'customer_id' => $line['customer_id'],
                        'amount' => $collected,
                        'method' => $upi > 0 && $cash > 0 ? 'other' : ($upi > 0 ? 'upi' : 'cash'),
                        'status' => 'completed',
                        'paid_at' => now(),
                        'recorded_by' => auth()->id(),
                        'notes' => "Settlement {$settlement->settlement_no}",
                    ]);

                    $newPaid = (float) $invoice->paid_amount + $collected;
                    $invoice->update([
                        'paid_amount' => $newPaid,
                        'status' => $newPaid >= (float) $invoice->grand_total ? 'paid' : 'partial',
                    ]);

                    $this->outstandingLedgerService->recordPayment($payment);
                }

                if ($outstanding > 0) {
                    $this->outstandingLedgerService->recordSettlement(
                        $line['customer_id'],
                        $outstanding,
                        $settlement,
                        "Outstanding from settlement {$settlement->settlement_no}"
                    );
                }
            }

            $settlement->update([
                'cash_collected' => $cashTotal,
                'upi_collected' => $upiTotal,
                'outstanding_amount' => $outstandingTotal,
            ]);

            $loadSheet->update(['status' => 'settled']);
        });

        return $this->flashSuccess('Settlement completed.', 'settlements.index');
    }

    public function show(Settlement $settlement): View
    {
        $settlement->load(['loadSheet', 'lines.customer', 'lines.invoice', 'settler']);

        return view('settlements.show', compact('settlement'));
    }

    protected function generateSettlementNo(): string
    {
        $date = now()->format('Ymd');
        $pattern = "SET-{$date}-%";
        $last = Settlement::where('settlement_no', 'like', $pattern)->orderByDesc('settlement_no')->value('settlement_no');
        $sequence = $last ? (int) Str::afterLast($last, '-') + 1 : 1;

        return sprintf('SET-%s-%04d', $date, $sequence);
    }

    protected function generatePaymentNo(): string
    {
        $date = now()->format('Ymd');
        $pattern = "PAY-{$date}-%";
        $last = Payment::where('payment_no', 'like', $pattern)->orderByDesc('payment_no')->value('payment_no');
        $sequence = $last ? (int) Str::afterLast($last, '-') + 1 : 1;

        return sprintf('PAY-%s-%04d', $date, $sequence);
    }
}
