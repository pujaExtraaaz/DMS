@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Targets' : 'New Targets')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Targets' : 'New Targets'">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="$item->exists ? route('targets.periods.show', $item) : route('targets.index')">Back</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <form
        method="POST"
        action="{{ $item->exists ? route('targets.periods.update', $item) : route('targets.periods.store') }}"
        class="space-y-4 max-w-xl"
        x-data="{
            category: '{{ old('target_category', $item->target_category ?? '') }}',
            brandId: '{{ old('brand_id', $item->brand_id ?? '') }}',
            productId: '{{ old('product_id', $item->product_id ?? '') }}',
            supplierId: '{{ old('supplier_id', $item->supplier_id ?? '') }}',
            isCategory(cat) {
                return this.category === cat || this.category === cat + 's';
            },
            onCategoryChange(newCat) {
                this.category = newCat;
                if (newCat !== 'brand' && newCat !== 'brands') this.brandId = '';
                if (newCat !== 'product' && newCat !== 'products') this.productId = '';
                if (newCat !== 'supplier' && newCat !== 'suppliers') this.supplierId = '';
            }
        }"
    >
        @csrf
        @if($item->exists)
            @method('PUT')
        @endif

        <x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />

        <x-ui.select name="period_type" label="Type" required>
            @foreach(['monthly', 'quarterly', 'annual'] as $t)
                <option value="{{ $t }}" @selected(old('period_type', $item->period_type) == $t)>{{ ucfirst($t) }}</option>
            @endforeach
        </x-ui.select>

        {{-- Single Target Category Dropdown --}}
        <div>
            <x-ui.select
                name="target_category"
                label="Target Category"
                placeholder="Select Target Category"
                required
                x-model="category"
                @change="onCategoryChange($event.target.value)"
            >
                <option value="brand" @selected(in_array(old('target_category', $item->target_category), ['brand', 'brands']))>Brands</option>
                <option value="product" @selected(in_array(old('target_category', $item->target_category), ['product', 'products']))>Products</option>
                <option value="supplier" @selected(in_array(old('target_category', $item->target_category), ['supplier', 'suppliers']))>Suppliers</option>
            </x-ui.select>
        </div>

        {{-- Category-Specific Dynamic Selection Dropdowns --}}
        <div x-show="isCategory('brand')" x-cloak class="space-y-1">
            <x-ui.select
                name="brand_id"
                label="Select Brand"
                placeholder="Select Brand"
                x-model="brandId"
                x-bind:disabled="!isCategory('brand')"
            >
                @foreach($brands as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </x-ui.select>
            @error('brand_id')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div x-show="isCategory('product')" x-cloak class="space-y-1">
            <x-ui.select
                name="product_id"
                label="Select Product"
                placeholder="Select Product"
                x-model="productId"
                x-bind:disabled="!isCategory('product')"
            >
                @foreach($products as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}{{ $p->sku ? ' (' . $p->sku . ')' : '' }}</option>
                @endforeach
            </x-ui.select>
            @error('product_id')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div x-show="isCategory('supplier')" x-cloak class="space-y-1">
            <x-ui.select
                name="supplier_id"
                label="Select Supplier"
                placeholder="Select Supplier"
                x-model="supplierId"
                x-bind:disabled="!isCategory('supplier')"
            >
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}{{ $s->code ? ' (' . $s->code . ')' : '' }}</option>
                @endforeach
            </x-ui.select>
            @error('supplier_id')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
            <x-ui.input name="starts_on" type="date" label="Starts" :value="old('starts_on', optional($item->starts_on)->format('Y-m-d') ?? $item->starts_on)" required />
            <x-ui.input name="ends_on" type="date" label="Ends" :value="old('ends_on', optional($item->ends_on)->format('Y-m-d') ?? $item->ends_on)" required />
        </div>

        <div class="pt-2">
            <x-ui.button type="submit" variant="primary">Save</x-ui.button>
        </div>
    </form>
</x-ui.card>
@endsection
