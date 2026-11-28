@extends('layouts.dms')
@section('title', $title)
@section('content')
@php
    $brandOptions = $brands->map(fn ($b) => ['id' => (string) $b->id, 'name' => $b->name])->values();
    $categoryOptions = $categories->map(fn ($c) => [
        'id' => (string) $c->id,
        'name' => $c->name,
        'brand_id' => $c->brand_id ? (string) $c->brand_id : '',
    ])->values();
    $productOptions = $products->map(fn ($p) => [
        'id' => (string) $p->id,
        'name' => $p->name,
        'brand_id' => $p->brand_id ? (string) $p->brand_id : '',
        'category_id' => $p->category_id ? (string) $p->category_id : '',
        'model' => $p->model,
    ])->values();
    $selectedBrandName = old('brand', $ticket->brand);
    $selectedBrandId = optional($brands->firstWhere('name', $selectedBrandName))->id;
    $selectedCategoryName = old('product_category', $ticket->product_category);
    $selectedCategoryId = optional($categories->firstWhere('name', $selectedCategoryName))->id;
@endphp
<div
    class="space-y-4"
    x-data="serviceTicketForm({
        requestType: @js(old('request_type', $ticket->request_type ?? 'complaint')),
        handlerType: @js(old('handler_type', $ticket->handler_type ?? 'avit')),
        brandId: @js(old('brand_id', $selectedBrandId ? (string) $selectedBrandId : '')),
        categoryId: @js(old('category_id', $selectedCategoryId ? (string) $selectedCategoryId : '')),
        productId: @js(old('product_id', $ticket->product_id ? (string) $ticket->product_id : '')),
        brandName: @js($selectedBrandName ?? ''),
        categoryName: @js($selectedCategoryName ?? ''),
        modelNumber: @js(old('model_number', $ticket->model_number ?? '')),
        brands: @js($brandOptions),
        categories: @js($categoryOptions),
        products: @js($productOptions),
        oemQuickUrl: @js(route('service.oem-centres.quick-store')),
        brandQuickUrl: @js(route('masters.brands.quick-store')),
        categoryQuickUrl: @js(route('masters.categories.quick-store')),
        csrf: @js(csrf_token()),
    })"
>
    <x-ui.page-header :title="$title">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="$ticket->exists ? route('service.tickets.show', $ticket) : route('service.tickets.index')">Back</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <form method="POST"
              action="{{ $ticket->exists ? route('service.tickets.update', $ticket) : route('service.tickets.store') }}"
              enctype="multipart/form-data"
              class="space-y-6 max-w-5xl">
            @csrf
            @if($ticket->exists) @method('PUT') @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 p-4 border border-red-200">
                    <ul class="list-disc list-inside text-xs text-red-700 space-y-1">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div>
                <h3 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-3">Request</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700">Request type <span class="text-red-500">*</span></label>
                            <button type="button" @click="createRequestType()" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">+ Create</button>
                        </div>
                        <select name="request_type" x-model="requestType" class="w-full rounded-lg border-slate-300 text-sm" required>
                            <template x-for="opt in requestTypeOptions" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label" :selected="requestType === opt.value"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Priority <span class="text-red-500">*</span></label>
                        <select name="priority" class="w-full rounded-lg border-slate-300 text-sm" required>
                            @foreach(['critical','high','normal','low'] as $p)
                                <option value="{{ $p }}" @selected(strtolower((string) old('priority', $ticket->priority ?? 'normal')) === $p)>{{ ucfirst($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700">Handler</label>
                            <button type="button" @click="createHandler()" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">+ Create</button>
                        </div>
                        <select name="handler_type" x-model="handlerType" class="w-full rounded-lg border-slate-300 text-sm">
                            <template x-for="opt in handlerOptions" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label" :selected="handlerType === opt.value"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700">OEM centre</label>
                            <button type="button" @click="createOem()" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">+ Create</button>
                        </div>
                        <select name="oem_centre_id" id="oem_centre_id" x-ref="oemSelect" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">—</option>
                            @foreach($oemCentres as $o)
                                <option value="{{ $o->id }}" @selected(old('oem_centre_id', $ticket->oem_centre_id) == $o->id)>{{ $o->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ui.input name="expected_visit_at" label="Expected visit" type="datetime-local" :value="old('expected_visit_at', $ticket->expected_visit_at ? $ticket->expected_visit_at->format('Y-m-d\TH:i') : '')" />
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-3">Party &amp; Contact</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Party type *</label>
                        <select name="party_type" class="w-full rounded-lg border-slate-300 text-sm" required>
                            @foreach(['dealer','end_user','company'] as $p)
                                <option value="{{ $p }}" @selected(old('party_type', $ticket->party_type ?? 'end_user') === $p)>{{ str_replace('_',' ',ucfirst($p)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Customer master</label>
                        <select name="customer_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">— Free text only —</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @selected(old('customer_id', $ticket->customer_id) == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ui.input name="customer_name" label="Customer / Contact name" :value="old('customer_name', $ticket->customer_name)" required />
                    <x-ui.input name="contact_person" label="Contact person" :value="old('contact_person', $ticket->contact_person)" />
                    <x-ui.input name="contact_mobile" label="Mobile" :value="old('contact_mobile', $ticket->contact_mobile)" />
                    <x-ui.input name="alt_contact_mobile" label="Alt mobile" :value="old('alt_contact_mobile', $ticket->alt_contact_mobile)" />
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Billing address</label>
                        <textarea name="billing_address" rows="2" class="w-full rounded-lg border-slate-300 text-sm">{{ old('billing_address', $ticket->billing_address) }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Site address</label>
                        <textarea name="site_address" rows="2" class="w-full rounded-lg border-slate-300 text-sm">{{ old('site_address', $ticket->site_address) }}</textarea>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-3">Product / Serial</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700">Brand</label>
                            <button type="button" @click="createBrand()" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">+ Create</button>
                        </div>
                        <select x-model="brandId" @change="onBrandChange()" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">— Select brand —</option>
                            <template x-for="b in brands" :key="b.id">
                                <option :value="b.id" x-text="b.name"></option>
                            </template>
                        </select>
                        <input type="hidden" name="brand" :value="brandName">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700">Category</label>
                            <button type="button" @click="createCategory()" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">+ Create</button>
                        </div>
                        <select x-model="categoryId" @change="onCategoryChange()" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">— Select category —</option>
                            <template x-for="c in filteredCategories" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                        <input type="hidden" name="product_category" :value="categoryName">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Model</label>
                        <input type="text" name="model_number" x-model="modelNumber" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Model">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product</label>
                        <select name="product_id" x-model="productId" @change="onProductChange()" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">—</option>
                            <template x-for="p in filteredProducts" :key="p.id">
                                <option :value="p.id" x-text="p.name"></option>
                            </template>
                        </select>
                    </div>
                    <x-ui.input name="serial_no" label="Serial no" :value="old('serial_no', $ticket->serial_no)" placeholder="Lookup fills warranty if known" />
                    <x-ui.input name="invoice_no" label="Invoice no" :value="old('invoice_no', $ticket->invoice_no)" />
                    <x-ui.input name="invoice_date" label="Invoice date" type="date" :value="old('invoice_date', optional($ticket->invoice_date)->format('Y-m-d'))" />
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Warranty *</label>
                        <select name="warranty_status" class="w-full rounded-lg border-slate-300 text-sm" required>
                            @foreach(['in_warranty','out_of_warranty','unknown'] as $w)
                                <option value="{{ $w }}" @selected(old('warranty_status', $ticket->warranty_status ?? 'unknown') === $w)>{{ str_replace('_',' ',ucfirst($w)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ui.input name="warranty_start" label="Warranty start" type="date" :value="old('warranty_start', optional($ticket->warranty_start)->format('Y-m-d'))" />
                    <x-ui.input name="warranty_expiry" label="Warranty expiry" type="date" :value="old('warranty_expiry', optional($ticket->warranty_expiry)->format('Y-m-d'))" />
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-3">Problem</h3>
                <textarea name="problem_description" rows="4" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Describe the issue / request…">{{ old('problem_description', $ticket->problem_description) }}</textarea>
                <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_paid_service" value="1" @checked(old('is_paid_service', $ticket->charges?->is_paid_service))>
                    Chargeable / paid service
                </label>
            </div>

            <div x-show="requestType === 'installation'" x-cloak>
                <h3 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-3">Installation</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Installation type</label>
                        <select name="installation_type" class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach(['free','chargeable','oem','avit_digital'] as $it)
                                <option value="{{ $it }}" @selected(old('installation_type', $ticket->installation?->installation_type ?? 'free') === $it)>{{ str_replace('_',' ',ucfirst($it)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ui.input name="project_name" label="Project name" :value="old('project_name', $ticket->installation?->project_name)" />
                    <x-ui.input name="quotation_amount" label="Quotation amount" type="number" step="0.01" :value="old('quotation_amount', $ticket->installation?->quotation_amount)" />
                    <x-ui.input name="installation_charges" label="Installation charges" type="number" step="0.01" :value="old('installation_charges', $ticket->installation?->installation_charges)" />
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Material consumed</label>
                        <textarea name="material_consumed" rows="2" class="w-full rounded-lg border-slate-300 text-sm">{{ old('material_consumed', $ticket->installation?->material_consumed) }}</textarea>
                    </div>
                </div>
            </div>

            @if(!$ticket->exists)
            <div>
                <h3 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-3">Attachments</h3>
                <input type="file" name="attachments[]" multiple class="text-sm">
            </div>
            @endif

            <div class="flex gap-2">
                <x-ui.button type="submit" variant="primary">{{ $ticket->exists ? 'Update Ticket' : 'Create Ticket' }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>

@push('scripts')
<script>
function serviceTicketForm(cfg) {
    return {
        requestType: cfg.requestType || 'complaint',
        handlerType: cfg.handlerType || 'avit',
        brandId: cfg.brandId || '',
        categoryId: cfg.categoryId || '',
        productId: cfg.productId || '',
        brandName: cfg.brandName || '',
        categoryName: cfg.categoryName || '',
        modelNumber: cfg.modelNumber || '',
        brands: cfg.brands || [],
        categories: cfg.categories || [],
        products: cfg.products || [],
        requestTypeOptions: [
            { value: 'complaint', label: 'Complaint' },
            { value: 'installation', label: 'Installation' },
            { value: 'service', label: 'Service' },
        ],
        handlerOptions: [
            { value: 'avit', label: 'Avit' },
            { value: 'oem', label: 'OEM' },
            { value: 'both', label: 'Both' },
        ],
        get filteredCategories() {
            if (!this.brandId) return this.categories;
            return this.categories.filter(c => !c.brand_id || c.brand_id === this.brandId);
        },
        get filteredProducts() {
            return this.products.filter(p => {
                if (this.brandId && p.brand_id && p.brand_id !== this.brandId) return false;
                if (this.categoryId && p.category_id && p.category_id !== this.categoryId) return false;
                return true;
            });
        },
        init() {
            const knownReq = this.requestTypeOptions.some(o => o.value === this.requestType);
            if (!knownReq && this.requestType) {
                this.requestTypeOptions.push({
                    value: this.requestType,
                    label: this.requestType.replace(/_/g, ' ').replace(/\b\w/g, m => m.toUpperCase()),
                });
            }
            const knownHandler = this.handlerOptions.some(o => o.value === this.handlerType);
            if (!knownHandler && this.handlerType) {
                this.handlerOptions.push({
                    value: this.handlerType,
                    label: this.handlerType.replace(/_/g, ' ').replace(/\b\w/g, m => m.toUpperCase()),
                });
            }
            this.$nextTick(() => this.refreshSearchables());
        },
        onBrandChange() {
            const b = this.brands.find(x => x.id === this.brandId);
            this.brandName = b ? b.name : '';
            this.categoryId = '';
            this.categoryName = '';
            this.productId = '';
            this.refreshSearchables();
        },
        onCategoryChange() {
            const c = this.filteredCategories.find(x => x.id === this.categoryId);
            this.categoryName = c ? c.name : '';
            this.productId = '';
            this.refreshSearchables();
        },
        onProductChange() {
            const p = this.products.find(x => x.id === this.productId);
            if (!p) return;
            if (p.model) this.modelNumber = p.model;
            if (p.brand_id) {
                this.brandId = p.brand_id;
                const b = this.brands.find(x => x.id === p.brand_id);
                this.brandName = b ? b.name : this.brandName;
            }
            if (p.category_id) {
                this.categoryId = p.category_id;
                const c = this.categories.find(x => x.id === p.category_id);
                this.categoryName = c ? c.name : this.categoryName;
            }
        },
        slugify(label) {
            return String(label || '').trim().toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '').slice(0, 40);
        },
        createRequestType() {
            const name = prompt('New request type name');
            if (!name || !name.trim()) return;
            const value = this.slugify(name) || 'custom';
            if (!this.requestTypeOptions.some(o => o.value === value)) {
                this.requestTypeOptions.push({ value, label: name.trim() });
            }
            this.requestType = value;
            this.refreshSearchables();
        },
        createHandler() {
            const name = prompt('New handler name');
            if (!name || !name.trim()) return;
            const value = this.slugify(name) || 'custom';
            if (!this.handlerOptions.some(o => o.value === value)) {
                this.handlerOptions.push({ value, label: name.trim() });
            }
            this.handlerType = value;
            this.refreshSearchables();
        },
        async createOem() {
            const name = prompt('OEM centre name');
            if (!name || !name.trim()) return;
            try {
                const res = await fetch(cfg.oemQuickUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                    },
                    body: JSON.stringify({ name: name.trim() }),
                });
                const data = await res.json();
                if (!data.success || !data.item) {
                    alert(data.message || 'Could not create OEM centre');
                    return;
                }
                const sel = this.$refs.oemSelect;
                let opt = Array.from(sel.options).find(o => o.value === String(data.item.id));
                if (!opt) {
                    opt = new Option(data.item.name, data.item.id, true, true);
                    sel.add(opt);
                }
                sel.value = String(data.item.id);
                sel.dispatchEvent(new Event('change', { bubbles: true }));
                this.refreshSearchables();
            } catch (e) {
                alert('Could not create OEM centre');
            }
        },
        async createBrand() {
            const name = prompt('Brand name');
            if (!name || !name.trim()) return;
            try {
                const res = await fetch(cfg.brandQuickUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                    },
                    body: JSON.stringify({ name: name.trim() }),
                });
                const data = await res.json();
                if (!data.success || !data.item) {
                    alert(data.message || 'Could not create brand');
                    return;
                }
                if (!this.brands.some(b => b.id === String(data.item.id))) {
                    this.brands.push({ id: String(data.item.id), name: data.item.name });
                }
                this.brandId = String(data.item.id);
                this.brandName = data.item.name;
                this.refreshSearchables();
            } catch (e) {
                alert('Could not create brand');
            }
        },
        async createCategory() {
            if (!this.brandId) {
                alert('Select a brand first');
                return;
            }
            const name = prompt('Category name');
            if (!name || !name.trim()) return;
            try {
                const res = await fetch(cfg.categoryQuickUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                    },
                    body: JSON.stringify({ name: name.trim(), brand_id: this.brandId }),
                });
                const data = await res.json();
                if (!data.success || !data.item) {
                    alert(data.message || 'Could not create category');
                    return;
                }
                if (!this.categories.some(c => c.id === String(data.item.id))) {
                    this.categories.push({
                        id: String(data.item.id),
                        name: data.item.name,
                        brand_id: String(data.item.brand_id || this.brandId),
                    });
                }
                this.categoryId = String(data.item.id);
                this.categoryName = data.item.name;
                this.refreshSearchables();
            } catch (e) {
                alert('Could not create category');
            }
        },
        refreshSearchables() {
            this.$nextTick(() => {
                if (window.DmsSearchableSelect?.enhanceAll) {
                    window.DmsSearchableSelect.enhanceAll(this.$el);
                }
                document.querySelectorAll('select[data-dms-searchable-initialized]').forEach((select) => {
                    select.dispatchEvent(new CustomEvent('dms:select-value-updated', { bubbles: false }));
                });
            });
        },
    };
}
</script>
@endpush
@endsection
