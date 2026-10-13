<x-tally::layouts.app :title="$job->number">
    <x-tally::shell.page :title="$job->number" section="Inventory" :description="ucfirst($job->status)">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.job-works.index') }}">Back</a>
        </x-slot:actions>
        <dl class="kv">
            <dt>Vendor</dt><dd>{{ $job->vendor?->name }}</dd>
            <dt>Product</dt><dd>{{ $job->product?->name ?: '—' }}</dd>
            <dt>Issue date</dt><dd>{{ $job->issue_date->format('d M Y') }}</dd>
            <dt>Issued</dt><dd>{{ $job->issued_qty }}</dd>
            <dt>Received</dt><dd>{{ $job->received_qty }}</dd>
            <dt>Balance</dt><dd>{{ $job->balanceQty() }}</dd>
            <dt>Charges</dt><dd>{{ $job->job_charges }}</dd>
            @if ($job->narration)
                <dt>Narration</dt><dd>{{ $job->narration }}</dd>
            @endif
        </dl>
        @if ($job->isOpen())
            <form method="POST" class="panel form-grid" action="{{ tally_route('books.tally.job-works.receive', $job) }}" style="margin-top:1rem">
                @csrf
                <x-tally::form.field name="received_qty" label="Receive qty" required>
                    <x-tally::form.input name="received_qty" required />
                </x-tally::form.field>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Record receipt</button></div>
            </form>
        @endif
    </x-tally::shell.page>
</x-tally::layouts.app>
