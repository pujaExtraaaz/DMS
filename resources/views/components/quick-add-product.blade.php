{{--
    Quick Add Product modal — self-contained Alpine widget.
    Emits a browser event `product-quick-added` with the created product summary
    so calling screens (PO / PI / Sales) can append it to their <select> or list.

    Usage:
        <x-quick-add-product />
        <button type="button" @click="$dispatch('open-quick-add-product')">+ Quick add</button>
--}}
<div
    x-data="quickAddProduct()"
    x-init="init()"
    x-on:open-quick-add-product.window="open()"
    x-cloak
>
    <template x-teleport="body">
        <div x-show="isOpen" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-2xl rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between px-5 py-3 border-b">
                    <h3 class="text-base font-semibold text-slate-800">Quick add product</h3>
                    <button type="button" @click="close()" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form @submit.prevent="save()" class="px-5 py-4 space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Product name *</label>
                            <input type="text" x-model="form.name" required class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">SKU (auto if blank)</label>
                            <input type="text" x-model="form.sku" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">HSN Code</label>
                            <input type="text" x-model="form.hsn_code" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Base UOM *</label>
                            <select x-model="form.base_uom_id" required class="block w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Select</option>
                                <template x-for="u in options.uoms" :key="u.id">
                                    <option :value="u.id" x-text="`${u.name} (${u.code})`"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Brand</label>
                            <select x-model="form.brand_id" class="block w-full rounded-lg border-gray-300 text-sm">
                                <option value="">—</option>
                                <template x-for="b in options.brands" :key="b.id"><option :value="b.id" x-text="b.name"></option></template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Category</label>
                            <select x-model="form.category_id" class="block w-full rounded-lg border-gray-300 text-sm">
                                <option value="">—</option>
                                <template x-for="c in options.categories" :key="c.id"><option :value="c.id" x-text="c.name"></option></template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Color / Variant</label>
                            <input type="text" x-model="form.color_variant" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Tax %</label>
                            <input type="number" step="0.01" x-model="form.tax_rate" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Purchase Price</label>
                            <input type="number" step="0.01" x-model="form.purchase_price" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Selling Price</label>
                            <input type="number" step="0.01" x-model="form.selling_price" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">MRP</label>
                            <input type="number" step="0.01" x-model="form.calculation_mrp" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                    </div>

                    <template x-if="error"><div class="text-xs text-red-600" x-text="error"></div></template>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="close()" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="saving" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                            <span x-show="!saving">Save &amp; use</span>
                            <span x-show="saving">Saving…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

@once
    @push('scripts')
    <script>
        function quickAddProduct() {
            return {
                isOpen: false,
                saving: false,
                error: null,
                options: { brands: [], categories: [], uoms: [] },
                form: { name: '', sku: '', hsn_code: '', base_uom_id: '', brand_id: '', category_id: '', color_variant: '', tax_rate: 0, purchase_price: 0, selling_price: 0, calculation_mrp: 0 },
                async init() {
                    try {
                        const r = await fetch("{{ route('masters.products.quick-add.options') }}", { headers: { Accept: 'application/json' } });
                        this.options = await r.json();
                    } catch (e) { /* silent */ }
                },
                open() { this.isOpen = true; this.error = null; },
                close() { this.isOpen = false; },
                async save() {
                    this.saving = true; this.error = null;
                    try {
                        const r = await fetch("{{ route('masters.products.quick-add') }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content, Accept: 'application/json' },
                            body: JSON.stringify(this.form),
                        });
                        const data = await r.json();
                        if (! r.ok) { this.error = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Failed to save.'); this.saving = false; return; }
                        window.dispatchEvent(new CustomEvent('product-quick-added', { detail: data.product }));
                        this.isOpen = false;
                        this.form = { name: '', sku: '', hsn_code: '', base_uom_id: '', brand_id: '', category_id: '', color_variant: '', tax_rate: 0, purchase_price: 0, selling_price: 0, calculation_mrp: 0 };
                    } catch (e) { this.error = 'Network error while saving.'; }
                    finally { this.saving = false; }
                },
            }
        }
    </script>
    @endpush
@endonce
