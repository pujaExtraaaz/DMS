@extends('layouts.dms')
@section('title', 'Contact Details: ' . $contact->contact_name)

@section('content')
@include('phone_directory._styles')
<div class="max-w-4xl mx-auto space-y-5">

    {{-- Breadcrumb & Navigation --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ route('phone-directory.index') }}" class="hover:text-violet-600 transition">Phone Directory</a>
            <span>/</span>
            <span class="font-medium text-slate-900">{{ $contact->contact_name }}</span>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('phone-directory.contacts.edit', $contact) }}"
                class="btn-pd-primary inline-flex items-center justify-center gap-1.5 rounded-xl bg-purple-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-purple-700 active:bg-purple-800 transition min-h-[34px]"
            >
                <svg class="h-4 w-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                <span>Edit Contact</span>
            </a>

            <a
                href="{{ route('phone-directory.index') }}"
                class="btn-pd-secondary inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-900 shadow-xs hover:bg-slate-50 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/20 transition min-h-[34px]"
            >
                Back to List
            </a>
        </div>
    </div>

    {{-- Profile Hero Card --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        {{-- Hero Banner --}}
        <div class="bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-700 px-6 py-8 text-white relative">
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                @if($contact->profile_photo_url)
                    <img
                        src="{{ $contact->profile_photo_url }}"
                        alt="{{ $contact->contact_name }}"
                        class="h-24 w-24 rounded-full object-cover border-4 border-white/90 shadow-xl shrink-0"
                    >
                @else
                    <div class="h-24 w-24 rounded-full flex items-center justify-center text-2xl font-bold border-4 border-white/90 shadow-xl bg-white/20 text-white shrink-0">
                        {{ $contact->initials }}
                    </div>
                @endif

                <div class="flex-1 text-center sm:text-left space-y-1">
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                        <h1 class="text-2xl font-bold">{{ $contact->contact_name }}</h1>
                        @if($contact->is_important)
                            <span class="rounded-full bg-amber-400/30 border border-amber-300 px-2 py-0.5 text-xs font-semibold text-amber-200">
                                ★ Important
                            </span>
                        @endif
                        @if($contact->isArchived())
                            <span class="rounded-full bg-rose-500/30 border border-rose-300 px-2 py-0.5 text-xs font-semibold text-rose-200">
                                Archived
                            </span>
                        @endif
                    </div>

                    <p class="text-sm text-violet-100 font-medium">{{ $contact->designation ?: 'Professional Contact' }}</p>

                    @if($contact->company_name)
                        <p class="text-sm font-semibold text-white/90 flex items-center justify-center sm:justify-start gap-1.5">
                            <svg class="h-4 w-4 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            <span>{{ $contact->company_name }}</span>
                        </p>
                    @endif

                    <div class="pt-3 flex flex-wrap items-center justify-center sm:justify-start gap-2">
                        @if($contact->mobile_number)
                            <a
                                href="tel:{{ $contact->clean_mobile }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-1.5 text-xs font-bold text-emerald-700 shadow-md hover:bg-emerald-50 transition"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                                <span>Call Primary</span>
                            </a>

                            <a
                                href="https://wa.me/{{ $contact->clean_whatsapp }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-500 px-3.5 py-1.5 text-xs font-bold text-white shadow-md hover:bg-emerald-600 transition"
                            >
                                <span>WhatsApp</span>
                            </a>
                        @endif

                        @if($contact->email)
                            <a
                                href="mailto:{{ $contact->email }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-white/20 px-3.5 py-1.5 text-xs font-bold text-white shadow-md hover:bg-white/30 transition"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                                <span>Send Email</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Details Body --}}
        <div class="p-6 space-y-6">
            {{-- Contact Information Grid --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Contact Information</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Primary Mobile</span>
                        <a href="tel:{{ $contact->clean_mobile }}" class="text-sm font-semibold text-slate-900 hover:text-violet-600">{{ $contact->mobile_number }}</a>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Alternate Mobile</span>
                        <span class="text-sm font-medium text-slate-800">{{ $contact->alternate_mobile ?: '—' }}</span>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Office Phone</span>
                        <span class="text-sm font-medium text-slate-800">
                            {{ $contact->office_phone ? $contact->office_phone . ($contact->extension_number ? ' (Ext: ' . $contact->extension_number . ')' : '') : '—' }}
                        </span>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">WhatsApp Number</span>
                        <span class="text-sm font-medium text-slate-800">{{ $contact->whatsapp_number ?: ($contact->mobile_number ?: '—') }}</span>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Primary Email</span>
                        @if($contact->email)
                            <a href="mailto:{{ $contact->email }}" class="text-sm font-semibold text-slate-900 hover:text-violet-600 truncate block">{{ $contact->email }}</a>
                        @else
                            <span class="text-sm text-slate-400">—</span>
                        @endif
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Alternate Email</span>
                        <span class="text-sm font-medium text-slate-800 truncate block">{{ $contact->alternate_email ?: '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Organization & Categorization --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Organization & Master Details</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Contact Type</span>
                        <span class="text-xs font-semibold text-slate-800">
                            {{ \App\Domains\PhoneDirectory\Models\PhoneDirectoryContact::CONTACT_TYPES[$contact->contact_type] ?? ucfirst($contact->contact_type) }}
                        </span>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Department</span>
                        <span class="text-xs font-semibold text-slate-800">{{ $contact->department?->name ?? '—' }}</span>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Category</span>
                        <span class="text-xs font-semibold text-slate-800">{{ $contact->category?->name ?? '—' }}</span>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Status</span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold {{ $contact->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ ucfirst($contact->status ?? 'active') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Location & Address --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Location Details</h3>
                <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 space-y-2">
                    @if($contact->address)
                        <p class="text-xs text-slate-800 font-medium">{{ $contact->address }}</p>
                    @endif
                    <div class="flex flex-wrap gap-4 text-xs text-slate-600">
                        @if($contact->area) <span><strong>Area:</strong> {{ $contact->area }}</span> @endif
                        @if($contact->city) <span><strong>City:</strong> {{ $contact->city }}</span> @endif
                        @if($contact->state) <span><strong>State:</strong> {{ $contact->state }}</span> @endif
                        @if($contact->pincode) <span><strong>PIN:</strong> {{ $contact->pincode }}</span> @endif
                        @if($contact->country) <span><strong>Country:</strong> {{ $contact->country }}</span> @endif
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            @if($contact->notes)
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Notes & Remarks</h3>
                    <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-4 text-xs text-slate-800 whitespace-pre-wrap">
                        {{ $contact->notes }}
                    </div>
                </div>
            @endif

            {{-- Audit Trail Metadata --}}
            <div class="border-t border-slate-100 pt-4 flex flex-wrap items-center justify-between text-[11px] text-slate-400">
                <div>
                    Created: {{ $contact->created_at ? $contact->created_at->format('d M Y, h:i A') : '—' }}
                    @if($contact->creator) by {{ $contact->creator->name }} @endif
                </div>
                <div>
                    Last Updated: {{ $contact->updated_at ? $contact->updated_at->format('d M Y, h:i A') : '—' }}
                    @if($contact->updater) by {{ $contact->updater->name }} @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
