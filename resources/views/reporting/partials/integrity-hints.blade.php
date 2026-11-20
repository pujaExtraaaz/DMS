@if(!empty($integrity ?? []))
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <div class="font-semibold mb-1">Report integrity</div>
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach($integrity as $hint)
                <li>{{ $hint }}</li>
            @endforeach
        </ul>
    </div>
@endif
