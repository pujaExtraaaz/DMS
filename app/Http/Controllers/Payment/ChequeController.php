<?php

namespace App\Http\Controllers\Payment;

use App\Domains\Master\Models\Customer;
use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Services\ChequeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ChequeController extends Controller
{
    public function __construct(protected ChequeService $chequeService) {}

    public function index(Request $request): View
    {
        $items = Cheque::query()
            ->with(['customer', 'recorder'])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('purpose'), fn ($q) => $q->where('purpose', $request->purpose))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('payments.cheques.index', [
            'items' => $items,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('payments.cheques.form', [
            'item' => new Cheque([
                'purpose' => 'pdc',
                'direction' => 'received_from_client',
                'cheque_date' => now()->toDateString(),
            ]),
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cheque_no' => 'required|string|max:50',
            'customer_id' => 'required|exists:customers,id',
            'purpose' => 'required|in:security,pdc',
            'direction' => 'required|in:received_from_client,received_from_vendor,issued_to_vendor',
            'amount' => 'required|numeric|min:0.01',
            'bank_name' => 'nullable|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'cheque_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $cheque = $this->chequeService->create($validated);

        return $this->flashSuccess('Cheque recorded.', 'cheques.show', ['cheque' => $cheque]);
    }

    public function show(Cheque $cheque): View
    {
        $cheque->load(['customer', 'recorder', 'bounces.recorder', 'payment']);

        return view('payments.cheques.show', ['item' => $cheque]);
    }

    public function deposit(Cheque $cheque): RedirectResponse
    {
        try {
            $this->chequeService->deposit($cheque);
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Cheque marked as deposited.');
    }

    public function clear(Cheque $cheque): RedirectResponse
    {
        try {
            $this->chequeService->clear($cheque);
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Cheque cleared.');
    }

    public function bounce(Request $request, Cheque $cheque): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
            'charges' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->chequeService->bounce(
                $cheque,
                $validated['reason'] ?? null,
                (float) ($validated['charges'] ?? 0)
            );
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Cheque bounce recorded. Party bounce count updated.');
    }

    public function cancel(Request $request, Cheque $cheque): RedirectResponse
    {
        try {
            $this->chequeService->cancel($cheque, $request->input('reason'));
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Cheque cancelled.');
    }
}
