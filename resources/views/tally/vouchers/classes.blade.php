<x-tally::layouts.app title="Voucher Class">
    <x-tally::shell.page title="Voucher Class" section="Transactions" :description="$company->name">
        <form class="tally-master" method="POST" action="{{ tally_route('books.tally.vouchers.classes.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="voucher_type" label="Voucher type" required>
                    <select id="voucher_type" name="voucher_type" class="input" required>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(old('voucher_type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="name" label="Class name" required>
                    <input class="input" name="name" value="{{ old('name') }}" maxlength="120" required>
                </x-form.field>
                <x-tally::form.field name="default_ledger_id" label="Default ledger">
                    <select id="default_ledger_id" name="default_ledger_id" class="input">
                        <option value="">Not Applicable</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" @selected((string) old('default_ledger_id') === (string) $ledger->id)>{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
            </div>
            <button class="btn btn-primary" type="submit">A: Accept</button>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Type</th><th>Class</th><th>Default ledger</th><th></th></tr></thead>
                <tbody>
                    @forelse ($classes as $class)
                        <tr>
                            <td>{{ $class->voucher_type }}</td>
                            <td>{{ $class->name }}</td>
                            <td>{{ $class->ledger?->name ?: 'Not Applicable' }}</td>
                            <td>
                                <form method="POST" action="{{ tally_route('books.tally.vouchers.classes.destroy', $class) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Not Applicable. Create a class to use it on a voucher.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
