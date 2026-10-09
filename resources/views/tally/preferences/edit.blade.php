<x-tally::layouts.app title="Preferences">
    <x-tally::shell.page title="Preferences" section="Settings" :description="$company?->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.preferences.update') }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <x-tally::form.field name="display_date_format" label="Date format">
                    <select id="display_date_format" name="display_date_format" class="input">
                        @foreach (['d M Y' => '01 Oct 2026', 'd/m/Y' => '01/10/2026', 'Y-m-d' => '2026-10-01'] as $format => $sample)
                            <option value="{{ $format }}" @selected($values['display.date_format'] === $format)>{{ $sample }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="dashboard_as_on" label="Dashboard as-on date">
                    <x-tally::form.input name="dashboard_as_on" type="date" value="{{ $values['dashboard.as_on'] }}" />
                </x-form.field>
            </div>
            <label class="check"><input type="checkbox" name="sales_enforce_credit_limit" value="1" @checked($values['sales.enforce_credit_limit'])> Stop a sales invoice that would exceed the customer credit limit</label>
            <label class="check"><input type="checkbox" name="inventory_repeat_scan_increments" value="1" @checked($values['inventory.repeat_scan_increments'])> Scanning the same barcode again increases quantity</label>
            <label class="check"><input type="checkbox" name="print_amount_in_words" value="1" @checked($values['print.amount_in_words'])> Show the amount in words on printed documents</label>
            <p class="form-note">Date format and the dashboard date follow the signed-in user. Credit limit, scanning, and amount in words follow the working company. Currency amounts stay in rupees.</p>
            <button class="btn btn-primary" type="submit">Save preferences</button>
        </form>
    </x-shell.page>
</x-layouts.app>
