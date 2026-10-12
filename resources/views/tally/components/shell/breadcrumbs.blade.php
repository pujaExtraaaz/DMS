@props(['items'])
@if (count($items) > 1)
    <nav class="crumbs mb-1" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
            @foreach ($items as $item)
                <li class="inline-flex items-center gap-1.5">
                    @if (! empty($item['url']))
                        <a href="{{ $item['url'] }}" class="hover:text-indigo-600">{{ $item['label'] }}</a>
                        <span class="text-slate-300" aria-hidden="true">/</span>
                    @else
                        <span class="font-medium text-slate-700" aria-current="page">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
