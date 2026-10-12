<x-tally::layouts.app title="Correcting entry">
    <x-tally::shell.page title="Correcting entry" section="Transactions" :description="'Corrects '.$original->voucher_number">
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
            :business-units="$businessUnits"
            :departments="$departments"
            :open-bills="$openBills"
            :currencies="$currencies ?? []"
            :payment-requests="$paymentRequests ?? []"
            :merchants="$merchants ?? []"
            :company="$company"
            :balances="$balances ?? []"
            :voucher-classes="$voucherClasses ?? []"
            :deduction-sections="$deductionSections ?? []"
        />
    </x-tally::shell.page>
</x-tally::layouts.app>
