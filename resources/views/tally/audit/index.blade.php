<x-tally::layouts.app :title="$title">
    <x-tally::shell.page :title="$title" section="Settings" description="Who changed what, when, and in which company, branch, and financial year.">
        <x-slot:actions>
            @if ($security)
                <a class="btn" href="{{ tally_route('books.tally.audit.index') }}">All audit</a>
            @else
                <a class="btn" href="{{ tally_route('books.tally.audit.security') }}">Security events</a>
            @endif
        </x-slot:actions>
        <form class="filters" method="GET">
            <x-tally::form.field name="q" label="Search">
                <x-tally::form.input name="q" value="{{ $filters['q'] }}" placeholder="Description" />
            </x-form.field>
            @unless ($security)
                <x-tally::form.field name="module" label="Module">
                    <select id="module" name="module" class="input">
                        <option value="">All</option>
                        @foreach ($modules as $module)
                            <option value="{{ $module }}" @selected($filters['module'] === $module)>{{ $module }}</option>
                        @endforeach
                    </select>
                </x-form.field>
            @endunless
            <x-tally::form.field name="action" label="Action">
                <select id="action" name="action" class="input">
                    <option value="">All</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ str_replace('_', ' ', $action) }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="entity" label="Entity">
                <select id="entity" name="entity" class="input">
                    <option value="">All</option>
                    @foreach ($entities as $entity)
                        <option value="{{ $entity }}" @selected($filters['entity'] === $entity)>{{ class_basename($entity) }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="user_id" label="User">
                <select id="user_id" name="user_id" class="input">
                    <option value="">All</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected($filters['user_id'] === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="branch_id" label="Branch">
                <select id="branch_id" name="branch_id" class="input">
                    <option value="">All</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected($filters['branch_id'] === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="financial_year_id" label="Financial year">
                <select id="financial_year_id" name="financial_year_id" class="input">
                    <option value="">All</option>
                    @foreach ($years as $year)
                        <option value="{{ $year->id }}" @selected($filters['financial_year_id'] === (string) $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="company_id" label="Company">
                <select id="company_id" name="company_id" class="input">
                    <option value="">All</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected($filters['company_id'] === (string) $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="from" label="From">
                <x-tally::form.input name="from" type="date" value="{{ $filters['from'] }}" />
            </x-form.field>
            <x-tally::form.field name="to" label="To">
                <x-tally::form.input name="to" type="date" value="{{ $filters['to'] }}" />
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn" href="{{ $security ? tally_route('books.tally.audit.security') : tally_route('books.tally.audit.index') }}">Reset</a>
        </form>
        @if ($entries->isEmpty())
            <x-tally::ui.empty-state title="No audit entries" message="Changes, sign-ins, and blocked deletes will appear here." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Entity</th>
                            <th>Company</th>
                            <th>Branch</th>
                            <th>Year</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td><a href="{{ tally_route('books.tally.audit.show', $entry) }}">{{ $entry->created_at->format('d M Y H:i') }}</a></td>
                                <td>{{ $entry->user->name ?? '—' }}</td>
                                <td>{{ str_replace('_', ' ', $entry->action) }}</td>
                                <td>{{ $entry->module }}</td>
                                <td>{{ $entry->entityName() }}</td>
                                <td>{{ $entry->company->name ?? '—' }}</td>
                                <td>{{ $entry->branch->name ?? '—' }}</td>
                                <td>{{ $entry->financialYear->name ?? '—' }}</td>
                                <td>{{ $entry->description ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $entries->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
