<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tally\Banking\ElectronicPaymentService;
use Tally\Banking\NeftRtgsPaymentFileService;
use Tally\Context\WorkspaceContext;
use Tally\Models\BankAccount;
use Tally\Models\ElectronicPayment;

class ElectronicPaymentController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Electronic payments',
                'message' => 'Select a company before managing electronic payments.',
            ]);
        }

        return view('tally::banking.electronic-payments', [
            'company' => $company,
            'accounts' => BankAccount::query()->with('ledger')->where('company_id', $company->id)->orderBy('bank_name')->get(),
            'payments' => ElectronicPayment::query()
                ->with('bankAccount')
                ->where('company_id', $company->id)
                ->orderByDesc('payment_date')
                ->paginate(40),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, ElectronicPaymentService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $service->save($company, $this->validated($request, $company->id));

        return back()->with('status', 'Electronic payment recorded.');
    }

    public function update(Request $request, WorkspaceContext $context, ElectronicPayment $payment, ElectronicPaymentService $service): RedirectResponse
    {
        abort_unless($context->company() && $payment->company_id === $context->company()->id, 404);
        $service->save($context->company(), $this->validated($request, $context->company()->id), $payment);

        return back()->with('status', 'Electronic payment updated.');
    }

    public function exportNeft(WorkspaceContext $context, NeftRtgsPaymentFileService $files): StreamedResponse|RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $payments = ElectronicPayment::query()
            ->where('company_id', $company->id)
            ->whereIn('channel', ['neft', 'rtgs'])
            ->whereIn('status', ['initiated', 'sent'])
            ->orderBy('id')
            ->get();

        try {
            $built = $files->exportAndMark($company, $payments, true);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return response()->streamDownload(function () use ($built) {
            echo $built['csv'];
        }, $built['filename'], ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $companyId): array
    {
        $request->merge([
            'bank_account_id' => $request->input('bank_account_id') ?: null,
            'voucher_id' => $request->input('voucher_id') ?: null,
        ]);

        return $request->validate([
            'bank_account_id' => ['nullable', 'integer', Rule::exists('acct_bank_accounts', 'id')->where('company_id', $companyId)],
            'voucher_id' => ['nullable', 'integer', Rule::exists('acct_vouchers', 'id')->where('company_id', $companyId)],
            'channel' => ['required', Rule::in(['neft', 'rtgs', 'imps', 'upi', 'card', 'gateway'])],
            'reference' => ['nullable', 'string', 'max:80'],
            'utr' => ['nullable', 'string', 'max:80'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'beneficiary' => ['nullable', 'string', 'max:160'],
            'beneficiary_account' => ['nullable', 'string', 'max:40'],
            'beneficiary_ifsc' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(['initiated', 'sent', 'cleared', 'failed', 'cancelled'])],
        ]);
    }
}
