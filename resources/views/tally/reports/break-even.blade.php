<x-tally::layouts.app title="Break-even / CVP">
    <x-tally::shell.page title="Break-even / CVP" section="Reports" :description="$company->name">
        <form method="GET" class="form-grid panel">
            <x-tally::form.field name="fixed_costs" label="Fixed costs" required>
                <x-tally::form.input name="fixed_costs" value="{{ old('fixed_costs', $input['fixed_costs'] ?? '') }}" />
            </x-tally::form.field>
            <x-tally::form.field name="price_per_unit" label="Price / unit">
                <x-tally::form.input name="price_per_unit" value="{{ old('price_per_unit', $input['price_per_unit'] ?? '') }}" />
            </x-tally::form.field>
            <x-tally::form.field name="variable_cost_per_unit" label="Variable cost / unit">
                <x-tally::form.input name="variable_cost_per_unit" value="{{ old('variable_cost_per_unit', $input['variable_cost_per_unit'] ?? '') }}" />
            </x-tally::form.field>
            <x-tally::form.field name="contribution_margin_percent" label="Or contribution margin %">
                <x-tally::form.input name="contribution_margin_percent" value="{{ old('contribution_margin_percent', $input['contribution_margin_percent'] ?? '') }}" />
            </x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Calculate</button></div>
        </form>
        @if ($result && $result['mode'] !== 'none')
            <dl class="kv" style="margin-top:1rem">
                <dt>Fixed costs</dt><dd>{{ $result['fixed_costs'] }}</dd>
                <dt>Contribution</dt><dd>{{ $result['contribution_per_unit'] }}</dd>
                <dt>Break-even units</dt><dd>{{ $result['break_even_units'] }}</dd>
                <dt>Break-even revenue</dt><dd>{{ $result['break_even_revenue'] }}</dd>
            </dl>
        @elseif ($result)
            <p class="form-note">Enter price &amp; variable cost per unit, or a contribution margin %.</p>
        @endif
    </x-tally::shell.page>
</x-tally::layouts.app>
