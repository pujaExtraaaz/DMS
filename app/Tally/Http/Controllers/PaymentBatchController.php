<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tally\Banking\NeftRtgsPaymentFileService;
use Tally\Context\WorkspaceContext;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\PartyDirectory;
use Tally\Models\ElectronicPayment;
use Tally\Models\PaymentBatch;
use Tally\Payables\PaymentBatchService;

class PaymentBatchController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Payment batches', 'message' => 'Select a company first.']);
        }

        return view('tally::payment-batches.index', [
            'company' => $company,
            'batches' => PaymentBatch::query()
                ->where('company_id', $company->id)
                ->latest('batch_date')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context, PartyDirectory $parties): View
    {
        $company = $context->company();
        abort_unless($company, 404);

        return view('tally::payment-batches.form', [
            'company' => $company,
            'batch' => new PaymentBatch(['batch_date' => now()->toDateString(), 'status' => 'draft']),
            'suppliers' => $parties->options($company, InvoiceKind::Purchase),
            'bankLedgers' => $company->ledgers()->with('accountGroup')->orderBy('name')->get()->filter(fn ($l) => $l->isCashOrBank()),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, PaymentBatchService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'batch_date' => ['required', 'date'],
            'bank_ledger_id' => ['nullable', 'integer'],
            'narration' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['draft', 'pending_approval'])],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.supplier_ledger_id' => ['required', 'integer'],
            'lines.*.bill_id' => ['nullable', 'integer'],
            'lines.*.amount' => ['required', 'numeric', 'min:0.01'],
            'lines.*.tds_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.reference' => ['nullable', 'string', 'max:80'],
        ]);

        $batch = $service->save($company, $data + [
            'branch_id' => $context->branchId(),
        ]);

        return redirect()->to(tally_route('books.tally.payment-batches.show', $batch))
            ->with('status', 'Payment batch saved.');
    }

    public function show(WorkspaceContext $context, PaymentBatch $paymentBatch): View
    {
        abort_unless($context->company() && $paymentBatch->company_id === $context->company()->id, 404);
        $paymentBatch->load(['lines.supplier', 'bankLedger', 'approver']);

        return view('tally::payment-batches.show', [
            'company' => $context->company(),
            'batch' => $paymentBatch,
        ]);
    }

    public function submit(WorkspaceContext $context, PaymentBatch $paymentBatch, PaymentBatchService $service): RedirectResponse
    {
        abort_unless($context->company() && $paymentBatch->company_id === $context->company()->id, 404);
        $service->submit($context->company(), $paymentBatch);

        return back()->with('status', 'Payment batch submitted for approval.');
    }

    public function approve(Request $request, WorkspaceContext $context, PaymentBatch $paymentBatch, PaymentBatchService $service): RedirectResponse
    {
        abort_unless($context->company() && $paymentBatch->company_id === $context->company()->id, 404);
        $batch = $service->approve($context->company(), $paymentBatch, $request->user());

        return back()->with('status', $batch->status === 'paid'
            ? 'Payment batch approved and payment vouchers posted.'
            : 'Payment batch approved.');
    }

    public function pay(Request $request, WorkspaceContext $context, PaymentBatch $paymentBatch, PaymentBatchService $service): RedirectResponse
    {
        abort_unless($context->company() && $paymentBatch->company_id === $context->company()->id, 404);
        $service->pay($context->company(), $paymentBatch, $request->user());

        return back()->with('status', 'Payment vouchers posted.');
    }

    public function fromBills(Request $request, WorkspaceContext $context, PaymentBatchService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['integer'],
            'bank_ledger_id' => ['nullable', 'integer'],
        ]);

        $batch = $service->createFromBills(
            $company,
            $data['invoice_ids'],
            $context->branchId(),
            $data['bank_ledger_id'] ?? null,
        );

        return redirect()->to(tally_route('books.tally.payment-batches.show', $batch))
            ->with('status', 'Payment batch created from bills.');
    }

    public function exportNeft(
        WorkspaceContext $context,
        PaymentBatch $paymentBatch,
        NeftRtgsPaymentFileService $files,
    ): StreamedResponse|RedirectResponse {
        abort_unless($context->company() && $paymentBatch->company_id === $context->company()->id, 404);

        $payments = ElectronicPayment::query()
            ->where('company_id', $context->company()->id)
            ->where('payment_batch_id', $paymentBatch->id)
            ->whereIn('channel', ['neft', 'rtgs'])
            ->orderBy('id')
            ->get();

        try {
            $built = $files->exportAndMark($context->company(), $payments, true);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return response()->streamDownload(function () use ($built) {
            echo $built['csv'];
        }, $built['filename'], ['Content-Type' => 'text/csv']);
    }
}
