<?php

namespace Tally\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Accounting\FxRevaluationService;
use Tally\Context\WorkspaceContext;
use Tally\Models\Currency;
use Tally\Models\Ledger;

class FxRevaluationController extends Controller
{
    public function show(WorkspaceContext $context): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', [
                'title' => 'FX revaluation',
                'message' => 'Select a company and financial year first.',
            ]);
        }

        return view('tally::fx-revaluation.show', [
            'company' => $company,
            'year' => $year,
            'currencies' => Currency::query()->where('company_id', $company->id)->where('is_base', false)->orderBy('code')->get(),
            'ledgers' => Ledger::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, FxRevaluationService $service): RedirectResponse
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        $data = $request->validate([
            'as_on' => ['required', 'date'],
            'gain_loss_ledger_id' => [
                'required',
                'integer',
                Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id),
            ],
            'currency_id' => [
                'nullable',
                'integer',
                Rule::exists('acct_currencies', 'id')->where(fn ($q) => $q->where('company_id', $company->id)->where('is_base', false)),
            ],
        ]);

        /** @var User $user */
        $user = $request->user();
        $result = $service->revalue(
            $company,
            $branch,
            $year,
            $user,
            $data['as_on'],
            (int) $data['gain_loss_ledger_id'],
            isset($data['currency_id']) ? (int) $data['currency_id'] : null,
        );

        if (! $result['voucher']) {
            return back()->with('status', 'No FX differences to revalue on this date.');
        }

        return redirect()
            ->route('books.tally.vouchers.show', $result['voucher'])
            ->with('status', 'FX revaluation posted ('.count($result['lines']).' ledger lines).');
    }
}
