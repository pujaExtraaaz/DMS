<x-tally::layouts.app title="Vouchers">
    <x-tally::shell.page title="Vouchers" section="Transactions" :description="$company->name.' · '.$year->name">
        <x-slot:actions>
            <a class="btn {{ ($filters['voucher_type'] ?? null) === 'journal' || ! ($filters['voucher_type'] ?? null) ? 'btn-primary' : '' }}" href="{{ tally_route('books.tally.vouchers.create') }}">New journal</a>
            <a class="btn {{ ($filters['voucher_type'] ?? null) === 'payment' ? 'btn-primary' : '' }}" href="{{ tally_route('books.tally.vouchers.payment.create') }}">New payment</a>
            <a class="btn {{ ($filters['voucher_type'] ?? null) === 'receipt' ? 'btn-primary' : '' }}" href="{{ tally_route('books.tally.vouchers.receipt.create') }}">New receipt</a>
            <a class="btn {{ ($filters['voucher_type'] ?? null) === 'contra' ? 'btn-primary' : '' }}" href="{{ tally_route('books.tally.vouchers.contra.create') }}">New contra</a>
        </x-slot:actions>
        <form class="filters" method="GET" action="{{ tally_route('books.tally.vouchers.index') }}">
            <x-tally::form.field name="number" label="Voucher number">
                <x-tally::form.input name="number" value="{{ $filters['number'] }}" placeholder="Number" />
            </x-form.field>
            <x-tally::form.field name="q" label="Search">
                <x-tally::form.input name="q" value="{{ $filters['q'] }}" placeholder="Narration or reference" />
            </x-form.field>
            <x-tally::form.field name="from" label="From">
                <x-tally::form.input name="from" type="date" value="{{ $filters['from'] }}" />
            </x-form.field>
            <x-tally::form.field name="to" label="To">
                <x-tally::form.input name="to" type="date" value="{{ $filters['to'] }}" />
            </x-form.field>
            <x-tally::form.field name="voucher_type" label="Type">
                <select id="voucher_type" name="voucher_type" class="input">
                    <option value="">All types</option>
                    @foreach ([\Tally\Accounting\VoucherType::Journal, \Tally\Accounting\VoucherType::Payment, \Tally\Accounting\VoucherType::Receipt, \Tally\Accounting\VoucherType::Contra] as $type)
                        <option value="{{ $type->value }}" @selected(($filters['voucher_type'] ?? null) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="status" label="Status">
                <select id="status" name="status" class="input">
                    <option value="">All statuses</option>
                    @foreach (\Tally\Accounting\VoucherStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="ledger_id" label="Ledger">
                <select id="ledger_id" name="ledger_id" class="input">
                    <option value="">All ledgers</option>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}" @selected((string) ($filters['ledger_id'] ?? '') === (string) $ledger->id)>{{ $ledger->name }}</option>
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
            <a class="btn" href="{{ tally_route('books.tally.vouchers.index') }}">Reset</a>
        </form>
        @if ($vouchers->isEmpty())
            <x-tally::ui.empty-state title="No vouchers" message="No vouchers match this company, financial year, and filter." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Number</th>
                            <th>Type</th>
                            <th>Ledgers</th>
                            <th>Narration</th>
                            <th class="money">Debit</th>
                            <th class="money">Credit</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vouchers as $voucher)
                            <tr>
                                <td>{{ $voucher->voucher_date->format('d M Y') }}</td>
                                <td><a href="{{ tally_route('books.tally.vouchers.show', $voucher) }}">{{ $voucher->voucher_number }}</a></td>
                                <td>{{ $voucher->voucher_type->label() }}</td>
                                <td>{{ $voucher->ledgerSummary() }}</td>
                                <td>{{ $voucher->narration ?: '—' }}</td>
                                <td class="money">{{ $voucher->total_debit }}</td>
                                <td class="money">{{ $voucher->total_credit }}</td>
                                <td><span @class(['tag', 'is-bad' => $voucher->isCancelled()])>{{ $voucher->status->label() }}</span></td>
                                <td class="row-actions">
                                    <a href="{{ tally_route('books.tally.vouchers.show', $voucher) }}">View</a>
                                    @if ($voucher->isDraft())
                                        <a href="{{ tally_route('books.tally.vouchers.edit', $voucher) }}">Edit</a>
                                        <form method="POST" action="{{ tally_route('books.tally.vouchers.post', $voucher) }}">
                                            @csrf
                                            <button type="submit">Post</button>
                                        </form>
                                        <form method="POST" action="{{ tally_route('books.tally.vouchers.destroy', $voucher) }}" onsubmit="return confirm('Delete this draft? Posted vouchers are cancelled, not deleted.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit">Delete</button>
                                        </form>
                                    @elseif ($voucher->isPosted())
                                        <form method="POST" action="{{ tally_route('books.tally.vouchers.cancel', $voucher) }}" onsubmit="return confirm('Cancel this voucher?');">
                                            @csrf
                                            <button type="submit">Cancel</button>
                                        </form>
                                    @endif
                                    <a href="{{ tally_route('books.tally.vouchers.print', $voucher) }}" target="_blank">Print</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $vouchers->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
