<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Accounting\FxRateBook;
use Tally\Context\WorkspaceContext;
use Tally\Models\Currency;
use Tally\Models\ExchangeRate;

class ExchangeRateController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Exchange rates',
                'message' => 'Select a company before managing exchange rates.',
            ]);
        }

        return view('tally::exchange-rates.index', [
            'company' => $company,
            'currencies' => Currency::query()
                ->where('company_id', $company->id)
                ->where('is_base', false)
                ->orderBy('code')
                ->get(),
            'rates' => ExchangeRate::query()
                ->with('currency')
                ->where('company_id', $company->id)
                ->orderByDesc('rate_date')
                ->orderBy('currency_id')
                ->paginate(40),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, FxRateBook $book): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'currency_id' => [
                'required',
                'integer',
                Rule::exists('acct_currencies', 'id')->where(fn ($q) => $q->where('company_id', $company->id)->where('is_base', false)),
            ],
            'rate_date' => ['required', 'date'],
            'rate' => ['required', 'numeric', 'gt:0'],
        ]);

        $currency = Currency::query()->findOrFail($data['currency_id']);
        $book->upsert($company, $currency, $data['rate_date'], (float) $data['rate']);

        return back()->with('status', 'Exchange rate saved.');
    }
}
