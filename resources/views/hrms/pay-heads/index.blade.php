@extends('layouts.dms')
@section('title', 'Payroll · Pay Heads')
@section('content')
<x-hrms.payroll-nav active="heads" />

<div id="listing-container" data-dynamic-container>
    <x-ui.card title="Pay Heads">
        <x-ui.table-toolbar
            placeholder="Search pay heads by name or code..."
            :searchValue="request('search')"
            :resetUrl="route('hrms.pay-heads.index')"
        />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="sort_order" :current-sort="$sort" :current-direction="$direction">#</x-ui.sortable-th>
                        <x-ui.sortable-th column="name" :current-sort="$sort" :current-direction="$direction">Name</x-ui.sortable-th>
                        <x-ui.sortable-th column="code" :current-sort="$sort" :current-direction="$direction">Code</x-ui.sortable-th>
                        <x-ui.sortable-th column="type" :current-sort="$sort" :current-direction="$direction">Type</x-ui.sortable-th>
                        <x-ui.sortable-th column="is_active" :current-sort="$sort" :current-direction="$direction">Status</x-ui.sortable-th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 text-slate-500">{{ $item->sort_order }}</td>
                            <td class="px-3 py-2 font-medium text-slate-900">
                                {{ $item->name }}
                                @if($item->is_system)
                                    <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">Standard</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-mono text-xs">{{ $item->code ?: '—' }}</td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="$item->type === 'earning' ? 'success' : 'warning'">{{ ucfirst($item->type) }}</x-ui.badge>
                            </td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="$item->is_active ? 'success' : 'secondary'">{{ $item->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <x-ui.button variant="secondary" size="sm" :href="route('hrms.pay-heads.edit', $item)">Edit</x-ui.button>
                                @unless($item->is_system)
                                    <form method="POST" action="{{ route('hrms.pay-heads.destroy', $item) }}" class="inline" onsubmit="return confirm('Delete this pay head?')">
                                        @csrf @method('DELETE')
                                        <x-ui.button type="submit" variant="danger" size="sm">Delete</x-ui.button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No pay heads found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
