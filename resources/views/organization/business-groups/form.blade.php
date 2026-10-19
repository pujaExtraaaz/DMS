@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Business Group' : 'Add Business Group')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">{{ $item->exists ? 'Edit Business Group' : 'Add Business Group' }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">{{ $item->exists ? 'Update business group profile and associated entities' : 'Create a new corporate sister concern group' }}</p>
        </div>
        <a href="{{ route('organization.business-groups.index') }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition shadow-2xs">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Groups</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <form method="POST"
              action="{{ $item->exists ? route('organization.business-groups.update', $item) : route('organization.business-groups.store') }}"
              class="divide-y divide-slate-100">
            @csrf
            @if($item->exists)
                @method('PUT')
            @endif

            <div class="p-6 space-y-6">
                {{-- Section A: Group Details --}}
                <div>
                    <div class="flex items-center justify-between pb-2 mb-4 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">Section A</span>
                            <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700">Group Details</h4>
                        </div>
                        <span class="text-[11px] text-slate-400 font-normal">* Indicates required fields</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {{-- 1. Group Name --}}
                        <div class="space-y-1">
                            <label for="name" class="block text-xs font-semibold text-slate-700">Group Name <span class="text-red-500">*</span></label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $item->name) }}"
                                   required
                                   placeholder="e.g., Avit Digital Group"
                                   class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs @error('name') border-red-300 ring-1 ring-red-400 bg-red-50/20 @enderror">
                            @error('name')
                                <p class="text-[11px] text-red-600 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 2. Group Code --}}
                        <div class="space-y-1">
                            <label for="code" class="block text-xs font-semibold text-slate-700">Group Code <span class="text-red-500">*</span></label>
                            <input type="text"
                                   id="code"
                                   name="code"
                                   value="{{ old('code', $item->code) }}"
                                   required
                                   placeholder="e.g., ADG-01"
                                   class="w-full rounded-xl border-slate-200 text-xs font-mono uppercase focus:border-purple-500 focus:ring-purple-500 shadow-2xs @error('code') border-red-300 ring-1 ring-red-400 bg-red-50/20 @enderror">
                            @error('code')
                                <p class="text-[11px] text-red-600 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 3. Parent Company --}}
                        <div class="space-y-1">
                            <label for="parent_company_id" class="block text-xs font-semibold text-slate-700">Parent Company <span class="text-red-500">*</span></label>
                            <select id="parent_company_id"
                                    name="parent_company_id"
                                    required
                                    class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs @error('parent_company_id') border-red-300 ring-1 ring-red-400 bg-red-50/20 @enderror">
                                <option value="">-- Select Parent Company --</option>
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}" @selected(old('parent_company_id', $item->parent_company_id) == $c->id)>
                                        {{ $c->name }} ({{ $c->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_company_id')
                                <p class="text-[11px] text-red-600 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 4. Incorporation Date --}}
                        <div class="space-y-1">
                            <label for="incorporation_date" class="block text-xs font-semibold text-slate-700">Incorporation Date</label>
                            <input type="date"
                                   id="incorporation_date"
                                   name="incorporation_date"
                                   value="{{ old('incorporation_date', $item->incorporation_date?->format('Y-m-d')) }}"
                                   class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                        </div>

                        {{-- 5. Headquarters --}}
                        <div class="space-y-1">
                            <label for="headquarters" class="block text-xs font-semibold text-slate-700">Headquarters (HQ)</label>
                            <input type="text"
                                   id="headquarters"
                                   name="headquarters"
                                   value="{{ old('headquarters', $item->headquarters) }}"
                                   placeholder="e.g., Mumbai, Maharashtra"
                                   class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                        </div>

                        {{-- 6. Country --}}
                        <div class="space-y-1">
                            <label for="country" class="block text-xs font-semibold text-slate-700">Country</label>
                            <input type="text"
                                   id="country"
                                   name="country"
                                   value="{{ old('country', $item->country ?? 'India') }}"
                                   placeholder="e.g., India"
                                   class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                        </div>

                        {{-- 7. Industry --}}
                        <div class="space-y-1">
                            <label for="industry" class="block text-xs font-semibold text-slate-700">Industry</label>
                            <input type="text"
                                   id="industry"
                                   name="industry"
                                   value="{{ old('industry', $item->industry) }}"
                                   placeholder="e.g., Technology & Electronics"
                                   class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                        </div>

                        {{-- 8. Website --}}
                        <div class="space-y-1">
                            <label for="website" class="block text-xs font-semibold text-slate-700">Website</label>
                            <input type="text"
                                   id="website"
                                   name="website"
                                   value="{{ old('website', $item->website) }}"
                                   placeholder="e.g., https://avitdigital.com"
                                   class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs @error('website') border-red-300 ring-1 ring-red-400 bg-red-50/20 @enderror">
                            @error('website')
                                <p class="text-[11px] text-red-600 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 9. Email --}}
                        <div class="space-y-1">
                            <label for="email" class="block text-xs font-semibold text-slate-700">Email</label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   value="{{ old('email', $item->email) }}"
                                   placeholder="e.g., corporate@avitdigital.com"
                                   class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs @error('email') border-red-300 ring-1 ring-red-400 bg-red-50/20 @enderror">
                            @error('email')
                                <p class="text-[11px] text-red-600 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 10. Phone --}}
                        <div class="space-y-1">
                            <label for="phone" class="block text-xs font-semibold text-slate-700">Phone</label>
                            <input type="text"
                                   id="phone"
                                   name="phone"
                                   value="{{ old('phone', $item->phone) }}"
                                   placeholder="e.g., +91 98765 43210"
                                   class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                        </div>

                        {{-- 11. Group Logo (Emoji) --}}
                        <div class="space-y-1">
                            <label for="logo_emoji" class="block text-xs font-semibold text-slate-700">Group Logo (Emoji)</label>
                            <input type="text"
                                   id="logo_emoji"
                                   name="logo_emoji"
                                   value="{{ old('logo_emoji', $item->logo_emoji ?? '🏢') }}"
                                   placeholder="e.g., 🏢"
                                   class="w-full rounded-xl border-slate-200 text-xs font-emoji focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                        </div>

                        {{-- 12. Status --}}
                        <div class="space-y-1">
                            <label for="is_active" class="block text-xs font-semibold text-slate-700">Status</label>
                            <select id="is_active"
                                    name="is_active"
                                    class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">
                                <option value="1" @selected(old('is_active', $item->is_active ?? true))>Active</option>
                                <option value="0" @selected(!old('is_active', $item->is_active ?? true))>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Section B: Description & Notes --}}
                <div class="pt-2">
                    <div class="flex items-center gap-2 pb-2 mb-4 border-b border-slate-100">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">Section B</span>
                        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700">Description & Notes</h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="description" class="block text-xs font-semibold text-slate-700">Description</label>
                            <textarea id="description"
                                      name="description"
                                      rows="3"
                                      placeholder="Brief summary or portfolio profile..."
                                      class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">{{ old('description', $item->description) }}</textarea>
                        </div>

                        <div class="space-y-1">
                            <label for="notes" class="block text-xs font-semibold text-slate-700">Notes</label>
                            <textarea id="notes"
                                      name="notes"
                                      rows="3"
                                      placeholder="Internal remarks or governance notes..."
                                      class="w-full rounded-xl border-slate-200 text-xs focus:border-purple-500 focus:ring-purple-500 shadow-2xs">{{ old('notes', $item->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Linked Companies / Sister Concerns --}}
                <div class="pt-2">
                    <div class="flex items-center justify-between pb-2 mb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded">Entities</span>
                            <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700">Linked Sister Concerns</h4>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-48 overflow-y-auto p-3 rounded-xl border border-slate-200 bg-slate-50/50">
                        @foreach($companies as $c)
                            <label class="flex items-center gap-2 text-xs p-1.5 rounded-lg hover:bg-white transition cursor-pointer select-none">
                                <input type="checkbox"
                                       name="company_ids[]"
                                       value="{{ $c->id }}"
                                       @checked(in_array($c->id, old('company_ids', $selectedCompanies)))
                                       class="rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                                <span class="font-medium text-slate-700 truncate">{{ $c->name }}</span>
                                <span class="text-[10px] font-mono text-slate-400 ml-auto">{{ $c->code }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 flex items-center justify-end gap-2.5">
                <a href="{{ route('organization.business-groups.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 text-xs font-semibold shadow-xs transition">
                    {{ $item->exists ? 'Update Group' : 'Add Group' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
