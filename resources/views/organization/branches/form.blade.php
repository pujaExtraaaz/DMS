@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Branch' : 'Create Branch')
@section('content')
@php
    $selectedState = old('state', $item->state);
    $selectedCity = old('city', $item->city);
    $selectedCountry = old('country', $item->country ?: 'India');
    $selectedType = old('branch_type', $item->branch_type);
    $typeNames = collect($branchTypes ?? [])->pluck('name')->filter()->unique()->values();
    $initialCities = $selectedState
        ? ($stateCities[$selectedState] ?? \App\Support\IndianCities::getCitiesForState($selectedState))
        : [];
@endphp
<x-ui.page-header :title="$item->exists ? 'Edit Branch' : 'Create Branch'">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('organization.branches.index')">Back</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="POST"
          action="{{ $item->exists ? route('organization.branches.update', $item) : route('organization.branches.store') }}"
          class="space-y-6 max-w-4xl"
          x-data="branchForm({
              state: @js($selectedState),
              city: @js($selectedCity),
              country: @js($selectedCountry),
              branchType: @js($selectedType),
              cities: @js(array_values($initialCities)),
              stateCities: @js($stateCities ?? []),
              branchTypes: @js($typeNames),
              citiesUrl: @js(route('organization.branches.cities')),
              cityStoreUrl: @js(route('organization.branches.cities.quick-store')),
              branchTypeStoreUrl: @js(route('organization.branches.branch-types.quick-store')),
              csrf: @js(csrf_token()),
          })"
          x-init="init()">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div>
            <h3 class="text-sm font-semibold text-slate-800 mb-3">Branch Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company <span class="text-red-500">*</span></label>
                    <select name="company_id" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select company</option>
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}" @selected((string) old('company_id', $item->company_id) === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('company_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch Name <span class="text-red-500">*</span></label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', $item->getRawOriginal('name')) }}"
                           required
                           maxlength="255"
                           @blur="titleCaseField($event)"
                           placeholder="e.g. Head Office Ludhiana"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
                    <input type="text"
                           name="phone"
                           value="{{ old('phone', $item->phone) }}"
                           required
                           maxlength="30"
                           placeholder="Primary phone"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alternate Phone</label>
                    <input type="text"
                           name="alternate_phone"
                           value="{{ old('alternate_phone', $item->alternate_phone) }}"
                           maxlength="30"
                           placeholder="Optional"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('alternate_phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email"
                           name="email"
                           value="{{ old('email', $item->email) }}"
                           maxlength="255"
                           placeholder="branch@company.com"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alternate Email</label>
                    <input type="email"
                           name="alternate_email"
                           value="{{ old('alternate_email', $item->alternate_email) }}"
                           maxlength="255"
                           placeholder="Optional"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('alternate_email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:max-w-xs">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 mb-1">
                        <span>Branch Type <span class="text-red-500">*</span></span>
                        <button type="button" @click="openTypeModal()" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline">+ Create</button>
                    </label>
                    <select name="branch_type" x-ref="branchTypeSelect" x-model="branchType" required
                            class="block w-full max-w-xs rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select type</option>
                        @foreach($typeNames as $typeName)
                            <option value="{{ $typeName }}" @selected((string) $selectedType === (string) $typeName)>{{ $typeName }}</option>
                        @endforeach
                    </select>
                    @error('branch_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-800 mb-3">Address</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 1 <span class="text-red-500">*</span></label>
                    <input type="text"
                           name="address"
                           value="{{ old('address', $item->address) }}"
                           required
                           maxlength="500"
                           @blur="titleCaseField($event)"
                           placeholder="Building, street, area"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('address') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                    <input type="text"
                           name="address_line_2"
                           value="{{ old('address_line_2', $item->address_line_2) }}"
                           maxlength="255"
                           @blur="titleCaseField($event)"
                           placeholder="Landmark, floor (optional)"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('address_line_2') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State <span class="text-red-500">*</span></label>
                    <select name="state" x-model="state" @change="onStateChange()" x-on:change="onStateChange()" required
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
                    <input type="text"
                           name="pincode"
                           value="{{ old('pincode', $item->pincode) }}"
                           maxlength="12"
                           placeholder="Optional"
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
                    Set as default branch
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600">
                    Active
                </label>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="3" maxlength="2000"
                      placeholder="Optional notes about this branch"
                      class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $item->notes) }}</textarea>
            @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <x-ui.button type="submit" variant="primary">Save Branch</x-ui.button>
            <x-ui.button variant="secondary" :href="route('organization.branches.index')">Cancel</x-ui.button>
        </div>

        <div x-show="typeModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50" @click="typeModalOpen = false"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white shadow-xl border border-slate-200 p-5 space-y-4">
                <h3 class="text-sm font-semibold text-slate-800">Create Branch Type</h3>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text"
                           x-model="newTypeName"
                           @blur="newTypeName = toTitleCase(newTypeName)"
                           @keydown.enter.prevent="saveBranchType()"
                           placeholder="e.g. Service Center"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-[11px] text-slate-400">Each word is auto-capitalized.</p>
                    <p class="mt-1 text-xs text-rose-600" x-show="typeError" x-text="typeError"></p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="typeModalOpen = false" class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="button" @click="saveBranchType()" :disabled="typeSaving"
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
                    <input type="text"
                           x-model="newCityName"
                           @blur="newCityName = toTitleCase(newCityName)"
                           @keydown.enter.prevent="saveCity()"
                           placeholder="e.g. Khanna"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-[11px] text-slate-400">Each word is auto-capitalized. City is saved under the selected state.</p>
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
function branchForm(config) {
    return {
        state: config.state || '',
        city: config.city || '',
        country: config.country || 'India',
        branchType: config.branchType || '',
        cities: Array.isArray(config.cities) ? config.cities : [],
        stateCities: config.stateCities || {},
        branchTypes: Array.isArray(config.branchTypes) ? config.branchTypes : [],
        citiesUrl: config.citiesUrl,
        cityStoreUrl: config.cityStoreUrl,
        branchTypeStoreUrl: config.branchTypeStoreUrl,
        csrf: config.csrf,
        typeModalOpen: false,
        newTypeName: '',
        typeSaving: false,
        typeError: '',
        cityModalOpen: false,
        newCityName: '',
        citySaving: false,
        cityError: '',

        init() {
            this.$watch('state', (value, oldValue) => {
                if (String(value || '') === String(oldValue || '')) return;
                this.onStateChange();
            });
            this.$nextTick(() => {
                this.rebuildCitySelect(false);
                if (window.DmsSearchableSelect) {
                    window.DmsSearchableSelect.enhanceAll(this.$el);
                }
            });
        },

        toTitleCase(value) {
            return String(value || '')
                .trim()
                .split(/\s+/)
                .filter(Boolean)
                .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                .join(' ');
        },

        titleCaseField(event) {
            if (!event?.target) return;
            event.target.value = this.toTitleCase(event.target.value);
        },

        async onStateChange() {
            // Keep Alpine in sync if searchable-select updated the native <select>.
            const nativeState = this.$el.querySelector('select[name="state"]')?.value || '';
            if (nativeState && nativeState !== this.state) {
                this.state = nativeState;
            }
            this.city = '';
            await this.loadCitiesForState();
            this.rebuildCitySelect(true);
        },

        async loadCitiesForState() {
            if (!this.state) {
                this.cities = [];
                return;
            }
            if (Array.isArray(this.stateCities[this.state]) && this.stateCities[this.state].length) {
                this.cities = this.stateCities[this.state];
                return;
            }
            try {
                const res = await fetch(`${this.citiesUrl}?state=${encodeURIComponent(this.state)}`, {
                    headers: { 'Accept': 'application/json' }
                });
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

            // Always unwrap the searchable widget before mutating options.
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
                if (String(name) === String(current)) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });

            select.disabled = !this.state;
            select.required = true;
            select.value = current && (this.cities || []).includes(current) ? current : '';
            this.city = select.value;

            if (reenhance && this.state && window.DmsSearchableSelect) {
                window.DmsSearchableSelect.enhance(select);
            } else if (!this.state) {
                // Keep disabled native select visible with placeholder until state is chosen.
                select.style.cssText = '';
            }
        },

        openTypeModal() {
            this.typeError = '';
            this.newTypeName = '';
            this.typeModalOpen = true;
        },

        openCityModal() {
            if (!this.state) return;
            this.cityError = '';
            this.newCityName = '';
            this.cityModalOpen = true;
        },

        async saveCity() {
            this.newCityName = this.toTitleCase(this.newCityName);
            if (!this.state) {
                this.cityError = 'Select a state first.';
                return;
            }
            if (!this.newCityName) {
                this.cityError = 'City name is required.';
                return;
            }

            this.citySaving = true;
            this.cityError = '';
            try {
                const res = await fetch(this.cityStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
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

        async saveBranchType() {
            this.newTypeName = this.toTitleCase(this.newTypeName);
            if (!this.newTypeName) {
                this.typeError = 'Branch type name is required.';
                return;
            }

            this.typeSaving = true;
            this.typeError = '';
            try {
                const res = await fetch(this.branchTypeStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ name: this.newTypeName }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    this.typeError = data.message || 'Could not create branch type.';
                    return;
                }

                const name = data.item?.name || this.newTypeName;
                const select = this.$refs.branchTypeSelect;
                const exists = [...(select?.options || [])].some(o => String(o.value).toLowerCase() === String(name).toLowerCase());
                if (select && !exists) {
                    const opt = document.createElement('option');
                    opt.value = name;
                    opt.textContent = name;
                    select.appendChild(opt);
                }
                this.branchType = name;
                if (select) select.value = name;

                if (select && window.DmsSearchableSelect) {
                    this.resetSearchableSelect(select);
                    window.DmsSearchableSelect.enhance(select);
                }

                this.typeModalOpen = false;
            } catch (e) {
                this.typeError = 'Could not create branch type.';
            } finally {
                this.typeSaving = false;
            }
        },
    };
}
</script>
@endpush
