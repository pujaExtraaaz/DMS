@php
    $selectedSupplier = (string) ($selected ?? '');
@endphp
<select name="supplier_id" id="supplier_id" onchange="handleSupplierChange(this.value)" required
        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    <option value="">Select supplier</option>
    @foreach($suppliers as $s)
        @php
            $sPhone = $s->phone ?? ($s->mobile ?? '');
        @endphp
        <option value="{{ $s->id }}"
                data-credit-days="{{ $s->credit_days ?? 0 }}"
                @if(!empty($sPhone)) data-code="{{ $sPhone }}" data-subtext="{{ $sPhone }}" @elseif(!empty($s->code)) data-code="{{ $s->code }}" @endif
                @selected($selectedSupplier === (string) $s->id)>
            {{ $s->name }}
        </option>
    @endforeach
</select>
