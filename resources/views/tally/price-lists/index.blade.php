<x-tally::layouts.app title="Price lists">
    <x-tally::shell.page title="Price lists" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.price-lists.create') }}">New price list</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Items</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lists as $list)
                        <tr>
                            <td>{{ $list->name }}</td>
                            <td>{{ $list->lines_count }}</td>
                            <td>
                                <a href="{{ tally_route('books.tally.price-lists.edit', $list) }}">Alter</a>
                                <form method="POST" action="{{ tally_route('books.tally.price-lists.destroy', $list) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No price lists yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
