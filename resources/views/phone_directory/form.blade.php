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
    $designationOptions = collect($masterDesignations ?? [])->values();
    $companyOptions = collect($companies ?? [])->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name])->values();
    $classificationOptions = collect($classifications ?? [])->map(fn ($cl) => ['id' => (string) $cl->id, 'name' => $cl->name])->values();
    $resolvedDesignation = \App\Support\ContactDesignations::resolve(
        old('designation', $contact->designation ?? ''),
        old('designation_id', $contact->designation_id) ? (int) old('designation_id', $contact->designation_id) : null
    );
@endphp

<div
    class="pd-page max-w-5xl mx-auto space-y-6"
    x-data="contactFormHandler({
        photoUrl: @js($contact->profile_photo_url),
        departments: @js($deptOptions),
        categories: @js($catOptions),
        contactTypes: @js($typeOptions),
        designations: @js($designationOptions),
        companies: @js($companyOptions),
        classifications: @js($classificationOptions),
        states: @js($states ?? []),
        stateCities: @js($stateCities ?? []),
        countries: @js($countries ?? []),
        selectedDepartmentId: @js((string) old('department_id', $contact->department_id ?? '')),
        selectedCategoryId: @js((string) old('category_id', $contact->category_id ?? '')),
        selectedContactType: @js((string) old('contact_type', $contact->contact_type ?? 'other')),
        selectedDesignationId: @js((string) old('designation_id', $resolvedDesignation['designation_id'] ?? '')),
        selectedDesignationName: @js((string) old('designation', $resolvedDesignation['designation'] ?? '')),
        otherDesignation: @js((string) old('other_designation', $resolvedDesignation['other_designation'] ?? '')),
        selectedCompanyId: @js((string) old('company_id', $contact->company_id ?? ($isEdit ? '' : ($defaultCompanyId ?? '')))),
        companyName: @js((string) old('company_name', $contact->company?->name ?? $contact->company_name ?? '')),
        companyWebsite: @js((string) old('company_website', $contact->company_website ?? '')),
        selectedClassificationId: @js((string) old('customer_type_id', old('classification_id', $contact->customer_type_id ?? ''))),
        country: @js(old('country', $contact->country ?? 'India')),
        state: @js(old('state', $contact->state ?? '')),
        city: @js(old('city', $contact->city ?? '')),
        area: @js(old('area', $contact->area ?? '')),
        storeDeptUrl: @js(route('phone-directory.departments.store')),
        storeCatUrl: @js(route('phone-directory.categories.store')),
        storeDesignationUrl: @js(route('masters.designations.quick-store')),
        csrf: @js(csrf_token()),
    })"
>
    @php
        $returnTo = request('return_to', old('return_to'));
        $partyId = request('party_id', old('party_id'));
        $partyReturnUrl = $partyId ? route('masters.customers.edit', $partyId) : route('masters.customers.create');
    @endphp

    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            @if($returnTo === 'party')
                <a href="{{ $partyReturnUrl }}" class="hover:text-indigo-600 transition">Parties</a>
                <span>/</span>
            @endif
            <a href="{{ route('phone-directory.index') }}" class="hover:text-indigo-600 transition">Phone Directory</a>
            <span>/</span>
            <span class="font-medium text-slate-900">{{ $isEdit ? 'Edit Contact' : 'New Contact' }}</span>
        </div>
        @if($returnTo === 'party')
            <a href="{{ $partyReturnUrl }}" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-900 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                <span>Back to Party Form</span>
            </a>
        @else
            <a href="{{ route('phone-directory.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                <span>Back to Directory</span>
            </a>
        @endif
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
        <input type="hidden" name="return_to" value="{{ $returnTo }}">
        <input type="hidden" name="party_id" value="{{ $partyId }}">

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
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <label class="pd-label mb-0">Party Classification</label>
                        </div>
                        <input type="hidden" name="customer_type_id" :value="selectedClassificationId">
                        <div class="relative" @click.outside="classOpen = false">
                            <input
                                type="text"
                                x-model="classificationQuery"
                                @focus="classOpen = true"
                                @input="classOpen = true; onClassificationInput()"
                                placeholder="Search party classification..."
                                class="pd-field"
                                autocomplete="off"
                            >
                            <div x-show="classOpen" x-cloak class="pd-dropdown">
                                <button type="button" @click="selectClassification(null)" class="text-slate-400 italic">Clear Selection</button>
                                <template x-for="opt in filteredClassifications" :key="opt.id">
                                    <button type="button" @click="selectClassification(opt)" x-text="opt.name"></button>
                                </template>
                                <div x-show="filteredClassifications.length === 0" class="px-3 py-2 text-xs text-slate-400">No classifications found</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <label class="pd-label mb-0">Designation / Job Title</label>
                            <button type="button" class="pd-create-link" @click="openCreate('designation')">+ Create Designation</button>
                        </div>
                        <input type="hidden" name="designation_id" :value="selectedDesignationId">
                        <input type="hidden" name="designation" :value="effectiveDesignation()">
                        <div class="relative" @click.outside="desigOpen = false">
                            <input type="text" x-model="desigQuery" @focus="desigOpen = true" @input="desigOpen = true" placeholder="Search designation..." class="pd-field" autocomplete="off">
                            <div x-show="desigOpen" x-cloak class="pd-dropdown">
                                <button type="button" @click="selectDesignation(null)">Clear</button>
                                <template x-for="opt in filteredDesignations" :key="opt.id">
                                    <button type="button" @click="selectDesignation(opt)" x-text="opt.name"></button>
                                </template>
                            </div>
                        </div>
                        <div x-show="isOtherDesignation" x-cloak class="mt-2">
                            <label class="pd-label">Custom Designation</label>
                            <input type="text" x-model="otherDesignation" @blur="otherDesignation = toTitleCase(otherDesignation)" placeholder="Enter custom job title" class="pd-field pd-titlecase">
                        </div>
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

                    <div>
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <label class="pd-label mb-0">Company / Organization</label>
                        </div>
                        <input type="hidden" name="company_id" :value="selectedCompanyId">
                        <input type="hidden" name="company_name" :value="selectedCompanyName">
                        <div class="relative" @click.outside="companyOpen = false">
                            <input
                                type="text"
                                x-model="companyQuery"
                                @focus="companyOpen = true"
                                @input="companyOpen = true; onCompanyInput()"
                                placeholder="Search company from Setup → Companies..."
                                class="pd-field"
                                autocomplete="off"
                            >
                            <div x-show="companyOpen" x-cloak class="pd-dropdown">
                                <button type="button" @click="selectCompany(null)" class="text-slate-400 italic">Clear Selection</button>
                                <template x-for="opt in filteredCompanies" :key="opt.id">
                                    <button type="button" @click="selectCompany(opt)" x-text="opt.name"></button>
                                </template>
                                <div x-show="filteredCompanies.length === 0" class="px-3 py-2 text-xs text-slate-400">No companies found</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <label class="pd-label mb-0" for="company_website">Company Website</label>
                        </div>
                        <input
                            type="url"
                            id="company_website"
                            name="company_website"
                            x-model="companyWebsite"
                            @blur="normalizeWebsite()"
                            value="{{ old('company_website', $contact->company_website) }}"
                            placeholder="https://www.example.com"
                            class="pd-field"
                            autocomplete="url"
                        >
                        @error('company_website')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
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

        {{-- Form Actions Footer --}}
        <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex items-center justify-end gap-3">
            <a
                href="{{ $returnTo === 'party' ? $partyReturnUrl : route('phone-directory.index') }}"
                class="btn-pd-secondary inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-900 shadow-xs hover:bg-slate-50 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/20 transition whitespace-nowrap min-h-[38px]"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn-pd-primary inline-flex items-center justify-center gap-1.5 rounded-xl bg-purple-600 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-purple-700 active:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition whitespace-nowrap min-h-[38px]"
            >
                <svg class="h-4 w-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
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
        designations: config.designations || [],
        companies: config.companies || [],
        classifications: config.classifications || [],
        states: config.states || [],
        stateCities: config.stateCities || {},
        countries: config.countries || [],
        selectedDepartmentId: config.selectedDepartmentId || '',
        selectedCategoryId: config.selectedCategoryId || '',
        selectedContactType: config.selectedContactType || 'other',
        selectedDesignationId: config.selectedDesignationId || '',
        selectedDesignationName: config.selectedDesignationName || '',
        otherDesignation: config.otherDesignation || '',
        selectedCompanyId: config.selectedCompanyId || '',
        selectedCompanyName: config.companyName || '',
        companyWebsite: config.companyWebsite || '',
        selectedClassificationId: config.selectedClassificationId || '',
        country: config.country || 'India',
        state: config.state || '',
        city: config.city || '',
        area: config.area || '',
        deptQuery: '', catQuery: '', typeQuery: '', desigQuery: '', companyQuery: '', classificationQuery: '', countryQuery: '', stateQuery: '', cityQuery: '',
        deptOpen: false, catOpen: false, typeOpen: false, desigOpen: false, companyOpen: false, classOpen: false, countryOpen: false, stateOpen: false, cityOpen: false,
        countryHi: -1, stateHi: -1, cityHi: -1,
        createModal: false, createKind: '', createName: '', createError: '', createSaving: false,
        storeDeptUrl: config.storeDeptUrl, storeCatUrl: config.storeCatUrl, storeDesignationUrl: config.storeDesignationUrl, csrf: config.csrf,

        init() {
            this.deptQuery = (this.departments.find(d => String(d.id) === String(this.selectedDepartmentId)) || {}).name || '';
            this.catQuery = (this.categories.find(c => String(c.id) === String(this.selectedCategoryId)) || {}).name || '';
            this.typeQuery = (this.contactTypes.find(t => String(t.id) === String(this.selectedContactType)) || {}).name || '';
            const matchedDesig = this.designations.find(d => String(d.id) === String(this.selectedDesignationId));
            this.desigQuery = matchedDesig?.name || this.selectedDesignationName || '';
            if (matchedDesig) this.selectedDesignationName = matchedDesig.name;

            const matchedCompany = this.companies.find(c => String(c.id) === String(this.selectedCompanyId));
            if (matchedCompany) {
                this.companyQuery = matchedCompany.name;
                this.selectedCompanyName = matchedCompany.name;
            } else if (config.companyName) {
                this.companyQuery = config.companyName;
                this.selectedCompanyName = config.companyName;
            }

            const matchedClass = this.classifications.find(c => String(c.id) === String(this.selectedClassificationId));
            if (matchedClass) {
                this.classificationQuery = matchedClass.name;
            }

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
            return list.filter((item) => String(mapper(item) || '').toLowerCase().startsWith(q));
        },

        containsFilter(list, query, mapper = (x) => x) {
            const q = String(query || '').toLowerCase().trim();
            if (!q) return list;
            return list.filter((item) => String(mapper(item) || '').toLowerCase().includes(q));
        },

        get filteredDepartments() { return this.startsWithFilter(this.departments, this.deptQuery, o => o.name); },
        get filteredCategories() { return this.startsWithFilter(this.categories, this.catQuery, o => o.name); },
        get filteredTypes() { return this.startsWithFilter(this.contactTypes, this.typeQuery, o => o.name); },
        get filteredDesignations() { return this.startsWithFilter(this.designations, this.desigQuery, o => o.name); },
        get filteredCompanies() { return this.containsFilter(this.companies, this.companyQuery, o => o.name); },
        get filteredClassifications() { return this.containsFilter(this.classifications, this.classificationQuery, o => o.name); },
        get filteredCountries() { return this.startsWithFilter(this.countries, this.countryQuery); },
        get filteredStates() { return this.startsWithFilter(this.states, this.stateQuery); },
        get filteredCities() { return this.startsWithFilter(this.stateCities[this.state] || [], this.cityQuery); },
        get isOtherDesignation() { return String(this.selectedDesignationName || '').toLowerCase() === 'other'; },

        selectDepartment(opt) { this.selectedDepartmentId = opt ? opt.id : ''; this.deptQuery = opt ? opt.name : ''; this.deptOpen = false; },
        selectCategory(opt) { this.selectedCategoryId = opt ? opt.id : ''; this.catQuery = opt ? opt.name : ''; this.catOpen = false; },
        selectType(opt) { this.selectedContactType = opt ? opt.id : ''; this.typeQuery = opt ? opt.name : ''; this.typeOpen = false; },
        selectCompany(opt) {
            this.selectedCompanyId = opt ? String(opt.id) : '';
            this.selectedCompanyName = opt ? opt.name : '';
            this.companyQuery = opt ? opt.name : '';
            this.companyOpen = false;
        },
        onCompanyInput() {
            if (!this.companyQuery.trim()) {
                this.selectedCompanyId = '';
                this.selectedCompanyName = '';
            }
        },
        normalizeWebsite() {
            let val = String(this.companyWebsite || '').trim();
            if (val && !/^https?:\/\//i.test(val)) {
                val = 'https://' + val;
            }
            this.companyWebsite = val;
        },
        selectClassification(opt) {
            this.selectedClassificationId = opt ? String(opt.id) : '';
            this.classificationQuery = opt ? opt.name : '';
            this.classOpen = false;
        },
        onClassificationInput() {
            if (!this.classificationQuery.trim()) {
                this.selectedClassificationId = '';
            }
        },
        selectDesignation(opt) {
            this.selectedDesignationId = opt ? String(opt.id) : '';
            this.selectedDesignationName = opt ? opt.name : '';
            this.desigQuery = opt ? opt.name : '';
            this.desigOpen = false;
            if (!opt || String(opt.name).toLowerCase() !== 'other') {
                this.otherDesignation = '';
            }
        },
        effectiveDesignation() {
            if (this.isOtherDesignation) {
                const custom = String(this.otherDesignation || '').trim();
                return custom !== '' ? custom : 'Other';
            }
            return this.selectedDesignationName || '';
        },
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
            return ({
                department: 'Create Department',
                category: 'Create Category',
                type: 'Create Contact Type',
                designation: 'Create Designation',
            })[this.createKind] || 'Create';
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
                const url = this.createKind === 'department'
                    ? this.storeDeptUrl
                    : (this.createKind === 'category' ? this.storeCatUrl : this.storeDesignationUrl);
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
                } else if (this.createKind === 'category') {
                    const opt = { id: String(data.category.id), name: data.category.name };
                    this.categories.push(opt);
                    this.categories.sort((a, b) => a.name.localeCompare(b.name));
                    this.selectCategory(opt);
                } else if (this.createKind === 'designation') {
                    const item = data.item || data.designation || {};
                    const opt = { id: String(item.id), name: item.name || this.createName };
                    if (!this.designations.some(d => String(d.id) === String(opt.id))) {
                        this.designations.push(opt);
                        this.designations.sort((a, b) => a.name.localeCompare(b.name));
                    }
                    this.selectDesignation(opt);
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
