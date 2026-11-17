@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Warehouse' : 'Create Warehouse')
@section('content')
@php
    $branchOptions = ($branches ?? collect())->map(fn ($b) => [
        'id' => (string) $b->id,
        'company_id' => (string) $b->company_id,
        'name' => $b->name,
    ])->values();
    $selectedState = old('state', $item->state);
    $selectedCity = old('city', $item->city);
    $selectedCountry = old('country', $item->country ?: 'India');
    $selectedType = old('warehouse_type', $item->warehouse_type);
    $initialCities = $selectedState
        ? ($stateCities[$selectedState] ?? \App\Support\IndianCities::getCitiesForState($selectedState))
        : [];
@endphp
<x-ui.page-header :title="$item->exists ? 'Edit Warehouse' : 'Create Warehouse'">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('organization.warehouses.index')">Back</x-ui.button>
    </x-slot>
</x-ui.page-header>

<div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
    <p class="font-semibold text-slate-800">Company → Branch → Warehouse</p>
    <p class="mt-1 text-xs leading-relaxed">Every warehouse must belong to a branch, and every branch must belong to a company.</p>
</div>

<x-ui.card>
    <form method="POST"
          action="{{ $item->exists ? route('organization.warehouses.update', $item) : route('organization.warehouses.store') }}"
          class="space-y-6 max-w-4xl"
          x-data="warehouseForm({
              companyId: @js((string) old('company_id', $item->company_id)),
              branchId: @js((string) old('branch_id', $item->branch_id)),
              branches: @js($branchOptions),
              state: @js($selectedState),
              city: @js($selectedCity),
              country: @js($selectedCountry),
              warehouseType: @js($selectedType),
              warehouseTypes: @js(collect($warehouseTypes ?? [])->values()->all()),
              cities: @js(array_values($initialCities)),
              stateCities: @js($stateCities ?? []),
              citiesUrl: @js($citiesUrl ?? route('organization.branches.cities')),
              cityStoreUrl: @js($cityStoreUrl ?? route('organization.branches.cities.quick-store')),
              warehouseTypeStoreUrl: @js($warehouseTypeStoreUrl ?? route('organization.warehouses.warehouse-types.quick-store')),
              csrf: @js(csrf_token()),
          })"
          x-init="init()">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div>
            <h3 class="text-sm font-semibold text-slate-800 mb-3">Warehouse Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company <span class="text-red-500">*</span></label>
                    <select name="company_id" x-model="companyId" @change="onCompanyChange()" required
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select company</option>
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('company_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch <span class="text-red-500">*</span></label>
                    <select name="branch_id" x-ref="branchSelect" x-model="branchId" required :disabled="!companyId"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-50">
                        <option value="">{{ old('company_id', $item->company_id) ? 'Select branch' : 'Select company first' }}</option>
                        <template x-for="b in filteredBranches" :key="b.id">
                            <option :value="b.id" x-text="b.name" :selected="String(b.id) === String(branchId)"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-[11px] text-slate-500">Branches are filtered by the selected company.</p>
                    @error('branch_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Warehouse Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $item->name) }}" required maxlength="255"
                           @blur="titleCaseField($event)" placeholder="e.g. Main Warehouse"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:max-w-xs">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 mb-1">
                        <span>Warehouse Type</span>
                        <button type="button" @click="openTypeModal()" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline">+ Create</button>
                    </label>
                    <select name="warehouse_type" x-ref="warehouseTypeSelect" x-model="warehouseType"
                            class="block w-full max-w-xs rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select type</option>
                        @foreach(($warehouseTypes ?? []) as $typeName)
                            <option value="{{ $typeName }}" @selected((string) $selectedType === (string) $typeName)>{{ $typeName }}</option>
                        @endforeach
                    </select>
                    @error('warehouse_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person', $item->contact_person) }}" maxlength="255"
                           @blur="titleCaseField($event)" placeholder="Optional"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('contact_person') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $item->contact_phone) }}" maxlength="30"
                           placeholder="Optional"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('contact_phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-800 mb-3">Address</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 1 <span class="text-red-500">*</span></label>
                    <input type="text" name="address" value="{{ old('address', $item->address) }}" required maxlength="500"
                           @blur="titleCaseField($event)" placeholder="Building, street, area"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('address') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                    <input type="text" name="address_line_2" value="{{ old('address_line_2', $item->address_line_2) }}" maxlength="255"
                           @blur="titleCaseField($event)" placeholder="Landmark, floor (optional)"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('address_line_2') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State <span class="text-red-500">*</span></label>
                    <select name="state" x-model="state" @change="onStateChange()" required
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select state</option>
                        @foreach($states as $stateName)
                            <option value="{{ $stateName }}" @selected((string) $selectedState === (string) $stateName)>{{ $stateName }}</option>
                        @endforeach
                    </select>
                    @error('state') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 mb-1">
                        <span>City <span class="text-red-500">*</span></span>
                        <button type="button" x-show="state" @click="openCityModal()" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline">+ Create</button>
                    </label>
                    <select name="city" x-ref="citySelect" x-model="city" required :disabled="!state"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-50 disabled:text-slate-400">
                        <option value="">{{ $selectedState ? 'Select city' : 'Select state first' }}</option>
                        @foreach($initialCities as $cityName)
                            <option value="{{ $cityName }}" @selected((string) $selectedCity === (string) $cityName)>{{ $cityName }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-slate-500" x-show="!state">Select a state to enable city.</p>
                    @error('city') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Country <span class="text-red-500">*</span></label>
                    <select name="country" x-model="country" required
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select country</option>
                        @foreach($countries as $countryName)
                            <option value="{{ $countryName }}" @selected((string) $selectedCountry === (string) $countryName)>{{ $countryName }}</option>
                        @endforeach
                    </select>
                    @error('country') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pincode</label>
                    <input type="text" name="pincode" value="{{ old('pincode', $item->pincode) }}" maxlength="12" placeholder="Optional"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('pincode') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-800 mb-3">Operational Settings</h3>
            <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $item->is_default ?? false)) class="rounded border-gray-300 text-indigo-600">
                    Set as default warehouse
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600">
                    Active
                </label>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="3" maxlength="2000" placeholder="Optional notes"
                      class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $item->notes) }}</textarea>
            @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <x-ui.button type="submit" variant="primary">Save Warehouse</x-ui.button>
            <x-ui.button variant="secondary" :href="route('organization.warehouses.index')">Cancel</x-ui.button>
        </div>

        <div x-show="typeModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50" @click="typeModalOpen = false"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white shadow-xl border border-slate-200 p-5 space-y-4">
                <h3 class="text-sm font-semibold text-slate-800">Create Warehouse Type</h3>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="newTypeName" @blur="newTypeName = toTitleCase(newTypeName)"
                           @keydown.enter.prevent="saveWarehouseType()" placeholder="e.g. Finished Goods"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-[11px] text-slate-400">Each word is auto-capitalized.</p>
                    <p class="mt-1 text-xs text-rose-600" x-show="typeError" x-text="typeError"></p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="typeModalOpen = false" class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="button" @click="saveWarehouseType()" :disabled="typeSaving"
                            class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                        <span x-text="typeSaving ? 'Saving…' : 'Create'"></span>
                    </button>
                </div>
            </div>
        </div>

        <div x-show="cityModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50" @click="cityModalOpen = false"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white shadow-xl border border-slate-200 p-5 space-y-4">
                <h3 class="text-sm font-semibold text-slate-800">Create City</h3>
                <p class="text-xs text-slate-500">State: <span class="font-semibold text-slate-700" x-text="state"></span></p>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">City Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="newCityName" @blur="newCityName = toTitleCase(newCityName)"
                           @keydown.enter.prevent="saveCity()" placeholder="e.g. Khanna"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-rose-600" x-show="cityError" x-text="cityError"></p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="cityModalOpen = false" class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="button" @click="saveCity()" :disabled="citySaving"
                            class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                        <span x-text="citySaving ? 'Saving…' : 'Create'"></span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection

@push('scripts')
<script>
function warehouseForm(config) {
    return {
        companyId: config.companyId || '',
        branchId: config.branchId || '',
        branches: Array.isArray(config.branches) ? config.branches : [],
        state: config.state || '',
        city: config.city || '',
        country: config.country || 'India',
        warehouseType: config.warehouseType || '',
        warehouseTypes: Array.isArray(config.warehouseTypes) ? config.warehouseTypes : [],
        cities: Array.isArray(config.cities) ? config.cities : [],
        stateCities: config.stateCities || {},
        citiesUrl: config.citiesUrl,
        cityStoreUrl: config.cityStoreUrl,
        warehouseTypeStoreUrl: config.warehouseTypeStoreUrl,
        csrf: config.csrf,
        typeModalOpen: false,
        newTypeName: '',
        typeSaving: false,
        typeError: '',
        cityModalOpen: false,
        newCityName: '',
        citySaving: false,
        cityError: '',

        get filteredBranches() {
            return this.branches.filter(b => !this.companyId || String(b.company_id) === String(this.companyId));
        },

        init() {
            this.$watch('state', (value, oldValue) => {
                if (String(value || '') === String(oldValue || '')) return;
                this.onStateChange();
            });
            this.$nextTick(() => {
                this.rebuildCitySelect(false);
                this.rebuildBranchSelect();
                if (window.DmsSearchableSelect) {
                    window.DmsSearchableSelect.enhanceAll(this.$el);
                }
            });
        },

        onCompanyChange() {
            if (!this.filteredBranches.some(b => String(b.id) === String(this.branchId))) {
                this.branchId = '';
            }
            this.$nextTick(() => this.rebuildBranchSelect(true));
        },

        rebuildBranchSelect(reenhance = false) {
            const select = this.$refs.branchSelect;
            if (!select) return;
            this.resetSearchableSelect(select);
            const current = this.branchId || '';
            select.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = this.companyId ? 'Select branch' : 'Select company first';
            select.appendChild(placeholder);
            this.filteredBranches.forEach((b) => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = b.name;
                if (String(b.id) === String(current)) opt.selected = true;
                select.appendChild(opt);
            });
            select.disabled = !this.companyId;
            select.value = this.filteredBranches.some(b => String(b.id) === String(current)) ? current : '';
            this.branchId = select.value;
            if (reenhance && this.companyId && window.DmsSearchableSelect) {
                window.DmsSearchableSelect.enhance(select);
            }
        },

        toTitleCase(value) {
            return String(value || '').trim().split(/\s+/).filter(Boolean)
                .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ');
        },
        titleCaseField(event) {
            if (!event?.target) return;
            event.target.value = this.toTitleCase(event.target.value);
        },

        async onStateChange() {
            const nativeState = this.$el.querySelector('select[name="state"]')?.value || '';
            if (nativeState && nativeState !== this.state) this.state = nativeState;
            this.city = '';
            await this.loadCitiesForState();
            this.rebuildCitySelect(true);
        },

        async loadCitiesForState() {
            if (!this.state) { this.cities = []; return; }
            if (Array.isArray(this.stateCities[this.state]) && this.stateCities[this.state].length) {
                this.cities = this.stateCities[this.state];
                return;
            }
            try {
                const res = await fetch(`${this.citiesUrl}?state=${encodeURIComponent(this.state)}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.cities = Array.isArray(data.cities) ? data.cities : [];
                this.stateCities[this.state] = this.cities;
            } catch (e) {
                this.cities = [];
            }
        },

        resetSearchableSelect(select) {
            if (!select) return;
            const container = select.closest('.dms-searchable-container');
            if (container && container.parentNode) {
                container.parentNode.insertBefore(select, container);
                container.remove();
            }
            select.removeAttribute('data-dms-searchable-initialized');
            select.classList.remove('dms-searchable-enhanced');
            select.style.cssText = '';
            select.tabIndex = 0;
        },

        rebuildCitySelect(reenhance = false) {
            const select = this.$refs.citySelect;
            if (!select) return;
            this.resetSearchableSelect(select);
            const current = this.city || '';
            select.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = this.state ? 'Select city' : 'Select state first';
            select.appendChild(placeholder);
            (this.cities || []).forEach((name) => {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                if (String(name) === String(current)) opt.selected = true;
                select.appendChild(opt);
            });
            select.disabled = !this.state;
            select.value = current && (this.cities || []).includes(current) ? current : '';
            this.city = select.value;
            if (reenhance && this.state && window.DmsSearchableSelect) {
                window.DmsSearchableSelect.enhance(select);
            }
        },

        openTypeModal() {
            this.typeError = '';
            this.newTypeName = '';
            this.typeModalOpen = true;
        },

        rebuildWarehouseTypeSelect(reenhance = false) {
            const select = this.$refs.warehouseTypeSelect;
            if (!select) return;
            this.resetSearchableSelect(select);
            const current = this.warehouseType || '';
            select.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select type';
            select.appendChild(placeholder);
            (this.warehouseTypes || []).forEach((name) => {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                if (String(name) === String(current)) opt.selected = true;
                select.appendChild(opt);
            });
            select.value = current && (this.warehouseTypes || []).some(n => String(n).toLowerCase() === String(current).toLowerCase())
                ? (this.warehouseTypes.find(n => String(n).toLowerCase() === String(current).toLowerCase()) || current)
                : '';
            this.warehouseType = select.value;
            if (reenhance && window.DmsSearchableSelect) {
                window.DmsSearchableSelect.enhance(select);
            }
        },

        async saveWarehouseType() {
            this.newTypeName = this.toTitleCase(this.newTypeName);
            if (!this.newTypeName) {
                this.typeError = 'Warehouse type name is required.';
                return;
            }
            this.typeSaving = true;
            this.typeError = '';
            try {
                const res = await fetch(this.warehouseTypeStoreUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ name: this.newTypeName }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    this.typeError = data.message || 'Could not create warehouse type.';
                    return;
                }
                const name = data.item?.name || this.newTypeName;
                if (!this.warehouseTypes.some(t => String(t).toLowerCase() === String(name).toLowerCase())) {
                    this.warehouseTypes = [...this.warehouseTypes, name].sort((a, b) => a.localeCompare(b));
                }
                this.warehouseType = name;
                this.rebuildWarehouseTypeSelect(true);
                this.typeModalOpen = false;
            } catch (e) {
                this.typeError = 'Could not create warehouse type.';
            } finally {
                this.typeSaving = false;
            }
        },

        openCityModal() {
            if (!this.state) return;
            this.cityError = '';
            this.newCityName = '';
            this.cityModalOpen = true;
        },

        async saveCity() {
            this.newCityName = this.toTitleCase(this.newCityName);
            if (!this.state) { this.cityError = 'Select a state first.'; return; }
            if (!this.newCityName) { this.cityError = 'City name is required.'; return; }
            this.citySaving = true;
            this.cityError = '';
            try {
                const res = await fetch(this.cityStoreUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ state: this.state, name: this.newCityName }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    this.cityError = data.message || 'Could not create city.';
                    return;
                }
                const name = data.item?.name || this.newCityName;
                if (!this.cities.some(c => String(c).toLowerCase() === String(name).toLowerCase())) {
                    this.cities = [...this.cities, name].sort((a, b) => a.localeCompare(b));
                }
                this.stateCities[this.state] = this.cities;
                this.city = name;
                this.rebuildCitySelect(true);
                this.cityModalOpen = false;
            } catch (e) {
                this.cityError = 'Could not create city.';
            } finally {
                this.citySaving = false;
            }
        },
    };
}
</script>
@endpush
