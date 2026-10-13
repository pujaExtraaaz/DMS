<x-tally::layouts.app title="Comparative report">
    <x-tally::shell.page title="Comparative P&amp;L" section="Reports" :description="$company->name.' · '.$year->name">
        <form method="GET" class="filters">
            <label>From <input class="input" type="date" name="from" value="{{ $from }}"></label>
            <label>To <input class="input" type="date" name="to" value="{{ $to }}"></label>
            <button class="btn" type="submit">Compare</button>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Group</th>
                        <th class="money">Current</th>
                        <th class="money">Prior</th>
                        <th class="money">Variance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparison['current']['groups'] as $key => $amount)
                        <tr>
                            <td>{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                            <td class="money">{{ $amount }}</td>
                            <td class="money">{{ $comparison['prior']['groups'][$key] ?? '0.00' }}</td>
                            <td class="money">{{ $comparison['variance'][$key] ?? '0.00' }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td><strong>Net</strong></td>
                        <td class="money"><strong>{{ $comparison['current']['net'] }}</strong></td>
                        <td class="money"><strong>{{ $comparison['prior']['net'] }}</strong></td>
                        <td class="money"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
