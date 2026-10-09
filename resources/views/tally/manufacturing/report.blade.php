<x-tally::layouts.app :title="$title">
    <x-tally::shell.page :title="$title" section="Reports" :description="$company->name">
        @if ($report['rows'] === [])
            <x-tally::ui.empty-state title="Nothing to show" message="Post a manufacturing journal to see this report." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            @foreach (array_keys($report['rows'][0]) as $heading)
                                <th>{{ str_replace('_', ' ', ucfirst($heading)) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['rows'] as $row)
                            <tr>
                                @foreach ($row as $value)
                                    <td>{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-shell.page>
</x-layouts.app>
