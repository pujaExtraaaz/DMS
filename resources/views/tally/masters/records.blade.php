<x-tally::layouts.app :title="$title">
    <x-tally::shell.page :title="$title" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ $create }}">Create</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        @foreach ($columns as $heading => $attribute)
                            <th>{{ $heading }}</th>
                        @endforeach
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            @foreach ($columns as $attribute)
                                <td>
                                    @php
                                        $value = $record;
                                        foreach (explode('.', $attribute) as $part) {
                                            $value = $value->{$part} ?? null;
                                        }
                                    @endphp
                                    {{ $value instanceof \DateTimeInterface ? $value->format('d M Y') : $value }}
                                </td>
                            @endforeach
                            <td>
                                <x-tally::ui.record-actions
                                    :edit="tally_route($edit, $record)"
                                    :delete="tally_route($delete, $record)"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columns) + 1 }}">No records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $records->links() }}
    </x-shell.page>
</x-layouts.app>
