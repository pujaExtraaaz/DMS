<x-tally::layouts.app title="New job work">
    <x-tally::shell.page title="New job work" section="Inventory" :description="$company->name">
        <form method="POST" class="panel" action="{{ tally_route('books.tally.job-works.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="issue_date" label="Issue date" required>
                    <x-tally::form.input type="date" name="issue_date" value="{{ old('issue_date', optional($job->issue_date)->toDateString() ?? now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="vendor_ledger_id" label="Vendor" required>
                    <select class="input" name="vendor_ledger_id" required>
                        <option value="">Select</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) old('vendor_ledger_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="product_id" label="Product">
                    <select class="input" name="product_id">
                        <option value="">—</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="issued_qty" label="Issued qty" required>
                    <x-tally::form.input name="issued_qty" value="{{ old('issued_qty') }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="job_charges" label="Job charges">
                    <x-tally::form.input name="job_charges" value="{{ old('job_charges', '0') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="narration" label="Narration" class="span-full">
                    <x-tally::form.input name="narration" value="{{ old('narration') }}" />
                </x-tally::form.field>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.job-works.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
