<x-tally::layouts.app :title="$title">
    <x-tally::shell.page :title="$title" section="Transactions" :description="$company->name.' · '.$branch->code.' · '.$year->name">
        <x-tally::invoice.form
            :action="$action"
            :method="$method"
            :kind="$kind"
            :invoice="$invoice"
            :lines="$lines"
            :party-ledgers="$partyLedgers"
            :account-ledgers="$accountLedgers"
            :products="$products ?? []"
            :godowns="$godowns ?? []"
            :tax-rates="$taxRates ?? []"
            :hsn-sacs="$hsnSacs ?? []"
            :price-lists="$priceLists ?? []"
            :next-number="$nextNumber"
        />
    </x-shell.page>
</x-layouts.app>
