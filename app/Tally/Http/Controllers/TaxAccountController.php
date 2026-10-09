<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\TaxAccountRequest;
use Tally\Models\Ledger;
use Tally\Models\TaxAccount;
use Tally\Tax\TaxComponent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class TaxAccountController extends Controller
{
    public function edit(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Tax accounts',
                'message' => 'Select or create a company before mapping tax ledgers.',
            ]);
        }

        $accounts = $company->taxAccounts()->get()->keyBy(fn (TaxAccount $account) => $account->component->value);
        $ledgers = $company->ledgers()->with('accountGroup.parent')->where('is_active', true)->orderBy('name')->get()
            ->filter(fn (Ledger $ledger) => $ledger->belongsToGroup('DUTIES'))
            ->values();

        return view('tally::tax-accounts.edit', [
            'company' => $company,
            'accounts' => $accounts,
            'ledgers' => $ledgers,
            'components' => TaxComponent::cases(),
        ]);
    }

    public function update(TaxAccountRequest $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        $ledgers = $company->ledgers()->with('accountGroup.parent')->whereIn(
            'id',
            collect(TaxComponent::cases())
                ->map(fn (TaxComponent $component) => $request->input($component->value.'_ledger_id'))
                ->filter()
                ->all()
        )->get()->keyBy('id');

        foreach (TaxComponent::cases() as $component) {
            $ledgerId = $request->input($component->value.'_ledger_id');

            if (! $ledgerId) {
                $company->taxAccounts()->where('component', $component)->delete();

                continue;
            }

            $ledger = $ledgers->get((int) $ledgerId);

            if (! $ledger || ! $ledger->belongsToGroup('DUTIES')) {
                throw ValidationException::withMessages([
                    $component->value.'_ledger_id' => 'Select an active ledger under Duties & Taxes.',
                ]);
            }

            $company->taxAccounts()->updateOrCreate(
                ['component' => $component],
                ['ledger_id' => $ledger->id],
            );
        }

        return redirect()->route('books.tally.tax-accounts.edit')->with('status', 'Tax ledgers saved.');
    }
}
