<x-tally::layouts.app title="Approvals">
    <x-tally::shell.page title="Approvals" section="Controls" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.approvals.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="document_type" label="Document type" required>
                <select class="input" name="document_type" required>
                    @foreach (['voucher','payment_batch','purchase_order','expense_claim'] as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="document_id" label="Document ID" required><x-tally::form.input type="number" name="document_id" required /></x-tally::form.field>
            <x-tally::form.field name="amount" label="Amount"><x-tally::form.input name="amount" value="0" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Request</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Document</th><th class="money">Amount</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($requests as $req)
                        <tr>
                            <td>{{ $req->document_type }} #{{ $req->document_id }}</td>
                            <td class="money">{{ $req->amount }}</td>
                            <td>{{ ucfirst($req->status) }}</td>
                            <td><a href="{{ tally_route('books.tally.approvals.show', $req) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No approval requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
