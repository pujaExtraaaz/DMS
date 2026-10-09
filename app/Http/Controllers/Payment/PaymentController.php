<?php

namespace App\Http\Controllers\Payment;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Services\FinancialYearService;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected OutstandingLedgerService $outstandingLedgerService,
        protected FinancialYearService $financialYearService,
    ) {}

    public function index(Request $request): View
    {
        $query = Payment::query()
            ->with(['customer', 'invoice', 'recorder'])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('paid_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('paid_at', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['payment_no', 'reference_no', 'method', 'status', 'notes'],
            ['customer' => ['name'], 'invoice' => ['invoice_no']]
        );

        $allowedSorts = [
            'payment_no' => 'payment_no',
            'method' => 'method',
            'amount' => 'amount',
            'paid_at' => 'paid_at',
            'customer' => function ($q, $dir) {
                $q->join('customers', 'payments.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $dir)
                  ->select('payments.*');
            },
            'invoice' => function ($q, $dir) {
                $q->leftJoin('invoices', 'payments.invoice_id', '=', 'invoices.id')
                  ->orderBy('invoices.invoice_no', $dir)
                  ->select('payments.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'paid_at',
            defaultDirection: 'desc'
        );

        $payments = $query->paginate(15)->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'customer_id', 'method', 'date_from', 'date_to']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(Request $request): View
    {
        $invoice = $request->filled('invoice_id')
            ? Invoice::with('customer')->findOrFail($request->invoice_id)
            : null;

        return view('payments.create', [
            'invoice' => $invoice,
            'invoices' => Invoice::with('customer')
                ->whereIn('status', ['issued', 'partial'])
                ->orderByDesc('invoice_date')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,upi,bank,cheque,other',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // FY lock — block payments dated inside a closed period.
        $this->financialYearService->assertOpen($validated['paid_at'] ?? now());

        $invoice = Invoice::findOrFail($validated['invoice_id']);
        $outstanding = (float) $invoice->grand_total - (float) $invoice->paid_amount;

        if ($validated['amount'] > $outstanding) {
            return $this->flashError('Payment amount exceeds invoice outstanding balance.');
        }

        DB::transaction(function () use ($validated, $invoice) {
            $payment = Payment::create([
                'payment_no' => $this->generatePaymentNo(),
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $validated['amount'],
                'method' => $validated['method'],
                'status' => 'completed',
                'paid_at' => $validated['paid_at'] ?? now(),
                'recorded_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            \App\Domains\Payment\Models\PaymentAllocation::create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
            ]);

            $newPaid = (float) $invoice->paid_amount + (float) $validated['amount'];
            $invoice->update([
                'paid_amount' => $newPaid,
                'status' => $newPaid >= (float) $invoice->grand_total ? 'paid' : 'partial',
            ]);

            $this->outstandingLedgerService->recordPayment($payment);
        });

        return $this->flashSuccess('Payment recorded successfully.', 'payments.index');
    }

    public function show(Payment $payment): View
    {
        $payment->load(['customer', 'invoice', 'recorder']);

        return view('payments.show', compact('payment'));
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
