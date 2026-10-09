<x-tally::layouts.app :title="$transaction->number">
    <x-tally::shell.page :title="$transaction->number" section="Transactions" :description="$type->label().' · '.$transaction->transaction_date->format('d M Y')">
        <x-slot:actions>
            @if ($transaction->isPosted())
                <form method="POST" action="{{ tally_route($type->routeName('cancel'), $transaction) }}" onsubmit="return confirm('Cancel this stock transaction?');">
                    @csrf
                    <button class="btn" type="submit">Cancel</button>
                </form>
            @endif
            <a class="btn" href="{{ tally_route($type->routeName('index')) }}">Back</a>
        </x-slot:actions>
        @if ($errors->any())
            <p class="flash is-error" role="alert">{{ $errors->first() }}</p>
        @endif
        <div class="form-grid">
            <p><span class="muted">Status</span><br>{{ $transaction->status->label() }}</p>
            <p><span class="muted">Branch</span><br>{{ $transaction->branch->name ?? '—' }}</p>
            @if ($transaction->sourceGodown)
                <p><span class="muted">From</span><br>{{ $transaction->sourceGodown->name }}</p>
                <p><span class="muted">To</span><br>{{ $transaction->destinationGodown->name ?? '—' }}</p>
            @endif
            <p class="span-2"><span class="muted">Narration</span><br>{{ $transaction->narration ?: '—' }}</p>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Godown</th>
                        <th class="money">Quantity</th>
                        <th class="money">Rate</th>
                        <th class="money">Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transaction->lines as $line)
                        <tr>
                            <td>{{ $line->product->name ?? '—' }}</td>
                            <td>{{ $line->godown->name ?? '—' }}</td>
                            <td class="money">{{ rtrim(rtrim($line->quantity, '0'), '.') }}</td>
                            <td class="money">{{ $line->rate }}</td>
                            <td class="money">{{ $line->value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
