<x-tally::layouts.app title="Employee loan">
    <x-tally::shell.page title="Loan · {{ $loan->employee?->name }}" section="Payroll" :description="$company->name">
        <dl class="meta">
            <div><dt>Principal</dt><dd>{{ $loan->principal }}</dd></div>
            <div><dt>EMI</dt><dd>{{ $loan->emi_amount }}</dd></div>
            <div><dt>Outstanding</dt><dd>{{ $loan->outstanding }}</dd></div>
            <div><dt>Tenure</dt><dd>{{ $loan->tenure_months }} months</dd></div>
            <div><dt>Status</dt><dd>{{ ucfirst($loan->status) }}</dd></div>
        </dl>
    </x-tally::shell.page>
</x-tally::layouts.app>
