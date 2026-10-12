<x-tally::layouts.app :title="$project->name">
    <x-tally::shell.page :title="$project->name" section="Masters" :description="$company->name">
        <dl class="kv">
            <dt>Customer</dt><dd>{{ $project->customer?->name ?: '—' }}</dd>
            <dt>Contract</dt><dd>{{ $project->contract_value }}</dd>
            <dt>Status</dt><dd>{{ ucfirst($project->status) }}</dd>
        </dl>
        <form method="POST" action="{{ tally_route('books.tally.projects.milestones.store', $project) }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="name" label="Milestone" required><x-tally::form.input name="name" required /></x-tally::form.field>
            <x-tally::form.field name="percent" label="%"><x-tally::form.input name="percent" /></x-tally::form.field>
            <x-tally::form.field name="amount" label="Amount"><x-tally::form.input name="amount" /></x-tally::form.field>
            <x-tally::form.field name="due_date" label="Due"><x-tally::form.input type="date" name="due_date" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Add milestone</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Milestone</th><th class="money">%</th><th class="money">Amount</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($project->milestones as $milestone)
                        <tr>
                            <td>{{ $milestone->name }}</td>
                            <td class="money">{{ $milestone->percent }}</td>
                            <td class="money">{{ $milestone->amount }}</td>
                            <td>{{ ucfirst($milestone->status) }}</td>
                            <td>
                                @if (! $milestone->billed_invoice_id)
                                    <a class="btn btn-primary" href="{{ tally_route('books.tally.projects.milestones.bill', [$project, $milestone]) }}">Bill</a>
                                @else
                                    Invoiced
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No milestones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
