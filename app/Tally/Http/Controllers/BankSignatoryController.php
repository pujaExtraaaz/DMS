<?php

namespace Tally\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\BankAccount;
use Tally\Models\BankSignatoryLimit;

class BankSignatoryController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Bank signatories',
                'message' => 'Select a company before managing bank signatory limits.',
            ]);
        }

        return view('tally::banking.signatories', [
            'company' => $company,
            'accounts' => BankAccount::query()->where('company_id', $company->id)->orderBy('bank_name')->get(),
            'users' => User::query()->orderBy('name')->limit(200)->get(),
            'limits' => BankSignatoryLimit::query()
                ->with(['bankAccount', 'user'])
                ->where('company_id', $company->id)
                ->orderByDesc('id')
                ->paginate(40),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $this->validated($request, $company->id);
        BankSignatoryLimit::query()->create($data + [
            'company_id' => $company->id,
            'requires_dual' => $request->boolean('requires_dual'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Signatory limit saved.');
    }

    public function update(Request $request, WorkspaceContext $context, BankSignatoryLimit $signatory): RedirectResponse
    {
        abort_unless($context->company() && $signatory->company_id === $context->company()->id, 404);

        $data = $this->validated($request, $context->company()->id);
        $signatory->update($data + [
            'requires_dual' => $request->boolean('requires_dual'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Signatory limit updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $companyId): array
    {
        $request->merge(['bank_account_id' => $request->input('bank_account_id') ?: null]);

        return $request->validate([
            'bank_account_id' => ['nullable', 'integer', Rule::exists('acct_bank_accounts', 'id')->where('company_id', $companyId)],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'single_limit' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'daily_limit' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'requires_dual' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
