@extends('layouts.dms')
@section('title', 'Products')
@section('content')
@php
    $currentListingUrl = request()->getRequestUri();
@endphp
<div id="listing-container" data-dynamic-container x-data="productIndex()">
    <x-ui.page-header title="Products">
        <x-slot name="actions">
            <x-ui.export-menu />
            <x-ui.button variant="primary" :href="route('masters.products.create', ['return_url' => $currentListingUrl])" data-preserve-state>+ Add Product</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if(isset($bannerProduct) && $bannerProduct)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-start sm:items-center gap-3">
                <div class="h-9 w-9 shrink-0 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-bold text-emerald-900">Recently Created Product:</span>
                        <span class="text-xs font-semibold text-emerald-800">{{ $bannerProduct->name }}</span>
                        @if($bannerProduct->sku)
                            <span class="font-mono text-[11px] text-emerald-700 bg-emerald-100/60 px-1.5 py-0.5 rounded">Part: {{ $bannerProduct->sku }}</span>
                        @endif
                    </div>
                    <p class="text-[11px] text-emerald-700 mt-0.5">
                        Product was created successfully. Your previously selected filters and page are preserved below.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                <x-ui.button variant="secondary" size="sm" :href="route('masters.products.show', ['product' => $bannerProduct, 'return_url' => $currentListingUrl])" data-preserve-state>View</x-ui.button>
                <x-ui.button variant="secondary" size="sm" :href="route('masters.products.edit', ['product' => $bannerProduct, 'return_url' => $currentListingUrl])" data-preserve-state>Edit</x-ui.button>
            </div>
        </div>
    @endif

    <x-ui.card>
        @if(($serialMatches ?? collect())->isNotEmpty())
            <div class="mb-6 space-y-4">
                @foreach($serialMatches as $serial)
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4">
                        <p class="mb-3 text-sm font-semibold text-indigo-900">Serial {{ $serial->serial_number }}</p>
                        @include('inventory.serials.partials.warranty-result', ['serial' => $serial])
                    </div>
                @endforeach
            </div>
        @endif

        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search Name, Part Number, Serial No, Model..."
            :reset-url="route('masters.products.index')"
        >
            <x-slot name="filters">
                <div
                    class="flex flex-wrap items-center gap-2"
                    data-hierarchical-filter-group
                    data-all-categories='@json($allCategoriesData ?? [])'
                    data-all-subcategories='@json($allSubCategoriesData ?? [])'
                >
                    <select
                        name="brand_id"
                        id="filter-brand-id"
                        data-dynamic-filter
                        aria-label="Filter by Brand"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Brand: All</option>
                        @foreach($brands ?? [] as $b)
                            <option value="{{ $b->id }}" @selected((string) ($brandId ?? request('brand_id')) === (string) $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>

                    <select
                        name="category_id"
                        id="filter-category-id"
                        data-dynamic-filter
                        aria-label="Filter by Category"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Category: All</option>
                        @foreach($categories ?? [] as $cat)
                            <option value="{{ $cat->id }}" @selected((string) ($categoryId ?? request('category_id')) === (string) $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>

                    <select
                        name="sub_category_id"
                        id="filter-sub-category-id"
                        data-dynamic-filter
                        aria-label="Filter by Subcategory"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Subcategory: All</option>
                        @foreach($subCategories ?? [] as $sc)
                            <option value="{{ $sc->id }}" @selected((string) ($subCategoryId ?? request('sub_category_id', request('subcategory_id'))) === (string) $sc->id)>{{ $sc->name }}</option>
                        @endforeach
                    </select>

                    <select
                        name="product_type"
                        data-dynamic-filter
                        aria-label="Filter by Product Type"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Product Type: All</option>
                        @foreach($productTypes ?? [] as $pt)
                            <option value="{{ $pt->name }}" @selected(request('product_type') == $pt->name || request('product_type') == $pt->id)>{{ $pt->name }}</option>
                        @endforeach
                    </select>

                    <select
                        name="status"
                        data-dynamic-filter
                        aria-label="Filter by Status"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Status: All</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="serial_no" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Serial No.
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Name
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="sku" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Part Number
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="model" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Models
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="product_type" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Product Type
                        </x-ui.sortable-th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Unit</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-slate-500">Tax</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Warranty</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($items as $item)
                        @php
                            $isNew = (string) $item->id === (string) ($newProductId ?? request('new_product_id'));
                            $isUpdated = (string) $item->id === (string) ($updatedProductId ?? request('updated_product_id'));
                        @endphp
                        <tr class="{{ $isNew ? 'bg-emerald-50/70 ring-1 ring-emerald-300' : ($isUpdated ? 'bg-indigo-50/70 ring-1 ring-indigo-300' : 'hover:bg-slate-50/75') }} transition-colors"
                            @if($isNew || $isUpdated) x-init="$nextTick(() => { $el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); })" @endif>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $item->serial_no ?: '—' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <a href="{{ route('masters.products.show', ['product' => $item, 'return_url' => $currentListingUrl]) }}" data-preserve-state class="hover:text-indigo-700 hover:underline">{{ $item->name }}</a>
                                    @if($isNew)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">New</span>
                                    @elseif($isUpdated)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-100 text-indigo-800">Updated</span>
                                    @endif
                                </div>
                                @if($item->brand || $item->category || $item->subCategory)
                                    <div class="text-[11px] text-slate-400 font-normal mt-0.5">
                                        {{ collect([$item->brand?->name, $item->category?->name, $item->subCategory?->name])->filter()->join(' · ') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $item->sku }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->models ?: ($item->model ?: '—') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->product_type ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->baseUom?->code ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-slate-700 font-medium">{{ $item->tax_rate }}%</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item->warranty ?: 'No warranty' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-ui.button variant="secondary" size="sm" :href="route('masters.products.show', ['product' => $item, 'return_url' => $currentListingUrl])" title="View Product" aria-label="View Product" data-preserve-state>View</x-ui.button>
                                    <x-ui.button variant="secondary" size="sm" :href="route('masters.products.edit', ['product' => $item, 'return_url' => $currentListingUrl])" title="Edit Product" aria-label="Edit Product" data-preserve-state>Edit</x-ui.button>
                                    <button type="button"
                                            @click="promptDelete('{{ $item->id }}', '{{ addslashes($item->name) }}', '{{ addslashes($item->sku) }}', '{{ addslashes($item->serial_no ?: '—') }}')"
                                            :disabled="isDeleting"
                                            class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-xs px-2.5 py-1.5 text-xs"
                                            title="Delete Product"
                                            aria-label="Delete Product"
                                            data-delete-product="{{ $item->id }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-0">
                                <x-ui.empty-state
                                    title="No matching products found"
                                    description="Try clearing search or filters to see more results."
                                    class="border-0 rounded-none py-10"
                                >
                                    <x-slot name="action">
                                        <a href="{{ route('masters.products.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                            Reset Filters
                                        </a>
                                    </x-slot>
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="mt-4">{{ $items->links() }}</div>
        @endif
    </x-ui.card>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true"
         @keydown.escape.window="cancelDelete()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"
                 @click="cancelDelete()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="sm:flex sm:items-start gap-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:text-left flex-1">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Delete Product</h3>
                            <div class="mt-2 space-y-3">
                                <p class="text-xs text-slate-500">Are you sure you want to delete this product? This action cannot be undone.</p>
                                
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs text-left">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Product Name:</span>
                                        <span class="font-semibold text-slate-900" x-text="deletingProductName"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Part Number:</span>
                                        <span class="font-mono text-slate-700" x-text="deletingProductSku"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Serial No.:</span>
                                        <span class="font-mono text-slate-700" x-text="deletingProductSerial"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 flex flex-row-reverse gap-2.5 border-t border-slate-100">
                    <button type="button"
                            @click="executeDelete()"
                            :disabled="isDeleting"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isDeleting">Delete</span>
                        <span x-show="isDeleting" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Deleting...
                        </span>
                    </button>
                    <button type="button"
                            @click="cancelDelete()"
                            :disabled="isDeleting"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div x-show="notification.show"
         x-cloak
         x-transition
         class="fixed bottom-4 right-4 z-50 max-w-sm rounded-lg p-4 shadow-lg text-white text-sm"
         :class="notification.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
        <div class="flex items-center gap-2">
            <span x-text="notification.message"></span>
            <button type="button" @click="notification.show = false" class="ml-auto text-white/80 hover:text-white">&times;</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function productIndex() {
    return {
        showDeleteModal: false,
        isDeleting: false,
        deletingProductId: null,
        deletingProductName: '',
        deletingProductSku: '',
        deletingProductSerial: '',
        notification: {
            show: false,
            type: 'success',
            message: '',
        },

        promptDelete(id, name, sku, serialNo) {
            this.deletingProductId = id;
            this.deletingProductName = name;
            this.deletingProductSku = sku;
            this.deletingProductSerial = serialNo || '—';
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingProductId = null;
            this.deletingProductName = '';
            this.deletingProductSku = '';
            this.deletingProductSerial = '';
        },

        async executeDelete() {
            if (!this.deletingProductId || this.isDeleting) return;
            this.isDeleting = true;

            const url = `{{ url('masters/products') }}/${this.deletingProductId}`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showDeleteModal = false;
                    this.showNotification('success', data.message || 'Product deleted successfully.');

                    const rows = document.querySelectorAll('#listing-container tbody tr:not([data-empty])');
                    const currentUrl = new URL(window.location.href);
                    let currentPage = parseInt(currentUrl.searchParams.get('page') || '1', 10);

                    if (rows.length <= 1 && currentPage > 1) {
                        currentUrl.searchParams.set('page', currentPage - 1);
                        window.history.replaceState(null, '', currentUrl.toString());
                    }

                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to delete product.');
                }
            } catch (err) {
                console.error(err);
                this.showNotification('error', 'Network error occurred while deleting product.');
            } finally {
                this.isDeleting = false;
            }
        },

        async refreshList() {
            try {
                const res = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) {
                    window.location.reload();
                    return;
                }
                const html = await res.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContainer = doc.querySelector('#listing-container');
                const currentContainer = document.querySelector('#listing-container');

                if (newContainer && currentContainer) {
                    const currentTableWrapper = currentContainer.querySelector('.overflow-x-auto');
                    const newTableWrapper = newContainer.querySelector('.overflow-x-auto');
                    if (currentTableWrapper && newTableWrapper) {
                        currentTableWrapper.innerHTML = newTableWrapper.innerHTML;
                    }

                    const currentPagination = currentContainer.querySelector('.mt-4');
                    const newPagination = newContainer.querySelector('.mt-4');
                    if (currentPagination && newPagination) {
                        currentPagination.innerHTML = newPagination.innerHTML;
                    } else if (!currentPagination && newPagination) {
                        currentTableWrapper.insertAdjacentElement('afterend', newPagination);
                    } else if (currentPagination && !newPagination) {
                        currentPagination.remove();
                    }
                } else {
                    window.location.reload();
                }
            } catch (e) {
                console.error('Failed to refresh table dynamic container:', e);
                window.location.reload();
            }
        },

        showNotification(type, message) {
            this.notification.type = type;
            this.notification.message = message;
            this.notification.show = true;
            setTimeout(() => {
                this.notification.show = false;
            }, 4500);
        }
    };
}

(function() {
    if (window.__hierarchicalFiltersBound) return;
    window.__hierarchicalFiltersBound = true;

    // Listen on capture phase so invalid dependent dropdown selections
    // are reset BEFORE dynamic-tables.js builds the URL and triggers fetchAndSwap.
    document.addEventListener('change', function(e) {
        const filter = e.target;
        if (!filter || !filter.matches || !filter.matches('[data-dynamic-filter]')) return;

        const group = filter.closest('[data-hierarchical-filter-group]');
        if (!group) return;

        const brandSelect = group.querySelector('select[name="brand_id"]');
        const categorySelect = group.querySelector('select[name="category_id"]');
        const subCategorySelect = group.querySelector('select[name="sub_category_id"]');

        if (!brandSelect || !categorySelect || !subCategorySelect) return;

        let allCats = [];
        let allSubCats = [];
        try {
            allCats = JSON.parse(group.getAttribute('data-all-categories') || '[]');
            allSubCats = JSON.parse(group.getAttribute('data-all-subcategories') || '[]');
        } catch (_) {}

        if (filter === brandSelect) {
            const newBrandId = brandSelect.value;
            const currentCatId = categorySelect.value;

            if (currentCatId && newBrandId) {
                // Check if current category is tied to another brand
                const currentCat = allCats.find(c => String(c.id) === String(currentCatId));
                if (currentCat && currentCat.brand_id && String(currentCat.brand_id) !== String(newBrandId)) {
                    categorySelect.value = '';
                    subCategorySelect.value = '';
                }
            }
        } else if (filter === categorySelect) {
            const newCatId = categorySelect.value;
            const currentSubId = subCategorySelect.value;

            if (currentSubId && newCatId) {
                // Check if current subcategory belongs to another category
                const currentSub = allSubCats.find(sc => String(sc.id) === String(currentSubId));
                if (currentSub && currentSub.category_id && String(currentSub.category_id) !== String(newCatId)) {
                    subCategorySelect.value = '';
                }
            }
        }
    }, true);

    // Keep return_url on all state-preserving actions updated with live query string
    document.addEventListener('click', function(e) {
        const link = e.target.closest('[data-preserve-state]');
        if (!link || !link.href) return;
        try {
            const u = new URL(link.href, window.location.origin);
            const livePathAndQuery = window.location.pathname + window.location.search;
            u.searchParams.set('return_url', livePathAndQuery);
            link.href = u.toString();
        } catch (_) {}
    }, true);
})();
</script>
@endpush
@endsection
