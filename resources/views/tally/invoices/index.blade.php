<x-tally::layouts.app :title="$kind->label()">
    <x-tally::shell.page :title="$kind->label()" section="Transactions" :description="$company->name.' · '.$year->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route($kind->routeName('create')) }}">New {{ strtolower($kind->documentLabel()) }}</a>
        </x-slot:actions>
        @if ($errors->any())
            <p class="flash is-error" role="alert">{{ $errors->first() }}</p>
        @endif
        <form class="filters" method="GET" action="{{ tally_route($kind->routeName('index')) }}">
            <x-tally::form.field name="q" label="Search">
                <x-tally::form.input name="q" value="{{ $filters['q'] }}" placeholder="Number, party, narration" />
            </x-form.field>
            <x-tally::form.field name="from" label="From">
                <x-tally::form.input name="from" type="date" value="{{ $filters['from'] }}" />
            </x-form.field>
            <x-tally::form.field name="to" label="To">
                <x-tally::form.input name="to" type="date" value="{{ $filters['to'] }}" />
            </x-form.field>
            <x-tally::form.field name="status" label="Status">
                <select id="status" name="status" class="input">
                    <option value="">All statuses</option>
                    @foreach (\Tally\Accounting\VoucherStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="branch_id" label="Branch">
                <select id="branch_id" name="branch_id" class="input">
                    <option value="all" @selected(($filters['branch_id'] ?? '') === 'all')>All branches</option>
                    @foreach ($branches as $row)
                        <option value="{{ $row->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $row->id)>{{ $row->code }} · {{ $row->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn" href="{{ tally_route($kind->routeName('index')) }}">Reset</a>
        </form>
        @if ($invoices->isEmpty())
            <x-tally::ui.empty-state :title="'No '.$kind->label().' invoices'" message="No invoices match this company, financial year, and filter." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice number</th>
                            <th>{{ $kind->partyLabel() }}</th>
                            <th>Narration</th>
                            <th class="money">Amount</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                                <td><a href="{{ tally_route($kind->routeName('show'), $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                <td>{{ $invoice->party->name ?? '—' }}</td>
                                <td>{{ $invoice->narration ?: '—' }}</td>
                                <td class="money">{{ $invoice->grand_total }}</td>
                                <td><span @class(['tag', 'is-bad' => $invoice->isCancelled()])>{{ $invoice->status->label() }}</span></td>
                                <td class="row-actions">
                                    <a href="{{ tally_route($kind->routeName('show'), $invoice) }}">View</a>
                                    @if ($invoice->isDraft())
                                        <a href="{{ tally_route($kind->routeName('edit'), $invoice) }}">Edit</a>
                                        <form method="POST" action="{{ tally_route($kind->routeName('post'), $invoice) }}">
                                            @csrf
                                            <button type="submit">Post</button>
                                        </form>
                                    @elseif ($invoice->isPosted())
                                        <form method="POST" action="{{ tally_route($kind->routeName('cancel'), $invoice) }}" onsubmit="return confirm('Cancel this invoice? The accounting entry stays in the books as cancelled.');">
                                            @csrf
                                            <button type="submit">Cancel</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $invoices->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
