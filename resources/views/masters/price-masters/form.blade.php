@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Price' : 'Add Price')
@section('content')
@php
    $isEdit = $item->exists;
    $productOptions = $products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'sku' => $p->sku,
    ])->values();
    $uomOptions = $uoms->map(fn ($u) => [
        'id' => $u->id,
        'code' => $u->code,
        'name' => $u->name,
    ])->values();
    $oldLines = old('lines');
    if (is_array($oldLines) && count($oldLines)) {
        $initialLines = collect($oldLines)->map(function ($row) {
            return [
                'product_id' => (string) ($row['product_id'] ?? ''),
                'uom_id' => (string) ($row['uom_id'] ?? ''),
                'rate' => $row['rate'] ?? '',
                'min_qty' => $row['min_qty'] ?? '',
                'searchQuery' => '',
                'isOpen' => false,
            ];
        })->values()->all();
    } else {
        $initialLines = [[
            'product_id' => '',
            'uom_id' => '',
            'rate' => '',
            'min_qty' => '',
            'searchQuery' => '',
            'isOpen' => false,
        ]];
    }

    $serverLineErrors = collect($errors->getMessages())
        ->filter(function ($messages, $key) {
            return str_starts_with($key, 'lines.');
        })
        ->mapWithKeys(function ($messages, $key) {
            $parts = explode('.', $key);
            if (count($parts) < 3) {
                return [];
            }

            return [$parts[1].'.'.$parts[2] => $messages[0] ?? ''];
        })
        ->all();
@endphp

<x-ui.page-header :title="$isEdit ? 'Edit Price' : 'Add Price'">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('masters.price-masters.index')">Back</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <div x-data="priceMasterForm({
        isEdit: {{ $isEdit ? 'true' : 'false' }},
        customerTypeId: {{ json_encode((string) old('customer_type_id', $item->customer_type_id ?? '')) }},
        knownTypeNames: {{ json_encode($customerTypes->pluck('name')->map(fn ($n) => mb_strtolower(trim($n)))->values()) }},
        products: {{ json_encode($productOptions) }},
        uoms: {{ json_encode($uomOptions) }},
        lines: {{ json_encode($initialLines) }},
        editProductId: {{ json_encode((string) old('product_id', $item->product_id ?? '')) }},
        editUomId: {{ json_encode((string) old('uom_id', $item->uom_id ?? '')) }},
        editRate: {{ json_encode(old('rate', $item->rate ?? '')) }},
        editMinQty: {{ json_encode(old('min_qty', $item->min_qty ?? '')) }},
    })" class="space-y-4 {{ $isEdit ? 'max-w-xl' : 'max-w-4xl' }}">

        <form method="POST"
              action="{{ $isEdit ? route('masters.price-masters.update', $item) : route('masters.price-masters.store') }}"
              class="space-y-4">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            {{-- Customer Type (+ create) --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="customer_type_id" class="block text-sm font-medium text-gray-700">Customer Type</label>
                    <button type="button"
                            @click="openTypeModal()"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1 hover:underline focus:outline-none">
                        + Create
                    </button>
                </div>
                <select name="customer_type_id"
                        id="customer_type_id"
                        x-ref="typeSelect"
                        x-model="selectedCustomerTypeId"
                        @change="previousCustomerTypeId = selectedCustomerTypeId"
                        required
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select</option>
                    @foreach($customerTypes as $ct)
                        <option value="{{ $ct->id }}">{{ $ct->name }}</option>
                    @endforeach
                    <template x-for="t in customTypes" :key="t.id">
                        <option :value="t.id" x-text="t.name"></option>
                    </template>
                </select>
                @error('customer_type_id')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            @if($isEdit)
                {{-- Single-row edit --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                    <div class="relative" @click.outside="editProductOpen = false">
                        <input type="hidden" name="product_id" :value="editProductId" required>
                        <input type="text"
                               x-model="editProductQuery"
                               @focus="editProductOpen = true"
                               @input="editProductOpen = true; editProductId = ''"
                               placeholder="Search product name or SKU..."
                               autocomplete="off"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8">
                        <button type="button" x-show="editProductId" @click="clearEditProduct()"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 font-bold">&times;</button>
                        <div x-show="editProductOpen && filteredEditProducts.length > 0"
                             x-cloak
                             class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100">
                            <template x-for="prod in filteredEditProducts" :key="prod.id">
                                <div @click="selectEditProduct(prod)"
                                     class="p-2.5 hover:bg-indigo-50 cursor-pointer text-xs flex items-center justify-between">
                                    <span class="font-medium text-slate-800" x-text="prod.name"></span>
                                    <span class="font-mono text-[11px] text-slate-500" x-text="prod.sku"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    @error('product_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">UOM</label>
                    <select name="uom_id" x-model="editUomId" required
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select</option>
                        <template x-for="u in uoms" :key="u.id">
                            <option :value="String(u.id)" x-text="u.code"></option>
                        </template>
                    </select>
                    @error('uom_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rate</label>
                    <input type="number" step="0.01" name="rate" x-model="editRate" required
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('rate')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min Qty</label>
                    <input type="number" step="0.01" name="min_qty" x-model="editMinQty"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('min_qty')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            @else
                {{-- Multi-product create --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700">Products</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Add one or more products with UOM, rate, and min qty.</p>
                        </div>
                        <button type="button" @click="addLine()"
                                class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shrink-0">
                            + Add Product
                        </button>
                    </div>

                    @error('lines')
                        <p class="text-xs text-rose-600">{{ $message }}</p>
                    @enderror

                    <div class="space-y-3">
                        <template x-for="(line, idx) in lines" :key="idx">
                            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500" x-text="`Product #${idx + 1}`"></span>
                                    <button type="button" x-show="lines.length > 1" @click="removeLine(idx)"
                                            class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">Remove</button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="md:col-span-2 relative" @click.outside="line.isOpen = false">
                                        <label class="block text-xs font-medium text-slate-700 mb-1">Product</label>
                                        <input type="hidden" :name="`lines[${idx}][product_id]`" :value="line.product_id">
                                        <div class="relative">
                                            <input type="text"
                                                   x-model="line.searchQuery"
                                                   @focus="line.isOpen = true"
                                                   @input="line.isOpen = true; line.product_id = ''"
                                                   placeholder="Search product name or SKU..."
                                                   autocomplete="off"
                                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8">
                                            <button type="button" x-show="line.product_id" @click="clearLineProduct(idx)"
                                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 font-bold">&times;</button>
                                        </div>
                                        <div x-show="line.isOpen && filteredProducts(line).length > 0"
                                             x-cloak
                                             class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100">
                                            <template x-for="prod in filteredProducts(line)" :key="prod.id">
                                                <div @click="selectLineProduct(idx, prod)"
                                                     class="p-2.5 hover:bg-indigo-50 cursor-pointer text-xs flex items-center justify-between">
                                                    <span class="font-medium text-slate-800" x-text="prod.name"></span>
                                                    <span class="font-mono text-[11px] text-slate-500" x-text="prod.sku"></span>
                                                </div>
                                            </template>
                                        </div>
                                        <template x-if="lineErrors[idx]?.product_id">
                                            <p class="mt-1 text-xs text-rose-600" x-text="lineErrors[idx].product_id"></p>
                                        </template>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 mb-1">UOM</label>
                                        <select :name="`lines[${idx}][uom_id]`" x-model="line.uom_id"
                                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">Select</option>
                                            <template x-for="u in uoms" :key="u.id">
                                                <option :value="String(u.id)" x-text="u.code"></option>
                                            </template>
                                        </select>
                                        <template x-if="lineErrors[idx]?.uom_id">
                                            <p class="mt-1 text-xs text-rose-600" x-text="lineErrors[idx].uom_id"></p>
                                        </template>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 mb-1">Rate</label>
                                        <input type="number" step="0.01" :name="`lines[${idx}][rate]`" x-model="line.rate"
                                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <template x-if="lineErrors[idx]?.rate">
                                            <p class="mt-1 text-xs text-rose-600" x-text="lineErrors[idx].rate"></p>
                                        </template>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 mb-1">Min Qty</label>
                                        <input type="number" step="0.01" :name="`lines[${idx}][min_qty]`" x-model="line.min_qty"
                                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <template x-if="lineErrors[idx]?.min_qty">
                                            <p class="mt-1 text-xs text-rose-600" x-text="lineErrors[idx].min_qty"></p>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            @endif

            <x-ui.button type="submit" variant="primary">Save</x-ui.button>
        </form>

        {{-- Create Customer Type Modal --}}
        <div x-show="typeModalOpen"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             role="dialog"
             aria-modal="true"
             @keydown.escape.window="closeTypeModal()">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="typeModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 transition-opacity bg-slate-900/50 backdrop-blur-sm"
                     @click="closeTypeModal()"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="typeModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-2xl shadow-xl sm:my-8"
                     @click.stop>

                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-semibold text-slate-800">Create Customer Type</h3>
                        <button type="button" @click="closeTypeModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div x-show="typeError" x-cloak class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-xs font-medium text-red-700" x-text="typeError"></div>
                    <div x-show="typeSuccess" x-cloak class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs font-medium text-emerald-700" x-text="typeSuccess"></div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Name *</label>
                            <input type="text"
                                   x-ref="typeInput"
                                   x-model="newTypeName"
                                   :disabled="typeSaving"
                                   @keydown.enter.prevent="saveType()"
                                   placeholder="e.g. Retailer, Super Stockist"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100">
                        </div>
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" @click="closeTypeModal()" :disabled="typeSaving"
                                    class="px-4 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50">
                                Cancel
                            </button>
                            <button type="button" @click="saveType()" :disabled="typeSaving"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                                <span x-text="typeSaving ? 'Saving...' : 'Save'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-ui.card>

@push('scripts')
<script>
function priceMasterForm(config) {
    const serverLineErrors = @json($serverLineErrors);

    return {
        isEdit: !!config.isEdit,
        products: config.products || [],
        uoms: config.uoms || [],
        lines: (config.lines || []).map((line) => ({
            product_id: String(line.product_id || ''),
            uom_id: String(line.uom_id || ''),
            rate: line.rate ?? '',
            min_qty: line.min_qty ?? '',
            searchQuery: line.searchQuery || '',
            isOpen: false,
        })),
        selectedCustomerTypeId: String(config.customerTypeId || ''),
        previousCustomerTypeId: String(config.customerTypeId || ''),
        customTypes: [],
        knownTypeNames: config.knownTypeNames || [],
        typeModalOpen: false,
        newTypeName: '',
        typeSaving: false,
        typeError: '',
        typeSuccess: '',
        lineErrors: {},

        editProductId: String(config.editProductId || ''),
        editProductQuery: '',
        editProductOpen: false,
        editUomId: String(config.editUomId || ''),
        editRate: config.editRate ?? '',
        editMinQty: config.editMinQty ?? '',

        init() {
            // Hydrate product search labels after old() / edit load
            this.lines.forEach((line) => {
                if (line.product_id) {
                    const prod = this.products.find(p => String(p.id) === String(line.product_id));
                    if (prod) {
                        line.searchQuery = `${prod.name}${prod.sku ? ' (' + prod.sku + ')' : ''}`;
                    }
                }
            });

            if (this.editProductId) {
                const prod = this.products.find(p => String(p.id) === String(this.editProductId));
                if (prod) {
                    this.editProductQuery = `${prod.name}${prod.sku ? ' (' + prod.sku + ')' : ''}`;
                }
            }

            // Map server validation errors into lineErrors[idx][field]
            const mapped = {};
            Object.keys(serverLineErrors || {}).forEach((key) => {
                const [idx, field] = key.split('.');
                if (!mapped[idx]) mapped[idx] = {};
                mapped[idx][field] = serverLineErrors[key];
            });
            this.lineErrors = mapped;
        },

        openTypeModal() {
            this.typeModalOpen = true;
            this.newTypeName = '';
            this.typeError = '';
            this.typeSuccess = '';
            this.$nextTick(() => this.$refs.typeInput?.focus());
        },
        closeTypeModal() {
            this.typeModalOpen = false;
            this.typeError = '';
            this.typeSuccess = '';
        },
        async saveType() {
            const name = (this.newTypeName || '').trim();
            if (!name) {
                this.typeError = 'Name is required.';
                return;
            }
            if (this.knownTypeNames.includes(name.toLowerCase())) {
                this.typeError = 'A customer type with this name already exists.';
                return;
            }

            this.typeSaving = true;
            this.typeError = '';
            this.typeSuccess = '';

            try {
                const response = await fetch("{{ route('masters.customer-types.quick-store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name })
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    this.typeError = data.message || 'Failed to save customer type.';
                    this.typeSaving = false;
                    return;
                }

                const newId = String(data.item.id);
                if (!this.customTypes.some(t => String(t.id) === newId)) {
                    this.customTypes.push({ id: data.item.id, name: data.item.name });
                }
                this.knownTypeNames.push(name.toLowerCase());
                this.typeSuccess = data.message || 'Created successfully.';
                this.selectedCustomerTypeId = newId;
                this.previousCustomerTypeId = newId;

                setTimeout(() => this.closeTypeModal(), 600);
            } catch (e) {
                this.typeError = 'Unable to save customer type. Please try again.';
            } finally {
                this.typeSaving = false;
            }
        },

        addLine() {
            this.lines.push({
                product_id: '',
                uom_id: '',
                rate: '',
                min_qty: '',
                searchQuery: '',
                isOpen: false,
            });
        },
        removeLine(idx) {
            if (this.lines.length <= 1) return;
            this.lines.splice(idx, 1);
        },
        filteredProducts(line) {
            const q = (line.searchQuery || '').toLowerCase().trim();
            // Selected product keeps the label in the input; show browse list until user types (clears product_id).
            if (!q || line.product_id) {
                return this.products.slice(0, 40);
            }
            return this.products.filter(p =>
                (p.name && p.name.toLowerCase().includes(q)) ||
                (p.sku && String(p.sku).toLowerCase().includes(q))
            ).slice(0, 40);
        },
        selectLineProduct(idx, prod) {
            const line = this.lines[idx];
            if (!line) return;
            line.product_id = String(prod.id);
            line.searchQuery = `${prod.name}${prod.sku ? ' (' + prod.sku + ')' : ''}`;
            line.isOpen = false;
        },
        clearLineProduct(idx) {
            const line = this.lines[idx];
            if (!line) return;
            line.product_id = '';
            line.searchQuery = '';
            line.isOpen = false;
        },

        get filteredEditProducts() {
            const q = (this.editProductQuery || '').toLowerCase().trim();
            if (!q || this.editProductId) {
                return this.products.slice(0, 40);
            }
            return this.products.filter(p =>
                (p.name && p.name.toLowerCase().includes(q)) ||
                (p.sku && String(p.sku).toLowerCase().includes(q))
            ).slice(0, 40);
        },
        selectEditProduct(prod) {
            this.editProductId = String(prod.id);
            this.editProductQuery = `${prod.name}${prod.sku ? ' (' + prod.sku + ')' : ''}`;
            this.editProductOpen = false;
        },
        clearEditProduct() {
            this.editProductId = '';
            this.editProductQuery = '';
            this.editProductOpen = false;
        },

    };
}
</script>
@endpush
@endsection
