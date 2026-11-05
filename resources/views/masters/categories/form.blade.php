@extends('layouts.dms')

@section('title', $item->exists ? 'Edit Category' : 'Create Category')

@section('content')
<div x-data="catalogQuickCreate({
    companies: @js($companies->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name])->values()),
    brands: @js($brands->map(fn ($b) => ['id' => (string) $b->id, 'name' => $b->name])->values()),
    brandQuickUrl: @js(route('masters.brands.quick-store')),
    categoryQuickUrl: @js(route('masters.categories.quick-store')),
    brandSelectId: 'brand_id',
})" @keydown.escape.window="closeTopCreate()">
<x-ui.page-header :title="$item->exists ? 'Edit Category' : 'Create Category'">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('masters.categories.index')">Back</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="POST"
          action="{{ $item->exists ? route('masters.categories.update', $item) : route('masters.categories.store') }}"
          @submit="submitCategoryForm($event)"
          class="space-y-4 max-w-xl">
        @csrf

        @if($item->exists)
            @method('PUT')
        @endif

        <div class="space-y-1">
            <div class="flex items-center justify-between">
                <label for="brand_id" class="block text-sm font-medium text-gray-700">Brand <span class="text-red-500">*</span></label>
                <button type="button" @click="openBrandCreate('page')" class="text-[11px] font-semibold text-indigo-700 hover:text-indigo-900">+ Create</button>
            </div>
            <select id="brand_id" name="brand_id" required @change="brandError = ''" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select</option>
                @foreach($brands as $b)
                    <option value="{{ $b->id }}" @selected(old('brand_id', $item->brand_id) == $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
            <p x-show="brandError" x-cloak class="text-xs text-red-600 font-medium" x-text="brandError"></p>
            @error('brand_id')
                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />

        <label class="flex items-center gap-2 text-sm">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(old('is_active', $item->is_active ?? true))
                class="rounded border-gray-300 text-indigo-600"
            >
            Active
        </label>

        <x-ui.button type="submit" variant="primary">Save</x-ui.button>
    </form>
</x-ui.card>
@include('masters.partials.catalog-quick-modals')
</div>
@endsection

@push('scripts')
<script>
function catalogQuickCreate(config) {
    return {
        companies: config.companies || [],
        brands: config.brands || [],
        categories: config.categories || [],
        brandQuickUrl: config.brandQuickUrl,
        categoryQuickUrl: config.categoryQuickUrl,
        brandSelectId: config.brandSelectId || '',
        categorySelectId: config.categorySelectId || '',
        brandError: '',
        brandCreateOpen: false,
        brandCreateContext: 'page',
        brandCreateName: '',
        brandCreateDetail: '',
        brandCreateCompanyId: '',
        brandCreateActive: true,
        brandCreateError: '',
        brandCreateSaving: false,
        categoryCreateOpen: false,
        categoryCreateContext: 'page',
        categoryCreateName: '',
        categoryCreateBrandId: '',
        categoryCreateActive: true,
        categoryCreateError: '',
        categoryCreateSaving: false,
        closeTopCreate() {
            if (this.brandCreateOpen) { this.brandCreateOpen = false; return; }
            if (this.categoryCreateOpen) { this.categoryCreateOpen = false; }
        },
        submitCategoryForm(event) {
            const brandSelect = document.getElementById(this.brandSelectId || 'brand_id');
            const val = brandSelect ? brandSelect.value.trim() : '';
            if (!val) {
                if (event) {
                    event.preventDefault();
                }
                this.brandError = 'Please select a brand before creating a category.';
                if (brandSelect) {
                    brandSelect.focus();
                    if (typeof brandSelect.scrollIntoView === 'function') {
                        brandSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
                return false;
            }
            this.brandError = '';
            return true;
        },
        fillSelect(selectId, items, selected) {
            const select = document.getElementById(selectId);
            if (!select) return;
            select.replaceChildren();
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select';
            select.appendChild(placeholder);
            (items || []).forEach((item) => {
                const option = document.createElement('option');
                option.value = String(item.id);
                option.textContent = item.name;
                select.appendChild(option);
            });
            select.value = selected ? String(selected) : '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
        },
        addOption(selectId, id, name) {
            if (!selectId) return;
            const select = document.getElementById(selectId);
            if (!select) return;
            let option = Array.from(select.options).find((opt) => String(opt.value) === String(id));
            if (!option) {
                option = document.createElement('option');
                option.value = String(id);
                option.textContent = name;
                select.appendChild(option);
            }
            select.value = String(id);
            if (selectId === this.brandSelectId || selectId === 'brand_id') {
                this.brandError = '';
            }
            select.dispatchEvent(new Event('change', { bubbles: true }));
        },
        async postJson(url, payload) {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            return { ok: response.ok, data };
        },
        openBrandCreate(context) {
            this.brandCreateContext = context || 'page';
            this.brandCreateOpen = true;
            this.brandCreateError = '';
            this.brandCreateName = '';
            this.brandCreateDetail = '';
            this.brandCreateActive = true;
            this.brandCreateCompanyId = this.companies[0] ? String(this.companies[0].id) : '';
            this.fillSelect('catalog_brand_company', this.companies, this.brandCreateCompanyId);
        },
        async saveBrand() {
            const name = (this.brandCreateName || '').trim();
            if (!name) {
                this.brandCreateError = 'Brand Name is required.';
                return;
            }
            this.brandCreateSaving = true;
            this.brandCreateError = '';
            const result = await this.postJson(this.brandQuickUrl, {
                name,
                detail: this.brandCreateDetail || '',
                company_id: this.brandCreateCompanyId || null,
                is_active: !!this.brandCreateActive,
            });
            this.brandCreateSaving = false;
            if (!result.ok || !result.data.success) {
                this.brandCreateError = result.data.message || 'Could not create the brand.';
                return;
            }
            const item = result.data.item;
            if (!this.brands.some((brand) => String(brand.id) === String(item.id))) {
                this.brands.push(item);
            }
            this.addOption(this.brandSelectId, item.id, item.name);
            this.categoryCreateBrandId = String(item.id);
            this.fillSelect('catalog_category_brand', this.brands, this.categoryCreateBrandId);
            this.brandCreateOpen = false;
        },
        openCategoryCreate(context) {
            this.categoryCreateContext = context || 'page';
            this.categoryCreateOpen = true;
            this.categoryCreateError = '';
            this.categoryCreateName = '';
            this.categoryCreateActive = true;
            const pageBrand = this.brandSelectId ? document.getElementById(this.brandSelectId) : null;
            this.categoryCreateBrandId = pageBrand && pageBrand.value ? String(pageBrand.value) : '';
            this.fillSelect('catalog_category_brand', this.brands, this.categoryCreateBrandId);
        },
        async saveCategory() {
            if (!this.categoryCreateBrandId) {
                this.categoryCreateError = 'Please select a brand before creating a category.';
                const brandSelect = document.getElementById('catalog_category_brand');
                if (brandSelect) {
                    brandSelect.focus();
                    if (typeof brandSelect.scrollIntoView === 'function') {
                        brandSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
                return;
            }
            const name = (this.categoryCreateName || '').trim();
            if (!name) {
                this.categoryCreateError = 'Name is required.';
                return;
            }
            this.categoryCreateSaving = true;
            this.categoryCreateError = '';
            const result = await this.postJson(this.categoryQuickUrl, {
                name,
                brand_id: this.categoryCreateBrandId || null,
                is_active: !!this.categoryCreateActive,
            });
            this.categoryCreateSaving = false;
            if (!result.ok || !result.data.success) {
                this.categoryCreateError = result.data.message || 'Could not create the category.';
                return;
            }
            const item = result.data.item;
            if (!this.categories.some((category) => String(category.id) === String(item.id))) {
                this.categories.push(item);
            }
            this.addOption(this.categorySelectId, item.id, item.name);
            this.categoryCreateName = '';
            this.categoryCreateOpen = false;
        },
    };
}
</script>
@endpush