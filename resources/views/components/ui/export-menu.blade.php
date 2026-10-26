@props(['label' => 'Export'])

<div class="relative inline-block text-left" x-data="{ open: false }">
    <button
        type="button"
        @click="open = !open"
        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none"
    >
        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>{{ $label }}</span>
        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div
        x-show="open"
        x-cloak
        @click.away="open = false"
        class="absolute right-0 z-20 mt-1 w-40 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
    >
        <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="flex items-center px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">Excel (.xlsx)</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="flex items-center px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="flex items-center px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">PDF</a>
    </div>
</div>