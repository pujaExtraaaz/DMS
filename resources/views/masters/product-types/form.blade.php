@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Product Type' : 'Create Product Type')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <x-ui.page-header :title="$item->exists ? 'Edit Product Type' : 'Create Product Type'">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('masters.product-types.index')">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Product Types
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <form method="POST"
              action="{{ $item->exists ? route('masters.product-types.update', $item) : route('masters.product-types.store') }}"
              class="space-y-6"
              x-data="{
                  name: '{{ old('name', $item->name ?? '') }}',
                  toTitleCase(str) {
                      if (!str) return '';
                      return str.replace(/\b\w+/g, function(s) {
                          return s.charAt(0).toUpperCase() + s.substr(1).toLowerCase();
                      });
                  }
              }">
            @csrf
            @if($item->exists)
                @method('PUT')
            @endif

            {{-- Product Type Name --}}
            <div>
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Product Type Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    x-model="name"
                    @blur="name = toTitleCase(name)"
                    value="{{ old('name', $item->name ?? '') }}"
                    required
                    maxlength="100"
                    placeholder="e.g. Finished Goods, Spare Part, Hardware"
                    class="block w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs placeholder:text-slate-400 @error('name') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror"
                >
                <p class="mt-1 text-[11px] text-slate-500">
                    Names are automatically formatted to Title Case (e.g. Finished Goods, Spare Part).
                </p>
                @error('name')
                    <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Code (System Auto-generated preview) --}}
            <div>
                <label for="code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Code
                </label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    value="{{ old('code', $item->code ?? $previewCode ?? '') }}"
                    {{ $item->exists ? 'readonly' : '' }}
                    placeholder="Auto-generated on save"
                    class="block w-full rounded-xl border-slate-200 text-sm font-mono {{ $item->exists ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : 'focus:border-indigo-500 focus:ring-indigo-500' }} shadow-2xs"
                >
                <p class="mt-1 text-[11px] text-slate-500">
                    @if($item->exists)
                        System code is permanent and maintains historical references.
                    @else
                        Leave blank to automatically assign the next collision-safe sequence code (e.g. PT-GLOBAL-00001).
                    @endif
                </p>
                @error('code')
                    <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Description <span class="text-xs font-normal text-slate-400 lowercase">(optional)</span>
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    maxlength="1000"
                    placeholder="Enter short description or purpose for this product type..."
                    class="block w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-2xs placeholder:text-slate-400 @error('description') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror"
                >{{ old('description', $item->description ?? '') }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status --}}
            <div class="pt-2 border-t border-slate-100">
                <label class="inline-flex items-center gap-3 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $item->exists ? $item->is_active : true))
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                    >
                    <div>
                        <span class="text-sm font-semibold text-slate-800">Active</span>
                        <p class="text-xs text-slate-500">Active types can be assigned to new and existing products.</p>
                    </div>
                </label>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <x-ui.button variant="secondary" :href="route('masters.product-types.index')">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary">
                    {{ $item->exists ? 'Update Product Type' : 'Create Product Type' }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection

