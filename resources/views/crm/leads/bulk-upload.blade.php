@extends('layouts.dms')
@section('title', 'CRM Leads: Bulk Upload')

@section('content')
@php
    $alpineRows = [];
    if (!empty($previewData['rows'])) {
        foreach ($previewData['rows'] as $r) {
            $searchTerms = [
                $r['row_number'] ?? '',
                $r['status'] ?? '',
                $r['data']['name'] ?? '',
                $r['data']['company_name'] ?? '',
                $r['data']['mobile'] ?? '',
                $r['data']['secondary_mobile'] ?? '',
                $r['data']['email'] ?? '',
                $r['data']['secondary_email'] ?? '',
                $r['salesperson_display'] ?? '',
                $r['subcategory_display'] ?? '',
                $r['data']['city'] ?? '',
                $r['data']['state'] ?? '',
                implode(' ', $r['errors'] ?? []),
                implode(' ', $r['duplicate_reasons'] ?? []),
            ];
            $alpineRows[] = [
                'row_number' => $r['row_number'],
                'status' => $r['status'],
                'search_text' => strtolower(implode(' ', array_filter($searchTerms))),
            ];
        }
    }
@endphp

<div class="space-y-6 max-w-7xl mx-auto" x-data="leadBulkUploadPreview(@js($alpineRows))">
    <x-ui.page-header 
        title="CRM Leads: Bulk Upload" 
        description="Upload and import leads in bulk from Excel (.xlsx, .xls) or CSV files. Preview, validate, and check duplicates before saving."
    >
        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('crm.leads.bulk-upload.template', ['format' => 'xlsx']) }}" 
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download Excel Template (.xlsx)
                </a>
                <a href="{{ route('crm.leads.bulk-upload.template', ['format' => 'csv']) }}" 
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download CSV Template
                </a>
                <x-ui.button variant="secondary" :href="route('crm.leads.index')">
                    Back to Leads
                </x-ui.button>
            </div>
        </x-slot>
    </x-ui.page-header>

    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if(!$previewData)
        <!-- Step 1: Upload Form -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <x-ui.card class="lg:col-span-2">
                <form method="POST" action="{{ route('crm.leads.bulk-upload.preview') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-2">Select Spreadsheet File</h3>
                        <p class="text-xs text-slate-500 mb-4">Supported formats: <strong>.xlsx</strong>, <strong>.xls</strong>, or <strong>.csv</strong> (Max size: 20MB).</p>

                        <div class="mt-2 flex justify-center rounded-xl border-2 border-dashed border-slate-300 px-6 pt-8 pb-8 hover:border-indigo-400 transition bg-slate-50/50">
                            <div class="space-y-2 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <div class="flex text-sm text-slate-600 justify-center">
                                    <label for="lead-file-upload" class="relative cursor-pointer rounded-md font-semibold text-indigo-600 focus-within:outline-none hover:text-indigo-500">
                                        <span>Click to browse</span>
                                        <input id="lead-file-upload" name="file" type="file" required accept=".xlsx,.xls,.csv" class="sr-only" onchange="document.getElementById('selected-filename').textContent = this.files[0]?.name || ''">
                                    </label>
                                    <p class="pl-1 text-slate-500">or drag and drop here</p>
                                </div>
                                <p id="selected-filename" class="text-xs font-semibold text-indigo-600 mt-2"></p>
                            </div>
                        </div>
                        @error('file')
                            <p class="mt-2 text-xs text-red-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                        <x-ui.button type="submit" variant="primary">
                            Upload &amp; Preview Leads
                        </x-ui.button>
                        <x-ui.button variant="secondary" :href="route('crm.leads.index')">
                            Cancel
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <!-- Template Guidelines Card -->
            <x-ui.card title="Template Column Guidelines">
                <div class="space-y-3 text-xs text-slate-600">
                    <p class="font-medium text-slate-800">Your spreadsheet must match these header columns:</p>
                    <div class="space-y-1.5 max-h-96 overflow-y-auto pr-1">
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span class="font-semibold text-slate-700">Contact Name</span>
                            <span class="text-red-500 font-medium">Required</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>Company Name</span>
                            <span class="text-slate-400">Optional</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>Title</span>
                            <span class="text-slate-400">e.g. Director</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>Email</span>
                            <span class="text-slate-400">Valid email</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>Secondary Email</span>
                            <span class="text-slate-400">Valid email</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>Mobile</span>
                            <span class="text-slate-400">Primary (min 7 digits)</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>SECND MOB</span>
                            <span class="text-slate-400">Alternate mobile</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>Phone</span>
                            <span class="text-slate-400">Work phone</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>LANDLINE</span>
                            <span class="text-slate-400">Office landline</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>SALES PERSON</span>
                            <span class="text-slate-400">User name or email</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>Tag</span>
                            <span class="text-slate-400">e.g. VIP, Wholesale</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>SUB CATEGORY</span>
                            <span class="text-slate-400">Category name</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>Mailing Street</span>
                            <span class="text-slate-400">Premises/Street</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>Mailing City</span>
                            <span class="text-slate-400">e.g. Mumbai</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>Mailing State</span>
                            <span class="text-slate-400">e.g. Maharashtra</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span>Mailing Zip</span>
                            <span class="text-slate-400">Pincode</span>
                        </div>
                    </div>
                    <div class="pt-2 text-[11px] text-slate-500 italic">
                        Tip: Headers are case-insensitive and tolerate spaces. Download the template above for an exact pre-formatted layout.
                    </div>
                </div>
            </x-ui.card>
        </div>
    @else
        <!-- Step 2: Preview & Validation Table -->
        <div class="space-y-4">
            <!-- KPI Summary Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Rows</span>
                    <div class="mt-1 text-2xl font-bold text-slate-800">{{ $previewData['summary']['total'] }}</div>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 shadow-sm">
                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Valid Rows</span>
                    <div class="mt-1 text-2xl font-bold text-emerald-800">{{ $previewData['summary']['valid'] }}</div>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 shadow-sm">
                    <span class="text-xs font-semibold uppercase tracking-wider text-amber-700">Duplicate Rows</span>
                    <div class="mt-1 text-2xl font-bold text-amber-800">{{ $previewData['summary']['duplicate'] }}</div>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50/60 p-4 shadow-sm">
                    <span class="text-xs font-semibold uppercase tracking-wider text-red-700">Invalid Rows</span>
                    <div class="mt-1 text-2xl font-bold text-red-800">{{ $previewData['summary']['invalid'] }}</div>
                </div>
            </div>

            <!-- Tab Filters & Preview Card -->
            <x-ui.card padding="false">
                <div class="p-4 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">File:</span>
                            <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">{{ $filename }}</span>
                        </div>

                        <!-- Live Search Filter -->
                        <div class="relative w-full sm:w-64">
                            <input type="text"
                                   x-model="searchQuery"
                                   placeholder="Search preview records..."
                                   class="w-full rounded-lg border border-slate-300 bg-white py-1.5 pl-8 pr-3 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 shadow-sm">
                            <svg class="pointer-events-none absolute left-2.5 top-2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>

                    <!-- View Filter Tabs -->
                    <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-50 text-xs font-medium">
                        <button type="button" @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1 rounded-md transition">
                            All Rows ({{ $previewData['summary']['total'] }})
                        </button>
                        <button type="button" @click="activeTab = 'valid'" :class="activeTab === 'valid' ? 'bg-white text-emerald-700 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1 rounded-md transition">
                            Valid Only ({{ $previewData['summary']['valid'] }})
                        </button>
                        <button type="button" @click="activeTab = 'issues'" :class="activeTab === 'issues' ? 'bg-white text-red-800 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1 rounded-md transition">
                            Invalid / Issues ({{ $previewData['summary']['invalid'] }})
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[500px]">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-slate-600">Row</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-600">Contact Name</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-600">Company</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-600">Mobile / SECND</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-600">Email</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-600">Sales Person</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-600">Sub Category</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-600">Address</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-600">Validation / Issues</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($previewData['rows'] as $r)
                                <tr x-show="isRowVisible({{ $r['row_number'] }})" 
                                    @if($loop->iteration > 20) style="display: none; content-visibility: auto;" @else style="content-visibility: auto;" @endif
                                    class="{{ $r['status'] === 'invalid' ? 'bg-red-50/40 hover:bg-red-50/70' : 'hover:bg-slate-50' }}">
                                    <td class="px-3 py-2 font-mono text-slate-500 font-semibold">{{ $r['row_number'] }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if($r['status'] === 'valid')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                                Valid
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-800">
                                                Invalid
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 font-medium text-slate-900">
                                        {{ $r['data']['name'] ?: '—' }}
                                        @if($r['data']['title'])
                                            <span class="text-[10px] text-slate-400">({{ $r['data']['title'] }})</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-700">
                                        {{ $r['data']['company_name'] ?: '—' }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <div>{{ $r['data']['mobile'] ?: '—' }}</div>
                                        @if($r['data']['secondary_mobile'])
                                            <div class="text-[10px] text-slate-400">Sec: {{ $r['data']['secondary_mobile'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-700">
                                        <div>{{ $r['data']['email'] ?: '—' }}</div>
                                        @if($r['data']['secondary_email'])
                                            <div class="text-[10px] text-slate-400">{{ $r['data']['secondary_email'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-700 whitespace-nowrap">
                                        {{ $r['salesperson_display'] }}
                                    </td>
                                    <td class="px-3 py-2 text-slate-700 whitespace-nowrap">
                                        {{ $r['subcategory_display'] }}
                                    </td>
                                    <td class="px-3 py-2 text-slate-500 max-w-xs truncate" title="{{ trim(($r['data']['street'] ? $r['data']['street'].', ' : '').($r['data']['city'] ?? '').' '.($r['data']['state'] ?? '').' '.($r['data']['zip'] ?? '')) }}">
                                        {{ trim(($r['data']['city'] ?? '').' '.($r['data']['state'] ?? '').' '.($r['data']['zip'] ?? '')) ?: ($r['data']['street'] ?: '—') }}
                                    </td>
                                    <td class="px-3 py-2 text-xs">
                                        @if(!empty($r['errors']))
                                            <ul class="text-red-700 font-medium space-y-0.5 list-disc list-inside">
                                                @foreach($r['errors'] as $err)
                                                    <li>{{ $err }}</li>
                                                @endforeach
                                            </ul>
                                        @elseif(!empty($r['duplicate_reasons']))
                                            <ul class="text-amber-800 space-y-0.5 list-disc list-inside">
                                                @foreach($r['duplicate_reasons'] as $dup)
                                                    <li>{{ $dup }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-emerald-700">Ready to import</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr x-show="totalFiltered === 0" x-cloak>
                                <td colspan="10" class="px-4 py-8 text-center text-xs text-slate-500">
                                    No records match the selected filter or search query.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls & Record Summary -->
                <div class="px-4 py-3 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-700">
                    <div class="font-medium text-slate-600">
                        <span x-text="showingText">
                            @php
                                $initialTotal = $previewData['summary']['total'] ?? 0;
                                $initialEnd = min(20, $initialTotal);
                            @endphp
                            Showing {{ $initialTotal > 0 ? 1 : 0 }}–{{ $initialEnd }} of {{ $initialTotal }} records
                        </span>
                    </div>

                    <div class="flex items-center gap-1" x-show="totalPages > 1">
                        <!-- Previous Page Button -->
                        <button type="button" 
                                @click="prevPage()" 
                                :disabled="currentPage <= 1"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-300 bg-white font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            <span>Previous</span>
                        </button>

                        <!-- Desktop Page Number Buttons -->
                        <div class="hidden sm:inline-flex items-center gap-1 px-1">
                            <template x-for="(page, idx) in paginationPages" :key="idx">
                                <div>
                                    <template x-if="page === '...'">
                                        <span class="px-2 py-1 text-slate-400 font-semibold select-none">...</span>
                                    </template>
                                    <template x-if="page !== '...'">
                                        <button type="button"
                                                @click="goToPage(page)"
                                                :class="currentPage === page ? 'bg-indigo-600 text-white font-semibold shadow-sm' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-medium'"
                                                class="min-w-[32px] h-8 px-2 inline-flex items-center justify-center rounded-lg text-xs transition"
                                                x-text="page">
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Mobile Page Indicator -->
                        <div class="sm:hidden px-2 font-medium text-slate-600">
                            Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>
                        </div>

                        <!-- Next Page Button -->
                        <button type="button" 
                                @click="nextPage()" 
                                :disabled="currentPage >= totalPages"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-300 bg-white font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition shadow-sm">
                            <span>Next</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Final Import Controls -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-4">
                    <form method="POST" action="{{ route('crm.leads.bulk-upload.import') }}" class="flex flex-wrap items-center gap-4">
                        @csrf
                        <input type="hidden" name="import_token" value="{{ $importToken }}">

                        <label class="inline-flex items-center gap-2 text-xs text-slate-700 font-medium cursor-pointer">
                            <input type="checkbox" name="skip_duplicates" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Skip duplicate rows and import only new unique leads</span>
                        </label>

                        <x-ui.button type="submit" variant="primary" :disabled="$previewData['summary']['valid'] === 0">
                            Confirm &amp; Import {{ $previewData['summary']['valid'] }} Lead(s)
                        </x-ui.button>
                    </form>

                    <a href="{{ route('crm.leads.index') }}" 
                       class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Cancel Import
                    </a>
                </div>
            </x-ui.card>
        </div>
    @endif
</div>

<script>
function leadBulkUploadPreview(initialRows) {
    return {
        rows: Array.isArray(initialRows) ? initialRows : [],
        activeTab: 'all',
        searchQuery: '',
        currentPage: 1,
        pageSize: 20,

        init() {
            this.$watch('activeTab', () => { this.currentPage = 1; });
            this.$watch('searchQuery', () => { this.currentPage = 1; });
        },

        get filteredRows() {
            const query = (this.searchQuery || '').trim().toLowerCase();
            return this.rows.filter(r => {
                if (this.activeTab === 'valid' && r.status !== 'valid') return false;
                if (this.activeTab === 'issues' && r.status !== 'invalid' && r.status !== 'duplicate') return false;
                if (query && !r.search_text.includes(query)) return false;
                return true;
            });
        },

        get totalFiltered() {
            return this.filteredRows.length;
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.totalFiltered / this.pageSize));
        },

        get pagedRowNumbers() {
            const start = (this.currentPage - 1) * this.pageSize;
            const end = start + this.pageSize;
            const slice = this.filteredRows.slice(start, end);
            return new Set(slice.map(r => r.row_number));
        },

        isRowVisible(rowNumber) {
            return this.pagedRowNumbers.has(rowNumber);
        },

        get showingStart() {
            if (this.totalFiltered === 0) return 0;
            return (this.currentPage - 1) * this.pageSize + 1;
        },

        get showingEnd() {
            return Math.min(this.currentPage * this.pageSize, this.totalFiltered);
        },

        get showingText() {
            if (this.totalFiltered === 0) {
                return 'Showing 0 of 0 records';
            }
            return `Showing ${this.showingStart}–${this.showingEnd} of ${this.totalFiltered} records`;
        },

        goToPage(page) {
            const p = parseInt(page, 10);
            if (!isNaN(p) && p >= 1 && p <= this.totalPages) {
                this.currentPage = p;
            }
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
            }
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
            }
        },

        get paginationPages() {
            const current = this.currentPage;
            const total = this.totalPages;
            if (total <= 7) {
                return Array.from({ length: total }, (_, i) => i + 1);
            }

            const pages = [];
            if (current <= 4) {
                for (let i = 1; i <= 5; i++) pages.push(i);
                pages.push('...');
                pages.push(total);
            } else if (current >= total - 3) {
                pages.push(1);
                pages.push('...');
                for (let i = total - 4; i <= total; i++) pages.push(i);
            } else {
                pages.push(1);
                pages.push('...');
                pages.push(current - 1);
                pages.push(current);
                pages.push(current + 1);
                pages.push('...');
                pages.push(total);
            }
            return pages;
        }
    };
}
</script>
@endsection

