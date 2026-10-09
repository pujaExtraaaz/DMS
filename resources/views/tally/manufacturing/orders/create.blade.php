<x-tally::layouts.app title="Manufacturing journal">
    <x-tally::shell.page title="Manufacturing journal" section="Transactions" :description="$company->name.' · '.$year->name">
        <form method="POST" action="{{ tally_route('books.tally.manufacturing.store') }}" class="stack tally-vch">
            <header class="tally-vch-bar">
                <strong>Inventory Voucher Creation</strong>
                <span>{{ $company->name }}</span>
            </header>
            <div class="tally-vch-badge">Manufacturing Journal</div>
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="manufactured_on" label="Date" required>
                    <x-tally::form.input name="manufactured_on" type="date" value="{{ old('manufactured_on', $year->start_date->toDateString()) }}" required />
                </x-form.field>
                <x-tally::form.field name="bill_of_material_id" label="Bill of materials" required>
                    <select id="bill_of_material_id" name="bill_of_material_id" class="input" required>
                        <option value="">Select BOM</option>
                        @foreach ($boms as $bom)
                            <option value="{{ $bom->id }}" @selected((string) old('bill_of_material_id') === (string) $bom->id)>{{ $bom->name }} — {{ $bom->finishedProduct?->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="quantity" label="Production quantity" required>
                    <x-tally::form.input name="quantity" value="{{ old('quantity') }}" required />
                </x-form.field>
                <x-tally::form.field name="source_godown_id" label="Consume from" required>
                    <select id="source_godown_id" name="source_godown_id" class="input" required>
                        @foreach ($godowns as $godown)
                            <option value="{{ $godown->id }}" @selected((string) old('source_godown_id') === (string) $godown->id)>{{ $godown->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="destination_godown_id" label="Receive into" required>
                    <select id="destination_godown_id" name="destination_godown_id" class="input" required>
                        @foreach ($godowns as $godown)
                            <option value="{{ $godown->id }}" @selected((string) old('destination_godown_id') === (string) $godown->id)>{{ $godown->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="finished_ledger_id" label="Finished goods ledger">
                    <select id="finished_ledger_id" name="finished_ledger_id" class="input">
                        <option value="">Use stock ledgers</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" @selected((string) old('finished_ledger_id') === (string) $ledger->id)>{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="raw_ledger_id" label="Raw material ledger">
                    <select id="raw_ledger_id" name="raw_ledger_id" class="input">
                        <option value="">Use stock ledgers</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" @selected((string) old('raw_ledger_id') === (string) $ledger->id)>{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="narration" label="Narration" class="span-2">
                    <x-tally::form.input name="narration" value="{{ old('narration') }}" />
                </x-form.field>
            </div>
            @if ($tracked->isNotEmpty())
                <h2>Batches</h2>
                <p>Required for products that track batches.</p>
                @foreach ($tracked as $product)
                    <x-tally::form.field name="batches_{{ $product->id }}" :label="$product->name.' batch'">
                        <x-tally::form.input name="batches[{{ $product->id }}]" value="{{ old('batches.'.$product->id) }}" />
                    </x-form.field>
                @endforeach
            @endif
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Post manufacturing</button>
                <a class="btn" data-esc href="{{ tally_route('books.tally.manufacturing.index') }}">Q: Quit</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
