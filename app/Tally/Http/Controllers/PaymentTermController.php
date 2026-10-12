<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\PaymentTerm;

class PaymentTermController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Payment terms',
                'message' => 'Select a company before managing payment terms.',
            ]);
        }

        return view('tally::payment-terms.index', [
            'company' => $company,
            'terms' => PaymentTerm::query()->where('company_id', $company->id)->orderBy('name')->paginate(25),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('acct_payment_terms', 'name')->where('company_id', $company->id)],
            'days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'due_on_receipt' => ['nullable', 'boolean'],
        ]);

        PaymentTerm::query()->create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'days' => $request->boolean('due_on_receipt') ? 0 : (int) ($data['days'] ?? 0),
            'due_on_receipt' => $request->boolean('due_on_receipt'),
            'is_active' => true,
        ]);

        return back()->with('status', 'Payment term created.');
    }

    public function update(Request $request, WorkspaceContext $context, PaymentTerm $paymentTerm): RedirectResponse
    {
        abort_unless($context->company() && $paymentTerm->company_id === $context->company()->id, 404);

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('acct_payment_terms', 'name')->where('company_id', $context->company()->id)->ignore($paymentTerm->id),
            ],
            'days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'due_on_receipt' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $paymentTerm->update([
            'name' => $data['name'],
            'days' => $request->boolean('due_on_receipt') ? 0 : (int) ($data['days'] ?? 0),
            'due_on_receipt' => $request->boolean('due_on_receipt'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Payment term updated.');
    }
}
