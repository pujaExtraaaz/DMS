@php
    $selectedSupplier = (string) ($selected ?? '');
@endphp
<select name="supplier_id" id="supplier_id" required
        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
        onchange="handleSupplierChange(this.value)">
    <option value="">Select supplier</option>
    @foreach($suppliers as $s)
        <option value="{{ $s->id }}" data-credit-days="{{ $s->credit_days ?? 0 }}" @selected($selectedSupplier === (string) $s->id)>{{ $s->name }}</option>
    @endforeach
</select>
