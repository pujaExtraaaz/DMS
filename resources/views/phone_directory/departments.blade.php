@extends('layouts.dms')
@section('title', 'Manage Departments & Categories - Phone Directory')

@section('content')
@include('phone_directory._styles')
<div class="max-w-6xl mx-auto space-y-6" x-data="deptCategoryManager()">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ route('phone-directory.index') }}" class="hover:text-violet-600 transition">Phone Directory</a>
            <span>/</span>
            <span class="font-medium text-slate-900">Departments & Categories Master</span>
        </div>

        <a href="{{ route('phone-directory.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            <span>Back to Directory</span>
        </a>
    </div>

    {{-- Tabs or Dual Cards Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- DEPARTMENTS CARD --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50/70 px-5 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Departments Master</h2>
                    <p class="text-[11px] text-slate-500">Organize contacts by corporate departments.</p>
                </div>

                <button
                    type="button"
                    @click="addDeptModal = true"
                    class="btn-pd-primary inline-flex items-center justify-center gap-1 rounded-xl bg-purple-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-purple-700 active:bg-purple-800 transition min-h-[34px]"
                >
                    <svg class="h-3.5 w-3.5 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    <span>New Department</span>
                </button>
            </div>

            <div class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
                @forelse($departments as $dept)
                    <div class="p-3.5 flex items-center justify-between hover:bg-slate-50/50 transition">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-xs text-slate-900">{{ $dept->name }}</span>
                                @if($dept->code)
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-mono text-slate-600">{{ $dept->code }}</span>
                                @endif
                                <span class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-medium text-violet-700 border border-violet-100">
                                    {{ $dept->contacts_count }} linked
                                </span>
                            </div>
                            @if($dept->description)
                                <p class="text-[11px] text-slate-500 mt-0.5">{{ $dept->description }}</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-1">
                            @if($dept->contacts_count === 0)
                                <form method="POST" action="{{ route('phone-directory.departments.destroy', $dept) }}" onsubmit="return confirm('Delete department {{ $dept->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition" title="Delete department">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-slate-500">No departments found.</div>
                @endforelse
            </div>
        </div>

        {{-- CATEGORIES CARD --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50/70 px-5 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Categories Master</h2>
                    <p class="text-[11px] text-slate-500">Tag contacts with priorities or categories.</p>
                </div>

                <button
                    type="button"
                    @click="addCatModal = true"
                    class="btn-pd-primary inline-flex items-center justify-center gap-1 rounded-xl bg-purple-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-purple-700 active:bg-purple-800 transition min-h-[34px]"
                >
                    <svg class="h-3.5 w-3.5 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    <span>New Category</span>
                </button>
            </div>

            <div class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
                @forelse($categories as $cat)
                    <div class="p-3.5 flex items-center justify-between hover:bg-slate-50/50 transition">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-xs text-slate-900">{{ $cat->name }}</span>
                                <span class="rounded-full bg-purple-50 px-2 py-0.5 text-[10px] font-medium text-purple-700 border border-purple-100">
                                    {{ $cat->contacts_count }} linked
                                </span>
                            </div>
                            @if($cat->description)
                                <p class="text-[11px] text-slate-500 mt-0.5">{{ $cat->description }}</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-1">
                            @if($cat->contacts_count === 0)
                                <form method="POST" action="{{ route('phone-directory.categories.destroy', $cat) }}" onsubmit="return confirm('Delete category {{ $cat->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition" title="Delete category">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-slate-500">No categories found.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Add Department Modal --}}
    <div x-show="addDeptModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-2xs">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl border border-slate-200 p-5 space-y-4" @click.outside="addDeptModal = false">
            <h3 class="text-sm font-bold text-slate-900">Add New Department</h3>
            <form method="POST" action="{{ route('phone-directory.departments.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Department Name *</label>
                    <input type="text" name="name" required class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Code (Optional)</label>
                    <input type="text" name="code" placeholder="e.g. FIN, MKT" class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description (Optional)</label>
                    <textarea name="description" rows="2" class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500"></textarea>
                </div>
                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="addDeptModal = false" class="btn-pd-secondary rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-900 shadow-xs hover:bg-slate-50 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/20">Cancel</button>
                    <button type="submit" class="btn-pd-primary rounded-xl bg-purple-600 px-4 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-purple-700 active:bg-purple-800">Save Department</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Category Modal --}}
    <div x-show="addCatModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-2xs">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl border border-slate-200 p-5 space-y-4" @click.outside="addCatModal = false">
            <h3 class="text-sm font-bold text-slate-900">Add New Category</h3>
            <form method="POST" action="{{ route('phone-directory.categories.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Category Name *</label>
                    <input type="text" name="name" required class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description (Optional)</label>
                    <textarea name="description" rows="2" class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500"></textarea>
                </div>
                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="addCatModal = false" class="btn-pd-secondary rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-900 shadow-xs hover:bg-slate-50 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/20">Cancel</button>
                    <button type="submit" class="btn-pd-primary rounded-xl bg-purple-600 px-4 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-purple-700 active:bg-purple-800">Save Category</button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function deptCategoryManager() {
    return {
        addDeptModal: false,
        addCatModal: false,
    };
}
</script>
@endpush
@endsection
