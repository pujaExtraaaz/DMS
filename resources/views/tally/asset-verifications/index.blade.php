<x-tally::layouts.app title="Asset verification">
    <x-tally::shell.page title="Asset verification" section="Fixed assets" :description="$company->name">
        <form method="POST" class="panel form-grid" action="{{ tally_route('books.tally.asset-verifications.store') }}" style="margin-bottom:1rem">
            @csrf
            <x-tally::form.field name="fixed_asset_id" label="Asset" required>
                <select class="input" name="fixed_asset_id" required>
                    <option value="">Select</option>
                    @foreach ($assets as $asset)
                        <option value="{{ $asset->id }}">{{ $asset->asset_code }} — {{ $asset->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="verification_date" label="Date" required>
                <x-tally::form.input type="date" name="verification_date" value="{{ old('verification_date', now()->toDateString()) }}" required />
            </x-tally::form.field>
            <x-tally::form.field name="verified_by" label="Verified by">
                <x-tally::form.input name="verified_by" value="{{ old('verified_by') }}" />
            </x-tally::form.field>
            <x-tally::form.field name="condition" label="Condition" required>
                <select class="input" name="condition" required>
                    @foreach (['good','fair','poor','damaged','missing'] as $condition)
                        <option value="{{ $condition }}" @selected(old('condition', 'good') === $condition)>{{ ucfirst($condition) }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="found" label="Found">
                <label><input type="checkbox" name="found" value="1" @checked(old('found', true))> Asset found</label>
            </x-tally::form.field>
            <x-tally::form.field name="notes" label="Notes" class="span-full">
                <x-tally::form.input name="notes" value="{{ old('notes') }}" />
            </x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Record verification</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Asset</th><th>By</th><th>Condition</th><th>Found</th><th>Notes</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row->verification_date->format('d M Y') }}</td>
                            <td>
                                @if ($row->fixedAsset)
                                    <a href="{{ tally_route('books.tally.fixed-assets.show', $row->fixedAsset) }}">{{ $row->fixedAsset->asset_code }}</a>
                                @endif
                            </td>
                            <td>{{ $row->verified_by ?: '—' }}</td>
                            <td>{{ ucfirst($row->condition) }}</td>
                            <td>{{ $row->found ? 'Yes' : 'No' }}</td>
                            <td>{{ $row->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No verifications yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rows->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
