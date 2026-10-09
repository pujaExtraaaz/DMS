<x-tally::layouts.app title="TDS / TCS">
    <x-tally::shell.page title="TDS / TCS" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.deduction-sections.create') }}">New section</a>
        </x-slot:actions>
        <form class="filters" method="GET">
            <x-tally::form.field name="section_id" label="Section">
                <select id="section_id" name="section_id" class="input">
                    <option value="">Select</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" @selected((string) ($filters['section_id'] ?? '') === (string) $section->id)>{{ $section->section_code }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="amount" label="This transaction"><x-tally::form.input name="amount" value="{{ $filters['amount'] ?? '' }}" /></x-form.field>
            <x-tally::form.field name="year_to_date" label="Already considered"><x-tally::form.input name="year_to_date" value="{{ $filters['year_to_date'] ?? '' }}" /></x-form.field>
            <button class="btn" type="submit">Calculate</button>
        </form>
        @if ($preview)
            <p>Base {{ $preview->base }}. Deduction {{ $preview->deduction }} at {{ $preview->rate }}%. {{ $preview->thresholdCrossed ? 'Threshold crossed.' : 'Below the threshold.' }} This calculation is not posted.</p>
        @endif
        @if ($sections->isEmpty())
            <x-tally::ui.empty-state title="No sections" message="Add a TDS or TCS section. Rates stay on the section, not in the voucher screens." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Code</th><th>Name</th><th>Kind</th><th>Rate</th><th>Threshold</th><th>Parties</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($sections as $section)
                            <tr>
                                <td>{{ $section->section_code }}</td>
                                <td>{{ $section->name }}</td>
                                <td>{{ $section->kind->label() }}</td>
                                <td>{{ $section->rate }}%</td>
                                <td class="money">{{ $section->threshold_amount }}</td>
                                <td>{{ $section->party_role->label() }}</td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :edit="tally_route('books.tally.deduction-sections.edit', $section)"
                                        :active="$section->is_active"
                                        :toggle="tally_route('books.tally.deduction-sections.activation', $section)"
                                        :delete="tally_route('books.tally.deduction-sections.destroy', $section)"
                                        delete-reason="This section is used by a ledger and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $sections->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
