<div x-show="brandCreateOpen" x-cloak class="fixed inset-0 overflow-y-auto" style="z-index: 90" role="dialog" aria-modal="true" aria-labelledby="catalog-brand-create-title">
    <div class="fixed inset-0 bg-slate-900/60" @click="brandCreateOpen = false"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-xl rounded-xl border border-slate-200 bg-white shadow-2xl" @click.stop>
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="catalog-brand-create-title" class="text-base font-semibold text-slate-900">Create Brand</h3>
                <button type="button" @click="brandCreateOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4 px-6 py-5">
                <div class="space-y-1">
                    <label for="catalog_brand_company" class="block text-sm font-medium text-gray-700">Company</label>
                    <select id="catalog_brand_company" @change="brandCreateCompanyId = $event.target.value" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label for="catalog_brand_name" class="block text-sm font-medium text-gray-700">Brand Name <span class="text-red-500">*</span></label>
                    <input id="catalog_brand_name" type="text" x-model="brandCreateName" @keydown.enter.prevent="saveBrand()" maxlength="255" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="space-y-1">
                    <label for="catalog_brand_detail" class="block text-sm font-medium text-slate-700">Detail</label>
                    <textarea id="catalog_brand_detail" x-model="brandCreateDetail" rows="4" maxlength="2000" placeholder="Enter Brand Details..." class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" x-model="brandCreateActive" class="rounded border-gray-300 text-indigo-600">
                    Active
                </label>
                <p class="text-xs text-red-600" x-show="brandCreateError" x-text="brandCreateError"></p>
            </div>
            <div class="px-6 pb-5">
                <button type="button" @click="saveBrand()" :disabled="brandCreateSaving" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">Save</button>
            </div>
        </div>
    </div>
</div>

<div x-show="categoryCreateOpen" x-cloak class="fixed inset-0 overflow-y-auto" style="z-index: 80" role="dialog" aria-modal="true" aria-labelledby="catalog-category-create-title">
    <div class="fixed inset-0 bg-slate-900/60" @click="categoryCreateOpen = false"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-xl rounded-xl border border-slate-200 bg-white shadow-2xl" @click.stop>
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 id="catalog-category-create-title" class="text-base font-semibold text-slate-900">Create Category</h3>
                <button type="button" @click="categoryCreateOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4 px-6 py-5">
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label for="catalog_category_brand" class="block text-sm font-medium text-gray-700">Brand <span class="text-red-500">*</span></label>
                        <button type="button" @click="openBrandCreate('category')" class="text-[11px] font-semibold text-indigo-700 hover:text-indigo-900">+ Create</button>
                    </div>
                    <select id="catalog_category_brand" :required="categoryCreateOpen" @change="categoryCreateBrandId = $event.target.value; categoryCreateError = ''" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label for="catalog_category_name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                    <input id="catalog_category_name" type="text" x-model="categoryCreateName" @keydown.enter.prevent="saveCategory()" maxlength="255" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" x-model="categoryCreateActive" class="rounded border-gray-300 text-indigo-600">
                    Active
                </label>
                <p class="text-xs text-red-600" x-show="categoryCreateError" x-text="categoryCreateError"></p>
            </div>
            <div class="px-6 pb-5">
                <button type="button" @click="saveCategory()" :disabled="categoryCreateSaving" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">Save</button>
            </div>
        </div>
    </div>
</div>