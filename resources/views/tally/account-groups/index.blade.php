<x-tally::layouts.app title="Account groups">
    <x-tally::shell.page title="Account groups" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.account-groups.create') }}">New group</a>
        </x-slot:actions>
        @if ($groups->isEmpty())
            <x-tally::ui.empty-state title="No account groups" message="Prepare the standard chart, or add the first group.">
                <form method="POST" action="{{ tally_route('books.tally.account-groups.standard') }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Prepare standard groups</button>
                </form>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Nature</th>
                            <th>Ledgers</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groups as $row)
                            @php($group = $row['group'])
                            <tr>
                                <td>
                                    <a href="{{ tally_route('books.tally.account-groups.show', $group) }}" style="padding-left: {{ $row['depth'] * 16 }}px">{{ $group->name }}</a>
                                    @if ($group->is_system)
                                        <span class="tag">System</span>
                                    @endif
                                </td>
                                <td>{{ $group->code ?: '—' }}</td>
                                <td>{{ $group->nature->label() }}</td>
                                <td>{{ $group->ledgers_count }}</td>
                                <td><x-tally::ui.status :active="$group->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :view="tally_route('books.tally.account-groups.show', $group)"
                                        :edit="tally_route('books.tally.account-groups.edit', $group)"
                                        :active="$group->is_active"
                                        :toggle="tally_route('books.tally.account-groups.activation', $group)"
                                        :delete="tally_route('books.tally.account-groups.destroy', $group)"
                                        delete-reason="This account group has ledgers or child groups and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-shell.page>
</x-layouts.app>
