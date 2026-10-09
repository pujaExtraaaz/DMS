<x-tally::layouts.app :title="$type->label()">
    <x-tally::shell.page :title="$type->label()" section="Transactions" :description="$company->name.' · '.$branch->code.' · '.$year->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route($type->routeName('create')) }}">New {{ strtolower($type->label()) }}</a>
        </x-slot:actions>
        <form class="filters" method="GET" action="{{ tally_route($type->routeName('index')) }}">
            <x-tally::form.field name="q" label="Search">
                <x-tally::form.input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Number or narration" />
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn" href="{{ tally_route($type->routeName('index')) }}">Reset</a>
        </form>
        @if ($transactions->isEmpty())
            <x-tally::ui.empty-state :title="'No '.$type->label().' records'" message="Nothing is posted for this branch and financial year.">
                <a class="btn btn-primary" href="{{ tally_route($type->routeName('create')) }}">New {{ strtolower($type->label()) }}</a>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Number</th><th>Date</th><th>Narration</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td><a href="{{ tally_route($type->routeName('show'), $transaction) }}">{{ $transaction->number }}</a></td>
                                <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                                <td>{{ $transaction->narration ?: '—' }}</td>
                                <td>{{ $transaction->status->label() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $transactions->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
