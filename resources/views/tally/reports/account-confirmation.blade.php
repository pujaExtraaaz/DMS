<x-tally::layouts.app title="Account confirmation">
    <x-tally::shell.page title="Account confirmation" section="Reports" :description="$company->name">
        <div class="panel">
            <p>To: <strong>{{ $statement['party']->name }}</strong></p>
            <p>As on {{ \Illuminate\Support\Carbon::parse($asOn)->format('d M Y') }}, the balance in your account stands at <strong>{{ $statement['closing'] }}</strong>.</p>
            <p>Please confirm whether this balance is acceptable.</p>
            <div class="form-grid" style="margin-top:1.5rem">
                <label class="check"><input type="checkbox" disabled> Accepted</label>
                <label class="check"><input type="checkbox" disabled> Disputed</label>
            </div>
            <p class="muted" style="margin-top:1rem">Opening {{ $statement['opening'] }} · Closing {{ $statement['closing'] }}</p>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
