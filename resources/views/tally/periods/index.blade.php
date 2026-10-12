<x-tally::layouts.app title="Accounting periods">
    <x-tally::shell.page title="Accounting periods" section="Utilities" :description="$company->name.' · '.$year->name">
        <form class="filters" method="POST" action="{{ tally_route('books.tally.periods.lock-through') }}">
            @csrf
            <x-tally::form.field name="through_date" label="Lock through">
                <x-tally::form.input type="date" name="through_date" value="{{ old('through_date', now()->toDateString()) }}" />
            </x-tally::form.field>
            <button class="btn btn-primary" type="submit">Lock periods</button>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Period</th><th>From</th><th>To</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($periods as $period)
                        <tr>
                            <td>{{ $period->name }}</td>
                            <td>{{ $period->start_date->format('d M Y') }}</td>
                            <td>{{ $period->end_date->format('d M Y') }}</td>
                            <td>{{ $period->isLocked() ? 'Locked' : 'Open' }}</td>
                            <td>
                                @if ($period->isLocked())
                                    <form method="POST" action="{{ tally_route('books.tally.periods.unlock', $period) }}">@csrf<button class="btn" type="submit">Unlock</button></form>
                                @else
                                    <form method="POST" action="{{ tally_route('books.tally.periods.lock', $period) }}">@csrf<button class="btn btn-primary" type="submit">Lock</button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
