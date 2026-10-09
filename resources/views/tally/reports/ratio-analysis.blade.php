<x-tally::layouts.app title="Ratio Analysis">
    <x-tally::shell.page title="Ratio Analysis" section="Reports" :description="$company->name.' · '.\Illuminate\Support\Carbon::parse($filters['from'])->format('d-M-y').' to '.\Illuminate\Support\Carbon::parse($filters['to'])->format('d-M-y')">
        <a class="esc-back" data-esc href="{{ tally_route('books.tally.dashboard') }}">Quit</a>
        <div class="tally-sheet">
            <div class="tally-sheet-head">
                <div>
                    <strong>Principal Groups</strong>
                    <span>{{ $company->name }}</span>
                    <span>{{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d-M-y') }} to {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d-M-y') }}</span>
                </div>
                <div>
                    <strong>Principal Ratios</strong>
                    <span>{{ $company->name }}</span>
                    <span>{{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d-M-y') }} to {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d-M-y') }}</span>
                </div>
            </div>
            <div class="tally-sheet-body">
                <table class="data">
                    <tbody>
                        @forelse ($groups as $label => $row)
                            <tr>
                                <td><a href="{{ $row[1] }}">{{ $label }}</a></td>
                                <td class="money">{{ $row[0] }}</td>
                            </tr>
                        @empty
                            <tr><td>No group balance in this period.</td><td></td></tr>
                        @endforelse
                    </tbody>
                </table>
                <table class="data">
                    <tbody>
                        @forelse ($ratios as $label => $row)
                            <tr>
                                <td>
                                    <a href="{{ $row[2] }}">{{ $label }}</a>
                                    <span class="ratio-note">{{ $row[1] }}</span>
                                </td>
                                <td class="money">{{ $row[0] }}</td>
                            </tr>
                        @empty
                            <tr><td>No ratio can be calculated from the posted figures.</td><td></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-shell.page>
</x-layouts.app>
