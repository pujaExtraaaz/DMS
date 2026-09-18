@extends('layouts.dms')
@section('title', 'Leave Types')
@section('content')
<x-ui.page-header title="Leave Types"><x-slot name="actions"><x-ui.button variant="primary" :href="route('hrms.leave-types.create')">Add</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Name</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Days</th><th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Paid</th><th></th></tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $item)
<tr><td class="px-3 py-2 font-medium">{{ $item->name }}</td><td class="px-3 py-2">{{ $item->default_days }}</td><td class="px-3 py-2">{{ $item->is_paid ? 'Yes' : 'No' }}</td>
<td class="px-3 py-2 text-right"><x-ui.button variant="secondary" size="sm" :href="route('hrms.leave-types.edit', $item)">Edit</x-ui.button></td></tr>
@empty<tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">No records found.</td></tr>@endforelse
</tbody></table></div><div class="mt-4">{{ $items->links() }}</div></x-ui.card>
@endsection
