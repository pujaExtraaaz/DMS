<x-tally::layouts.app title="Job work">
    <x-tally::shell.page title="Job work" section="Inventory" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.job-works.create') }}">New job work</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Number</th><th>Date</th><th>Vendor</th><th>Product</th><th class="money">Issued</th><th class="money">Received</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($jobs as $job)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.job-works.show', $job) }}">{{ $job->number }}</a></td>
                            <td>{{ $job->issue_date->format('d M Y') }}</td>
                            <td>{{ $job->vendor?->name }}</td>
                            <td>{{ $job->product?->name ?: '—' }}</td>
                            <td class="money">{{ $job->issued_qty }}</td>
                            <td class="money">{{ $job->received_qty }}</td>
                            <td>{{ ucfirst($job->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No job work yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $jobs->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
