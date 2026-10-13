<x-tally::layouts.app title="Bank signatories">
    <x-tally::shell.page title="Bank signatories" section="Banking" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.banking.signatories.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="user_id" label="User" required>
                <select class="input" name="user_id" required>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="bank_account_id" label="Bank account">
                <select class="input" name="bank_account_id">
                    <option value="">All accounts</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) old('bank_account_id') === (string) $account->id)>{{ $account->bank_name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="single_limit" label="Single limit" required><x-tally::form.input name="single_limit" value="{{ old('single_limit', '0.00') }}" required /></x-tally::form.field>
            <x-tally::form.field name="daily_limit" label="Daily limit" required><x-tally::form.input name="daily_limit" value="{{ old('daily_limit', '0.00') }}" required /></x-tally::form.field>
            <label><input type="checkbox" name="requires_dual" value="1" @checked(old('requires_dual'))> Dual approval</label>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Save limit</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>User</th><th>Bank</th><th class="money">Single</th><th class="money">Daily</th><th>Dual</th><th>Active</th></tr></thead>
                <tbody>
                    @forelse ($limits as $limit)
                        <tr>
                            <td>{{ $limit->user?->name }}</td>
                            <td>{{ $limit->bankAccount?->bank_name ?? 'All' }}</td>
                            <td class="money">{{ $limit->single_limit }}</td>
                            <td class="money">{{ $limit->daily_limit }}</td>
                            <td>{{ $limit->requires_dual ? 'Yes' : 'No' }}</td>
                            <td>{{ $limit->is_active ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No signatory limits yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $limits->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
