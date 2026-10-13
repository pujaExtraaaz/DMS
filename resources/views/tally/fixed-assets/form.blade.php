<x-tally::layouts.app title="New fixed asset">
    <x-tally::shell.page title="New fixed asset" section="Fixed assets" :description="$company->name">
        <form method="POST" class="panel" action="{{ tally_route('books.tally.fixed-assets.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="asset_code" label="Asset code">
                    <x-tally::form.input name="asset_code" value="{{ old('asset_code') }}" placeholder="Auto" />
                </x-tally::form.field>
                <x-tally::form.field name="name" label="Name" required>
                    <x-tally::form.input name="name" value="{{ old('name') }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="category_id" label="Category">
                    <select class="input" name="category_id">
                        <option value="">—</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="acquisition_date" label="Acquisition date" required>
                    <x-tally::form.input type="date" name="acquisition_date" value="{{ old('acquisition_date', optional($asset->acquisition_date)->toDateString() ?? now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="capitalization_date" label="Capitalization date">
                    <x-tally::form.input type="date" name="capitalization_date" value="{{ old('capitalization_date') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="gross_value" label="Gross value" required>
                    <x-tally::form.input name="gross_value" value="{{ old('gross_value') }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="residual_value" label="Residual value">
                    <x-tally::form.input name="residual_value" value="{{ old('residual_value') }}" placeholder="From category %" />
                </x-tally::form.field>
                <x-tally::form.field name="location" label="Location">
                    <x-tally::form.input name="location" value="{{ old('location') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="department" label="Department">
                    <x-tally::form.input name="department" value="{{ old('department') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="custodian" label="Custodian">
                    <x-tally::form.input name="custodian" value="{{ old('custodian') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="is_cip" label="CIP">
                    <label><input type="checkbox" name="is_cip" value="1" @checked(old('is_cip'))> Capital work in progress</label>
                </x-tally::form.field>
                <x-tally::form.field name="parent_asset_id" label="Parent asset (component)">
                    <select class="input" name="parent_asset_id">
                        <option value="">—</option>
                        @foreach ($parents ?? [] as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_asset_id') === (string) $parent->id)>{{ $parent->asset_code }} · {{ $parent->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.fixed-assets.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
