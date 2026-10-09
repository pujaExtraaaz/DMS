<x-tally::layouts.app :title="$report['title']">
    <x-tally::shell.page :title="$report['title']" section="Reports" :description="$company->name.' · '.$from.' to '.$to">
        <form class="filters" method="GET">
            <x-tally::form.field name="from" label="From">
                <x-tally::form.input name="from" type="date" :value="$from" />
            </x-form.field>
            <x-tally::form.field name="to" label="To">
                <x-tally::form.input name="to" type="date" :value="$to" />
            </x-form.field>
            @if (($registrations ?? collect())->isNotEmpty())
                <x-tally::form.field name="gst_registration_id" label="GST registration">
                    @include('tally::masters._picker', [
                        'name' => 'gst_registration_id',
                        'options' => $registrations->mapWithKeys(fn ($row) => [$row->id => $row->gstin])->all(),
                        'current' => $registrationId,
                        'placeholder' => 'All GSTINs',
                        'optional' => true,
                        'create' => tally_route('books.tally.gst-registrations.create'),
                    ])
                </x-form.field>
            @endif
            <button class="btn btn-primary" type="submit">Apply</button>
            <button class="btn" type="button" data-print>Print</button>
            @if (! empty($download))
                <a class="btn" href="{{ tally_route($download, request()->only(['from', 'to', 'gst_registration_id'])) }}">Download GST JSON</a>
            @endif
        </form>
        @foreach ($report['sections'] as $section)
            <h2>{{ $section['heading'] }}</h2>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            @foreach ($section['columns'] as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($section['rows'] as $row)
                            <tr>
                                @foreach ($section['columns'] as $column)
                                    <td>
                                        @if ($column === ($section['columns'][0] ?? '') && ! empty($row['url']))
                                            <a href="{{ $row['url'] }}">{{ $row[$column] ?? '' }}</a>
                                        @else
                                            {{ $row[$column] ?? '' }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($section['columns']) }}">No rows for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </x-shell.page>
</x-layouts.app>
