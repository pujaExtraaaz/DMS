<x-tally::layouts.app title="Anomalies">
    <x-tally::shell.page title="Anomalies" section="Controls" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.anomalies.store') }}" class="form-actions panel">
            @csrf
            <button class="btn btn-primary" type="submit">Scan duplicate payments</button>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Type</th><th>Document</th><th class="money">Amount</th><th>Message</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($flags as $flag)
                        <tr>
                            <td>{{ $flag->flag_type }}</td>
                            <td>{{ $flag->document_type }} #{{ $flag->document_id }}</td>
                            <td class="money">{{ $flag->amount }}</td>
                            <td>{{ $flag->message }}</td>
                            <td>{{ ucfirst($flag->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No anomalies flagged.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $flags->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
