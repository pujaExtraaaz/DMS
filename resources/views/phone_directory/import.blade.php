@extends('layouts.dms')
@section('title', 'Import Contacts - Phone Directory')

@section('content')
@include('phone_directory._styles')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Breadcrumb & Title --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ route('phone-directory.index') }}" class="hover:text-violet-600 transition">Phone Directory</a>
            <span>/</span>
            <span class="font-medium text-slate-900">Import Contacts</span>
        </div>

        <a href="{{ route('phone-directory.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            <span>Back to Directory</span>
        </a>
    </div>

    {{-- Import Summary Banner (if just completed) --}}
    @if(isset($summary))
        <div class="rounded-2xl border border-violet-200 bg-violet-50/70 p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-violet-600 text-white font-bold">✓</span>
                    <div>
                        <h3 class="text-sm font-bold text-violet-950">Bulk Import Completed</h3>
                        <p class="text-xs text-violet-700">Processed {{ $summary['total_processed'] }} records in total.</p>
                    </div>
                </div>

                <a href="{{ route('phone-directory.index') }}" class="btn-pd-primary inline-flex items-center gap-1.5 rounded-xl bg-purple-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-purple-700 active:bg-purple-800">
                    <span>View Directory</span>
                </a>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 pt-2">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-center">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 block">Created</span>
                    <span class="text-xl font-bold text-emerald-800">{{ $summary['created'] }}</span>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-center">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 block">Updated</span>
                    <span class="text-xl font-bold text-blue-800">{{ $summary['updated'] }}</span>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Skipped</span>
                    <span class="text-xl font-bold text-slate-800">{{ $summary['skipped'] }}</span>
                </div>
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-center">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 block">Failed</span>
                    <span class="text-xl font-bold text-rose-800">{{ $summary['failed'] }}</span>
                </div>
            </div>

            @if(!empty($errorFile) && $summary['failed'] > 0)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 flex items-center justify-between">
                    <div class="text-xs text-amber-800">
                        <strong>{{ $summary['failed'] }} record(s)</strong> failed validation. Download the detailed error report CSV to review.
                    </div>
                    <a href="{{ route('phone-directory.import.errors', ['file' => $errorFile]) }}" class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        <span>Download Error CSV</span>
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- Step-by-Step Card --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-4">
            <h2 class="text-sm font-bold text-slate-900">Upload Contact List (CSV or Excel)</h2>
            <p class="text-xs text-slate-500">Download our sample spreadsheet, fill in your contacts, and upload.</p>
        </div>

        <div class="p-6 space-y-6">

            {{-- Step 1: Download Template --}}
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/40 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-violet-600 text-xs font-bold text-white">1</span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Download Standard Template</h3>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('phone-directory.import.template', ['format' => 'xlsx']) }}" class="btn-pd-secondary inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-900 shadow-2xs hover:bg-slate-50 hover:border-slate-400 transition">
                            <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <span>Excel Template (.xlsx)</span>
                        </a>

                        <a href="{{ route('phone-directory.import.template', ['format' => 'csv']) }}" class="btn-pd-secondary inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-900 shadow-2xs hover:bg-slate-50 hover:border-slate-400 transition">
                            <svg class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                            <span>CSV Template (.csv)</span>
                        </a>
                    </div>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed">
                    The template includes standard header columns: <code>Contact Name</code>, <code>Contact Type</code>, <code>Primary Mobile</code>, <code>Email</code>, <code>Department</code>, <code>Company</code>, <code>Address</code>, etc., along with sample rows.
                </p>
            </div>

            {{-- Step 2: Upload Form --}}
            <form method="POST" action="{{ route('phone-directory.import.process') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-violet-600 text-xs font-bold text-white">2</span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Select File & Duplicate Strategy</h3>
                    </div>

                    {{-- File Input --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Upload File (CSV, XLS, XLSX up to 10MB)</label>
                        <input
                            type="file"
                            name="file"
                            required
                            accept=".csv,.txt,.xlsx,.xls"
                            class="w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-violet-600 file:text-white hover:file:bg-violet-700 cursor-pointer border border-slate-300 rounded-xl p-1 bg-white"
                        >
                    </div>

                    {{-- Duplicate Handling Strategy --}}
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 space-y-2">
                        <span class="text-xs font-semibold text-slate-800 block">Duplicate Record Detection</span>
                        <p class="text-[11px] text-slate-500">If a contact with the same Primary Mobile number or Email already exists in the directory:</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <label class="flex items-start gap-2.5 p-3 rounded-lg border border-slate-200 bg-white hover:border-violet-300 cursor-pointer">
                                <input type="radio" name="duplicate_strategy" value="skip" checked class="mt-0.5 text-violet-600 focus:ring-violet-500">
                                <div>
                                    <span class="text-xs font-bold text-slate-900 block">Skip Duplicates</span>
                                    <span class="text-[11px] text-slate-500">Keep the existing contact unchanged and do not create duplicate.</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-2.5 p-3 rounded-lg border border-slate-200 bg-white hover:border-violet-300 cursor-pointer">
                                <input type="radio" name="duplicate_strategy" value="update" class="mt-0.5 text-violet-600 focus:ring-violet-500">
                                <div>
                                    <span class="text-xs font-bold text-slate-900 block">Update Existing</span>
                                    <span class="text-[11px] text-slate-500">Overwrite details of matching contact with newly imported row.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="pt-2 flex justify-end">
                    <button
                        type="submit"
                        class="btn-pd-primary inline-flex items-center justify-center gap-2 rounded-xl bg-purple-600 px-6 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-purple-700 active:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition min-h-[38px]"
                    >
                        <svg class="h-4 w-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                        <span>Import Contacts Now</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
