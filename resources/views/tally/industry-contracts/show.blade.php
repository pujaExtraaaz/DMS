<x-tally::layouts.app title="Industry contract">
    <x-tally::shell.page title="{{ $contract->name }}" section="Industry" :description="$contract->contract_number">
        <dl class="meta">
            <div><dt>Industry</dt><dd>{{ $contract->industry }}</dd></div>
            <div><dt>Value</dt><dd>{{ $contract->contract_value }}</dd></div>
            <div><dt>Recognized</dt><dd>{{ $contract->recognized_revenue }}</dd></div>
            <div><dt>Retention %</dt><dd>{{ $contract->retention_percent }}</dd></div>
            <div><dt>Status</dt><dd>{{ ucfirst($contract->status) }}</dd></div>
            <div><dt>Party</dt><dd>{{ $contract->partyLedger?->name ?? '—' }}</dd></div>
        </dl>
    </x-tally::shell.page>
</x-tally::layouts.app>
