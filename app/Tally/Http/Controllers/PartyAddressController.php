<?php

namespace Tally\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\Party;
use Tally\Models\PartyAddress;

class PartyAddressController extends Controller
{
    public function store(Request $request, WorkspaceContext $context, Party $party): RedirectResponse
    {
        abort_unless($context->company() && $party->company_id === $context->company()->id, 404);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:80'],
            'kind' => ['required', Rule::in(['billing', 'shipping', 'both'])],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'gstin' => ['nullable', 'string', 'max:15'],
            'place_of_supply' => ['nullable', 'string', 'max:80'],
            'is_default_billing' => ['nullable', 'boolean'],
            'is_default_shipping' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_default_billing')) {
            $party->addresses()->update(['is_default_billing' => false]);
        }
        if ($request->boolean('is_default_shipping')) {
            $party->addresses()->update(['is_default_shipping' => false]);
        }

        $party->addresses()->create([
            'company_id' => $party->company_id,
            ...$data,
            'is_default_billing' => $request->boolean('is_default_billing'),
            'is_default_shipping' => $request->boolean('is_default_shipping'),
            'is_active' => true,
        ]);

        return back()->with('status', 'Address added.');
    }

    public function destroy(WorkspaceContext $context, Party $party, PartyAddress $address): RedirectResponse
    {
        abort_unless(
            $context->company()
            && $party->company_id === $context->company()->id
            && $address->party_id === $party->id,
            404
        );

        $address->delete();

        return back()->with('status', 'Address removed.');
    }

    public function storePriceList(Request $request, WorkspaceContext $context, Party $party): RedirectResponse
    {
        abort_unless($context->company() && $party->company_id === $context->company()->id, 404);

        $data = $request->validate([
            'price_list_id' => ['required', 'integer', Rule::exists('acct_price_lists', 'id')->where('company_id', $party->company_id)],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        \Tally\Models\PartyPriceList::query()->updateOrCreate(
            [
                'party_id' => $party->id,
                'price_list_id' => $data['price_list_id'],
            ],
            [
                'company_id' => $party->company_id,
                'valid_from' => $data['valid_from'] ?? null,
                'valid_to' => $data['valid_to'] ?? null,
                'is_active' => true,
            ]
        );

        return back()->with('status', 'Party price list saved.');
    }
}
