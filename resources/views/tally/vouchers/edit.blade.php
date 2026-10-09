<x-tally::layouts.app :title="'Edit '.$voucher->voucher_number">
    <x-tally::shell.page :title="'Edit '.$voucher->voucher_number" section="Transactions" :description="$voucher->voucher_type->label().' · '.$year->name">
        @unless ($postOnly ?? false)
            <x-slot:actions>
                <form id="delete-voucher" method="POST" action="{{ tally_route('books.tally.vouchers.destroy', $voucher) }}" onsubmit="return confirm('Delete this draft?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn" type="submit">Delete draft</button>
                </form>
            </x-slot:actions>
        @endunless
        <x-tally::voucher.form
            :action="$action ?? tally_route('books.tally.vouchers.update', $voucher)"
            method="PUT"
            :voucher="$voucher"
            :entries="$entries"
            :ledgers="$ledgers"
            :cash-ledgers="$cashLedgers"
            :other-ledgers="$otherLedgers"
            :next-number="$nextNumber"
            :cost-centres="$costCentres"
            :open-bills="$openBills"
            :currencies="$currencies ?? []"
            :payment-requests="$paymentRequests ?? []"
            :merchants="$merchants ?? []"
            :company="$company"
            :balances="$balances ?? []"
            :voucher-classes="$voucherClasses ?? []"
            :deduction-sections="$deductionSections ?? []"
            :post-only="$postOnly ?? false"
            :back="$back ?? null"
        />
    </x-shell.page>
</x-layouts.app>
