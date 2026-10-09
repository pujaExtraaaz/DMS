<x-tally::layouts.app title="Balance Sheet">
    <x-tally::shell.page title="Balance Sheet" section="Reports" :description="$company->name.' as at '.\Illuminate\Support\Carbon::parse($filters['to'])->format('d-M-y')">
        <a class="esc-back" data-esc href="{{ $group ? tally_route('books.tally.reports.balance-sheet', request()->except('group')) : tally_route('books.tally.dashboard') }}">Quit</a>
        @php
            $collect = function (array $rows, array $order) use ($group): array {
                $sections = [];

                foreach ($order as $name) {
                    $sections[$name] = ['name' => $name, 'cents' => 0, 'lines' => []];
                }

                foreach ($rows as $row) {
                    $name = $row['group'] ?? 'Other';

                    if ($group && $name !== $group) {
                        continue;
                    }

                    if (! isset($sections[$name])) {
                        $sections[$name] = ['name' => $name, 'cents' => 0, 'lines' => []];
                    }

                    $cents = (int) round(((float) str_replace(',', '', $row['amount'])) * 100);
                    $sections[$name]['cents'] += $cents;
                    $sections[$name]['lines'][] = $row;
                }

                return array_values(array_filter($sections, fn (array $section) => $section['lines'] !== []));
            };
            $liabilitySections = $collect($report['liabilities'], ['Capital Account', 'Loans (Liability)', 'Current Liabilities']);
            $assetSections = $collect($report['assets'], ['Fixed Assets', 'Investments', 'Current Assets']);
            $profitOnLiability = $report['profit_side'] === 'liability' && $report['profit'] !== '0.00';
            $profitOnAsset = $report['profit_side'] === 'asset' && $report['profit'] !== '0.00';
        @endphp
        <div class="tally-sheet">
            <div class="tally-sheet-head">
                <div>
                    <strong>Liabilities</strong>
                    <span>{{ $company->name }}</span>
                    <span>as at {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d-M-y') }}</span>
                </div>
                <div>
                    <strong>Assets</strong>
                    <span>{{ $company->name }}</span>
                    <span>as at {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d-M-y') }}</span>
                </div>
            </div>
            <div class="tally-sheet-body">
                <table class="data">
                    <tbody>
                        @foreach ($liabilitySections as $section)
                            <tr>
                                <td>
                                    @unless ($group)
                                        <a href="{{ tally_route('books.tally.reports.balance-sheet', array_merge(request()->query(), ['group' => $section['name']])) }}">{{ $section['name'] }}</a>
                                    @else
                                        {{ $section['name'] }}
                                    @endunless
                                </td>
                                <td class="money">{{ number_format($section['cents'] / 100, 2, '.', '') }}</td>
                            </tr>
                            @foreach ($section['lines'] as $row)
                                <tr>
                                    <td class="indent"><a href="{{ $row['url'] }}">{{ $row['name'] }}</a></td>
                                    <td class="money">{{ $row['amount'] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                        @if ($profitOnLiability || ($report['opening_side'] ?? null) === 'liability')
                            <tr>
                                <td><a href="{{ tally_route('books.tally.reports.profit-and-loss', request()->only(['to', 'branch_id'])) }}">Profit &amp; Loss A/c</a></td>
                                <td class="money"></td>
                            </tr>
                            @if (($report['opening_side'] ?? null) === 'liability')
                                <tr>
                                    <td class="indent">Opening Balance</td>
                                    <td class="money">{{ $report['opening_profit'] }}</td>
                                </tr>
                            @endif
                            @if ($profitOnLiability)
                                <tr>
                                    <td class="indent">Current Period</td>
                                    <td class="money">{{ $report['profit'] }}</td>
                                </tr>
                            @endif
                        @endif
                    </tbody>
                    <tfoot>
                        <tr><th>Total</th><th class="money">{{ $report['liability_total'] }}</th></tr>
                    </tfoot>
                </table>
                <table class="data">
                    <tbody>
                        @foreach ($assetSections as $section)
                            <tr>
                                <td>
                                    @unless ($group)
                                        <a href="{{ tally_route('books.tally.reports.balance-sheet', array_merge(request()->query(), ['group' => $section['name']])) }}">{{ $section['name'] }}</a>
                                    @else
                                        {{ $section['name'] }}
                                    @endunless
                                </td>
                                <td class="money">{{ number_format($section['cents'] / 100, 2, '.', '') }}</td>
                            </tr>
                            @foreach ($section['lines'] as $row)
                                <tr>
                                    <td class="indent"><a href="{{ $row['url'] }}">{{ $row['name'] }}</a></td>
                                    <td class="money">{{ $row['amount'] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                        @if ($profitOnAsset || ($report['opening_side'] ?? null) === 'asset')
                            <tr>
                                <td><a href="{{ tally_route('books.tally.reports.profit-and-loss', request()->only(['to', 'branch_id'])) }}">Profit &amp; Loss A/c</a></td>
                                <td class="money"></td>
                            </tr>
                            @if (($report['opening_side'] ?? null) === 'asset')
                                <tr>
                                    <td class="indent">Opening Balance</td>
                                    <td class="money">{{ $report['opening_profit'] }}</td>
                                </tr>
                            @endif
                            @if ($profitOnAsset)
                                <tr>
                                    <td class="indent">Current Period</td>
                                    <td class="money">{{ $report['profit'] }}</td>
                                </tr>
                            @endif
                        @endif
                    </tbody>
                    <tfoot>
                        <tr><th>Total</th><th class="money">{{ $report['asset_total'] }}</th></tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @if ($report['asset_total'] !== $report['liability_total'])
            <p class="flash is-error" role="alert">Assets {{ $report['asset_total'] }} do not equal liabilities {{ $report['liability_total'] }}.</p>
        @endif
    </x-shell.page>
</x-layouts.app>
