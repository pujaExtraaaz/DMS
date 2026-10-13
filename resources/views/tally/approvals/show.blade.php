<x-tally::layouts.app title="Approval request">
    <x-tally::shell.page title="Approval #{{ $request->id }}" section="Controls" :description="$company->name">
        <dl class="meta">
            <div><dt>Document</dt><dd>{{ $request->document_type }} #{{ $request->document_id }}</dd></div>
            <div><dt>Amount</dt><dd>{{ $request->amount }}</dd></div>
            <div><dt>Status</dt><dd>{{ ucfirst($request->status) }}</dd></div>
            <div><dt>Policy</dt><dd>{{ $request->policy?->name ?? '—' }}</dd></div>
        </dl>
        @if ($request->status === 'pending')
            <div class="form-actions">
                <form method="POST" action="{{ tally_route('books.tally.approvals.approve', $request) }}">@csrf<button class="btn btn-primary" type="submit">Approve</button></form>
                <form method="POST" action="{{ tally_route('books.tally.approvals.reject', $request) }}">@csrf<button class="btn" type="submit">Reject</button></form>
            </div>
        @endif
    </x-tally::shell.page>
</x-tally::layouts.app>
