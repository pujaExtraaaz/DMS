<x-tally::layouts.app :title="'New '.$voucher->voucher_type->label()">
    <x-tally::shell.page :title="'New '.$voucher->voucher_type->label()" section="Transactions" :description="$company->name.' · '.$branch->code.' · '.$year->name">
        <x-tally::voucher.form
            :action="$action"
            method="POST"
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
        />
    </x-shell.page>
</x-layouts.app>
