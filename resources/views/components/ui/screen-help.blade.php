@php
    use App\Support\ScreenHelp;
    $help = $help ?? ScreenHelp::forRoute(request()->route()?->getName());
@endphp

@if($help)
<div
    x-data="{ open: localStorage.getItem('dmsHelpOpen') === 'true' }"
    x-init="$watch('open', v => localStorage.setItem('dmsHelpOpen', v ? 'true' : 'false'))"
    class="rounded-xl border border-slate-200 bg-white shadow-sm"
>
    <button
        type="button"
        @click="open = !open"
        class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left hover:bg-slate-50 rounded-xl"
    >
        <div class="flex min-w-0 items-center gap-2.5">
            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-teal-600 text-white text-xs font-bold">?</span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-800 truncate">How to use: {{ $help['title'] ?? 'this screen' }}</p>
                <p class="truncate text-xs text-slate-500" x-show="!open">{{ $help['summary'] ?? 'Tap to see steps' }}</p>
            </div>
        </div>
        <span class="shrink-0 text-xs font-medium text-teal-700" x-text="open ? 'Hide' : 'Show'"></span>
    </button>

    <div x-show="open" x-cloak class="border-t border-slate-100 px-3 py-3">
        <p class="mb-2 text-xs text-slate-600">{{ $help['summary'] ?? '' }}</p>
        @if(!empty($help['steps']))
            <ol class="list-decimal space-y-1 pl-4 text-sm text-slate-700">
                @foreach($help['steps'] as $step)
                    <li>{{ $step }}</li>
                @endforeach
            </ol>
        @endif
        <p class="mt-2 text-xs text-slate-500">
            <a href="{{ url('/docs/CLIENT_REQUIREMENTS.html') }}" target="_blank" class="font-medium text-teal-700 hover:underline">Product Guide</a>
            ·
            <a href="{{ url('/docs/CLIENT_CREDENTIALS.html') }}" target="_blank" class="font-medium text-teal-700 hover:underline">Client Login</a>
        </p>
    </div>
</div>
@endif
