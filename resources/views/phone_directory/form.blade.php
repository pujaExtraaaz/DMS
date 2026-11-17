@extends('layouts.dms')
@section('title', $isEdit ? 'Edit Contact: ' . $contact->contact_name : 'Add New Contact - Phone Directory')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/phone-directory.css') }}?v=20261116d">
@endpush

@section('content')
@php
    $deptOptions = $departments->map(fn ($d) => ['id' => (string) $d->id, 'name' => $d->name])->values();
    $catOptions = $categories->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name])->values();
    $typeOptions = collect($contactTypes)->map(fn ($label, $key) => ['id' => (string) $key, 'name' => $label])->values();
@endphp

<div
    class="pd-page max-w-5xl mx-auto space-y-6"
    x-data="contactFormHandler({
        photoUrl: @js($contact->profile_photo_url),
        departments: @js($deptOptions),
        categories: @js($catOptions),
        contactTypes: @js($typeOptions),
        states: @js($states ?? []),
        stateCities: @js($stateCities ?? []),
        countries: @js($countries ?? []),
        selectedDepartmentId: @js((string) old('department_id', $contact->department_id ?? '')),
        selectedCategoryId: @js((string) old('category_id', $contact->category_id ?? '')),
        selectedContactType: @js((string) old('contact_type', $contact->contact_type ?? 'other')),
        country: @js(old('country', $contact->country ?? 'India')),
        state: @js(old('state', $contact->state ?? '')),
        city: @js(old('city', $contact->city ?? '')),
        area: @js(old('area', $contact->area ?? '')),
        storeDeptUrl: @js(route('phone-directory.departments.store')),
        storeCatUrl: @js(route('phone-directory.categories.store')),
        csrf: @js(csrf_token()),
    })"
>
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ route('phone-directory.index') }}" class="hover:text-indigo-600 transition">Phone Directory</a>
            <span>/</span>
            <span class="font-medium text-slate-900">{{ $isEdit ? 'Edit Contact' : 'New Contact' }}</span>
        </div>
        <a href="{{ route('phone-directory.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            <span>Back to Directory</span>
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800">
            <div class="font-bold mb-2">Please correct the errors below before saving:</div>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ $isEdit ? route('phone-directory.contacts.update', $contact) : route('phone-directory.contacts.store') }}"
        enctype="multipart/form-data"
        class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden"
    >
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-900">{{ $isEdit ? 'Edit Contact Details' : 'Create New Directory Contact' }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">Fill in the contact information across the categorized sections below.</p>
            </div>
            <span class="text-[11px] text-slate-400 shrink-0"><span class="text-rose-500 font-bold">*</span> Required</span>
        </div>

        <div class="p-6 space-y-8">
            {{-- A: Basic --}}
            <section class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <span class="pd-section-badge">A</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Basic Details</h3>
                </div>

                <div class="pd-form-grid">
                    <div class="pd-photo-box">
                        <div class="pd-photo-box__avatar">
                            <template x-if="photoPreview">
                                <div class="pd-photo-avatar"><img :src="photoPreview" alt="Preview"></div>
                            </template>
                            <template x-if="!photoPreview && existingPhoto">
                                <div class="pd-photo-avatar"><img :src="existingPhoto" alt="Current"></div>
                            </template>
                            <template x-if="!photoPreview && !existingPhoto">
                                <div class="pd-photo-avatar">Photo</div>
                            </template>
                        </div>
                        <div class="pd-photo-box__meta space-y-2">
                            <label class="pd-label mb-0">Profile Photo</label>
                            <p>JPG, PNG, WebP · max 3MB</p>
                            <div class="flex flex-wrap items-center gap-2">
                                <input type="file" name="profile_photo" accept="image/*" @change="previewPhoto($event)" class="max-w-full text-xs text-slate-600">
                                @if($isEdit && $contact->profile_photo)
                                    <label class="inline-flex items-center gap-1 text-[11px] text-rose-600 cursor-pointer">
                                        <input type="checkbox" name="remove_photo" value="1" class="rounded border-slate-300 text-rose-600">
                                        <span>Remove</span>
                                    </label>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="pd-label">Contact Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="contact_name" value="{{ old('contact_name', $contact->contact_name) }}" required placeholder="e.g. Rajesh Kumar Sharma" class="pd-field pd-titlecase" @blur="titleCaseInput($event)">
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <label class="pd-label mb-0">Contact Type <span class="text-rose-500">*</span></label>
                            <button type="button" class="pd-create-link" @click="openCreate('type')">+ Create Type</button>
                        </div>
                        <input type="hidden" name="contact_type" :value="selectedContactType" required>
                        <div class="relative" @click.outside="typeOpen = false">
                            <input type="text" x-model="typeQuery" @focus="typeOpen = true" @input="typeOpen = true" placeholder="Search contact type..." class="pd-field" autocomplete="off">
                            <div x-show="typeOpen" x-cloak class="pd-dropdown">
                                <template x-for="opt in filteredTypes" :key="opt.id">
                                    <button type="button" @click="selectType(opt)" x-text="opt.name"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="pd-label">Designation / Job Title</label>
                        <input type="text" name="designation" value="{{ old('designation', $contact->designation) }}" placeholder="e.g. Procurement Head" class="pd-field pd-titlecase" @blur="titleCaseInput($event)">
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <label class="pd-label mb-0">Department</label>
                            <button type="button" class="pd-create-link" @click="openCreate('department')">+ Create Department</button>
                        </div>
                        <input type="hidden" name="department_id" :value="selectedDepartmentId">
                        <div class="relative" @click.outside="deptOpen = false">
                            <input type="text" x-model="deptQuery" @focus="deptOpen = true" @input="deptOpen = true" placeholder="Search department..." class="pd-field" autocomplete="off">
                            <div x-show="deptOpen" x-cloak class="pd-dropdown">
                                <button type="button" @click="selectDepartment(null)">Clear</button>
                                <template x-for="opt in filteredDepartments" :key="opt.id">
                                    <button type="button" @click="selectDepartment(opt)" x-text="opt.name"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="pd-label">Company / Organization</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $contact->company_name) }}" placeholder="e.g. Acme Corporation Pvt Ltd" class="pd-field pd-titlecase" @blur="titleCaseInput($event)">
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <label class="pd-label mb-0">Contact Category</label>
                            <button type="button" class="pd-create-link" @click="openCreate('category')">+ Create Category</button>
                        </div>
                        <input type="hidden" name="category_id" :value="selectedCategoryId">
                        <div class="relative" @click.outside="catOpen = false">
                            <input type="text" x-model="catQuery" @focus="catOpen = true" @input="catOpen = true" placeholder="Search category..." class="pd-field" autocomplete="off">
                            <div x-show="catOpen" x-cloak class="pd-dropdown">
                                <button type="button" @click="selectCategory(null)">Clear</button>
                                <template x-for="opt in filteredCategories" :key="opt.id">
                                    <button type="button" @click="selectCategory(opt)" x-text="opt.name"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- B: Contact --}}
            <section class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <span class="pd-section-badge">B</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Contact Information</h3>
                </div>
                <div class="pd-form-grid">
                    <div>
                        <label class="pd-label">Primary Mobile Number <span class="text-rose-500">*</span></label>
                        <input type="tel" name="mobile_number" value="{{ old('mobile_number', $contact->mobile_number) }}" required placeholder="e.g. 9876543210" class="pd-field">
                    </div>
                    <div>
                        <label class="pd-label">Alternate Mobile Number</label>
                        <input type="tel" name="alternate_mobile" value="{{ old('alternate_mobile', $contact->alternate_mobile) }}" placeholder="e.g. 9876543211" class="pd-field">
                    </div>
                    <div>
                        <label class="pd-label">WhatsApp Number</label>
                        <input type="tel" name="whatsapp_number" value="{{ old('whatsapp_number', $contact->whatsapp_number) }}" placeholder="Leave blank if same as mobile" class="pd-field">
                    </div>
                    <div>
                        <label class="pd-label">Office Phone / Landline</label>
                        <input type="text" name="office_phone" value="{{ old('office_phone', $contact->office_phone) }}" placeholder="e.g. 022-28765432" class="pd-field">
                    </div>
                    <div>
                        <label class="pd-label">Extension Number</label>
                        <input type="text" name="extension_number" value="{{ old('extension_number', $contact->extension_number) }}" placeholder="e.g. 104" class="pd-field">
                    </div>
                    <div>
                        <label class="pd-label">Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $contact->email) }}" placeholder="e.g. contact@example.com" class="pd-field">
                    </div>
                    <div class="pd-span-3">
                        <label class="pd-label">Alternate / Secondary Email</label>
                        <input type="email" name="alternate_email" value="{{ old('alternate_email', $contact->alternate_email) }}" placeholder="e.g. accounts@example.com" class="pd-field">
                    </div>
                </div>
            </section>

            {{-- C: Address Country > State > City > Area --}}
            <section class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <span class="pd-section-badge">C</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Address Details</h3>
                </div>
                <div class="pd-form-grid">
                    <div class="pd-span-3">
                        <label class="pd-label">Street Address / Building / Plot</label>
                        <textarea name="address" rows="2" placeholder="e.g. Suite 402, Signature Tower, MG Road" class="pd-field pd-titlecase" @blur="titleCaseInput($event)">{{ old('address', $contact->address) }}</textarea>
                    </div>

                    <div>
                        <label class="pd-label">Country</label>
                        <input type="hidden" name="country" :value="country">
                        <div class="relative" @click.outside="countryOpen = false; countryHi = -1">
                            <input
                                type="text"
                                x-model="countryQuery"
                                @focus="countryOpen = true; countryHi = Math.max(0, filteredCountries.indexOf(country))"
                                @input="countryOpen = true; countryHi = 0"
                                @keydown="onListKey($event, 'country')"
                                placeholder="Search country..."
                                class="pd-field"
                                autocomplete="off"
                                role="combobox"
                                aria-autocomplete="list"
                                :aria-expanded="countryOpen"
                            >
                            <div x-show="countryOpen" x-cloak class="pd-dropdown" x-ref="countryList" role="listbox">
                                <template x-for="(name, idx) in filteredCountries" :key="name">
                                    <button
                                        type="button"
                                        role="option"
                                        :class="{ 'is-active': countryHi === idx }"
                                        @mouseenter="countryHi = idx"
                                        @click="selectCountry(name)"
                                        x-text="name"
                                    ></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="pd-label">State</label>
                        <input type="hidden" name="state" :value="state">
                        <div class="relative" @click.outside="stateOpen = false; stateHi = -1">
                            <input
                                type="text"
                                x-model="stateQuery"
                                @focus="stateOpen = true; stateHi = 0"
                                @input="stateOpen = true; stateHi = 0"
                                @keydown="onListKey($event, 'state')"
                                placeholder=""
                                class="pd-field"
                                autocomplete="off"
                            >
                            <div x-show="stateOpen" x-cloak class="pd-dropdown" x-ref="stateList">
                                <button type="button" :class="{ 'is-active': stateHi === 0 }" @mouseenter="stateHi = 0" @click="selectState('')">Clear</button>
                                <template x-for="(name, idx) in filteredStates" :key="name">
                                    <button type="button" :class="{ 'is-active': stateHi === (idx + 1) }" @mouseenter="stateHi = idx + 1" @click="selectState(name)" x-text="name"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="pd-label">City</label>
                        <input type="hidden" name="city" :value="city">
                        <div class="relative" @click.outside="cityOpen = false; cityHi = -1">
                            <input
                                type="text"
                                x-model="cityQuery"
                                @focus="if (state) { cityOpen = true; cityHi = 0; }"
                                @input="if (state) { cityOpen = true; cityHi = 0; }"
                                @keydown="onListKey($event, 'city')"
                                :disabled="!state"
                                :placeholder="state ? 'Search city...' : 'Select state first'"
                                class="pd-field"
                                autocomplete="off"
                            >
                            <div x-show="cityOpen && state" x-cloak class="pd-dropdown" x-ref="cityList">
                                <button type="button" :class="{ 'is-active': cityHi === 0 }" @mouseenter="cityHi = 0" @click="selectCity('')">Clear</button>
                                <template x-for="(name, idx) in filteredCities" :key="name">
                                    <button type="button" :class="{ 'is-active': cityHi === (idx + 1) }" @mouseenter="cityHi = idx + 1" @click="selectCity(name)" x-text="name"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="pd-label">Area / Locality</label>
                        <input type="text" name="area" x-model="area" placeholder="" class="pd-field pd-titlecase" @blur="area = toTitleCase(area)">
                    </div>

                    <div>
                        <label class="pd-label">PIN Code</label>
                        <input type="text" name="pincode" value="{{ old('pincode', $contact->pincode) }}" placeholder="e.g. 400093" class="pd-field">
                    </div>
                </div>
            </section>

            {{-- D --}}
            <section class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <span class="pd-section-badge">D</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Additional Details & Flags</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-3 p-4 rounded-xl border border-slate-200 bg-slate-50">
                        <label class="flex items-center justify-between gap-3 cursor-pointer">
                            <div>
                                <span class="text-xs font-semibold text-slate-800">Important Contact</span>
                                <p class="text-[11px] text-slate-500">Mark for priority filtering and quick access.</p>
                            </div>
                            <input type="checkbox" name="is_important" value="1" @checked(old('is_important', $contact->is_important)) class="h-5 w-5 rounded border-slate-300 text-indigo-600">
                        </label>
                        <div class="border-t border-slate-200 pt-3">
                            <label class="pd-label">Status</label>
                            <select name="status" class="pd-field">
                                <option value="active" @selected(old('status', $contact->status ?? 'active') === 'active')>Active</option>
                                <option value="inactive" @selected(old('status', $contact->status) === 'inactive')>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="pd-label">Notes / Remarks</label>
                        <textarea name="notes" rows="4" placeholder="Add any internal remarks..." class="pd-field">{{ old('notes', $contact->notes) }}</textarea>
                    </div>
                </div>
            </section>
        </div>

        <div class="pd-footer">
            <a href="{{ route('phone-directory.index') }}" class="pd-cancel-btn">Cancel</a>
            <button type="submit" class="pd-save-btn">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                <span>{{ $isEdit ? 'Update Contact' : 'Save Contact' }}</span>
            </button>
        </div>
    </form>

    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl border border-slate-200 p-5 space-y-4" @click.outside="createModal = false">
            <h3 class="text-sm font-bold text-slate-900" x-text="createTitle"></h3>
            <div>
                <label class="pd-label">Name *</label>
                <input type="text" x-model="createName" @blur="createName = toTitleCase(createName)" class="pd-field pd-titlecase" placeholder="Enter name">
            </div>
            <p x-show="createError" class="text-[11px] text-rose-600" x-text="createError"></p>
            <div class="flex justify-end gap-2">
                <button type="button" @click="createModal = false" class="pd-cancel-btn">Cancel</button>
                <button type="button" @click="submitCreate()" class="pd-save-btn" :disabled="createSaving">
                    <span x-text="createSaving ? 'Saving...' : 'Save'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function contactFormHandler(config) {
    return {
        photoPreview: null,
        existingPhoto: config.photoUrl || null,
        departments: config.departments || [],
        categories: config.categories || [],
        contactTypes: config.contactTypes || [],
        states: config.states || [],
        stateCities: config.stateCities || {},
        countries: config.countries || [],
        selectedDepartmentId: config.selectedDepartmentId || '',
        selectedCategoryId: config.selectedCategoryId || '',
        selectedContactType: config.selectedContactType || 'other',
        country: config.country || 'India',
        state: config.state || '',
        city: config.city || '',
        area: config.area || '',
        deptQuery: '', catQuery: '', typeQuery: '', countryQuery: '', stateQuery: '', cityQuery: '',
        deptOpen: false, catOpen: false, typeOpen: false, countryOpen: false, stateOpen: false, cityOpen: false,
        countryHi: -1, stateHi: -1, cityHi: -1,
        createModal: false, createKind: '', createName: '', createError: '', createSaving: false,
        storeDeptUrl: config.storeDeptUrl, storeCatUrl: config.storeCatUrl, csrf: config.csrf,

        init() {
            this.deptQuery = (this.departments.find(d => String(d.id) === String(this.selectedDepartmentId)) || {}).name || '';
            this.catQuery = (this.categories.find(c => String(c.id) === String(this.selectedCategoryId)) || {}).name || '';
            this.typeQuery = (this.contactTypes.find(t => String(t.id) === String(this.selectedContactType)) || {}).name || '';
            this.countryQuery = this.country || '';
            this.stateQuery = this.state || '';
            this.cityQuery = this.city || '';
        },

        onListKey(e, kind) {
            const maps = {
                country: { open: 'countryOpen', hi: 'countryHi', list: () => this.filteredCountries, pick: (v) => this.selectCountry(v), ref: 'countryList', offset: 0 },
                state: { open: 'stateOpen', hi: 'stateHi', list: () => ['', ...this.filteredStates], pick: (v) => this.selectState(v), ref: 'stateList', offset: 0 },
                city: { open: 'cityOpen', hi: 'cityHi', list: () => this.state ? ['', ...this.filteredCities] : [], pick: (v) => this.selectCity(v), ref: 'cityList', offset: 0 },
            };
            const cfg = maps[kind];
            if (!cfg) return;
            const list = cfg.list();
            if (!list.length && e.key !== 'Escape') return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this[cfg.open] = true;
                this[cfg.hi] = Math.min((this[cfg.hi] < 0 ? -1 : this[cfg.hi]) + 1, list.length - 1);
                this.$nextTick(() => this.scrollActive(cfg.ref));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this[cfg.open] = true;
                this[cfg.hi] = Math.max(this[cfg.hi] - 1, 0);
                this.$nextTick(() => this.scrollActive(cfg.ref));
            } else if (e.key === 'Enter') {
                if (this[cfg.open] && this[cfg.hi] >= 0 && this[cfg.hi] < list.length) {
                    e.preventDefault();
                    cfg.pick(list[this[cfg.hi]]);
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this[cfg.open] = false;
                this[cfg.hi] = -1;
            }
        },

        scrollActive(refName) {
            const root = this.$refs[refName];
            if (!root) return;
            const el = root.querySelector('button.is-active');
            if (el) el.scrollIntoView({ block: 'nearest' });
        },

        toTitleCase(value) {
            return String(value || '').trim().replace(/\s+/g, ' ')
                .replace(/\w\S*/g, (w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase());
        },
        titleCaseInput(e) { e.target.value = this.toTitleCase(e.target.value); },

        startsWithFilter(list, query, mapper = (x) => x) {
            const q = String(query || '').toLowerCase().trim();
            if (!q) return list;
            const starts = [], contains = [];
            list.forEach((item) => {
                const name = String(mapper(item) || '').toLowerCase();
                if (name.startsWith(q)) starts.push(item);
                else if (name.includes(q)) contains.push(item);
            });
            return starts.concat(contains);
        },

        get filteredDepartments() { return this.startsWithFilter(this.departments, this.deptQuery, o => o.name); },
        get filteredCategories() { return this.startsWithFilter(this.categories, this.catQuery, o => o.name); },
        get filteredTypes() { return this.startsWithFilter(this.contactTypes, this.typeQuery, o => o.name); },
        get filteredCountries() { return this.startsWithFilter(this.countries, this.countryQuery); },
        get filteredStates() { return this.startsWithFilter(this.states, this.stateQuery); },
        get filteredCities() { return this.startsWithFilter(this.stateCities[this.state] || [], this.cityQuery); },

        selectDepartment(opt) { this.selectedDepartmentId = opt ? opt.id : ''; this.deptQuery = opt ? opt.name : ''; this.deptOpen = false; },
        selectCategory(opt) { this.selectedCategoryId = opt ? opt.id : ''; this.catQuery = opt ? opt.name : ''; this.catOpen = false; },
        selectType(opt) { this.selectedContactType = opt ? opt.id : ''; this.typeQuery = opt ? opt.name : ''; this.typeOpen = false; },
        selectCountry(name) { this.country = name; this.countryQuery = name; this.countryOpen = false; this.countryHi = -1; },
        selectState(name) { this.state = name; this.stateQuery = name; this.stateOpen = false; this.stateHi = -1; this.city = ''; this.cityQuery = ''; this.cityHi = -1; },
        selectCity(name) { this.city = name; this.cityQuery = name; this.cityOpen = false; this.cityHi = -1; },

        previewPhoto(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => { this.photoPreview = e.target.result; };
            reader.readAsDataURL(file);
        },

        get createTitle() {
            return ({ department: 'Create Department', category: 'Create Category', type: 'Create Contact Type' })[this.createKind] || 'Create';
        },
        openCreate(kind) { this.createKind = kind; this.createName = ''; this.createError = ''; this.createModal = true; },

        async submitCreate() {
            this.createName = this.toTitleCase(this.createName);
            if (!this.createName) { this.createError = 'Name is required.'; return; }

            if (this.createKind === 'type') {
                const id = this.createName.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '') || 'custom';
                if (!this.contactTypes.some(t => t.id === id)) this.contactTypes.push({ id, name: this.createName });
                this.selectType({ id, name: this.createName });
                this.createModal = false;
                return;
            }

            this.createSaving = true; this.createError = '';
            try {
                const url = this.createKind === 'department' ? this.storeDeptUrl : this.storeCatUrl;
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ name: this.createName }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    this.createError = data.message || Object.values(data.errors || {}).flat().join(' ') || 'Could not save.';
                    return;
                }
                if (this.createKind === 'department') {
                    const opt = { id: String(data.department.id), name: data.department.name };
                    this.departments.push(opt);
                    this.departments.sort((a, b) => a.name.localeCompare(b.name));
                    this.selectDepartment(opt);
                } else {
                    const opt = { id: String(data.category.id), name: data.category.name };
                    this.categories.push(opt);
                    this.categories.sort((a, b) => a.name.localeCompare(b.name));
                    this.selectCategory(opt);
                }
                this.createModal = false;
            } catch (e) {
                this.createError = 'Network error while saving.';
            } finally {
                this.createSaving = false;
            }
        },
    };
}
</script>
@endpush
@endsection
