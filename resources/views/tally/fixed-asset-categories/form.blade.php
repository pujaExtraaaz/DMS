<x-tally::layouts.app :title="$category->exists ? 'Edit category' : 'New category'">
    <x-tally::shell.page :title="$category->exists ? 'Edit category' : 'New category'" section="Fixed assets" :description="$company->name">
        <form method="POST" class="panel" action="{{ $category->exists ? tally_route('books.tally.fixed-asset-categories.update', $category) : tally_route('books.tally.fixed-asset-categories.store') }}">
            @csrf
            @if ($category->exists) @method('PUT') @endif
            <div class="form-grid">
                <x-tally::form.field name="name" label="Name" required>
                    <x-tally::form.input name="name" :value="old('name', $category->name)" required />
                </x-tally::form.field>
                <x-tally::form.field name="depreciation_method" label="Method" required>
                    <select class="input" name="depreciation_method" required>
                        <option value="straight_line" @selected(old('depreciation_method', $category->depreciation_method) === 'straight_line')>Straight line</option>
                        <option value="wdv" @selected(old('depreciation_method', $category->depreciation_method) === 'wdv')>WDV</option>
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="useful_life_years" label="Useful life (years)" required>
                    <x-tally::form.input name="useful_life_years" :value="old('useful_life_years', $category->useful_life_years)" required />
                </x-tally::form.field>
                <x-tally::form.field name="residual_percent" label="Residual %" required>
                    <x-tally::form.input name="residual_percent" :value="old('residual_percent', $category->residual_percent)" required />
                </x-tally::form.field>
                <x-tally::form.field name="asset_ledger_id" label="Asset ledger">
                    <select class="input" name="asset_ledger_id">
                        <option value="">—</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" @selected((string) old('asset_ledger_id', $category->asset_ledger_id) === (string) $ledger->id)>{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="accum_depr_ledger_id" label="Accum. depreciation">
                    <select class="input" name="accum_depr_ledger_id">
                        <option value="">—</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" @selected((string) old('accum_depr_ledger_id', $category->accum_depr_ledger_id) === (string) $ledger->id)>{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="depr_expense_ledger_id" label="Depreciation expense">
                    <select class="input" name="depr_expense_ledger_id">
                        <option value="">—</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" @selected((string) old('depr_expense_ledger_id', $category->depr_expense_ledger_id) === (string) $ledger->id)>{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.active :checked="old('is_active', $category->is_active ?? true)" />
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.fixed-asset-categories.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
