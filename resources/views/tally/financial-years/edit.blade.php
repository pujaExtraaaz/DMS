<x-tally::layouts.app :title="'Edit '.$financialYear->name">
    <x-tally::shell.page :title="'Edit '.$financialYear->name" section="Settings" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.companies.financial-years.update', [$company, $financialYear]) }}">
            @csrf
            @method('PUT')
            @include('tally::financial-years._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save financial year</button>
                <a class="btn" href="{{ tally_route('books.tally.companies.financial-years.show', [$company, $financialYear]) }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
