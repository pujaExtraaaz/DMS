<x-tally::layouts.app :title="'New '.$type->label()">
    <x-tally::shell.page :title="'New '.$type->label()" section="Transactions" :description="$company->name.' · '.$branch->code.' · '.$year->name">
        <form class="panel tally-vch" method="POST" action="{{ tally_route($type->routeName('store')) }}">
            <header class="tally-vch-bar">
                <strong>Inventory Voucher Creation</strong>
                <span>{{ $company->name }}</span>
            </header>
            <div class="tally-vch-badge">{{ $type->label() }}</div>
            @csrf
            @include('tally::stock._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Post {{ strtolower($type->label()) }}</button>
                <a class="btn" data-esc href="{{ tally_route($type->routeName('index')) }}">Q: Quit</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
