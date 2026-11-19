@extends('layouts.dms')
@section('title', 'Cheque Book Management')

@section('content')
<div class="space-y-6" x-data="{
    showAddModal: false,
    showEditModal: false,
    showCancelLeafModal: false,
    editingBook: null,
    cancellingBook: null,
    cancelChequeNo: '',
    cancelReason: '',

    // Add Form state
    fromNumber: '000001',
    toNumber: '000025',
    get totalAddCheques() {
        const from = parseInt(this.fromNumber, 10);
        const to = parseInt(this.toNumber, 10);
        return (!isNaN(from) && !isNaN(to) && to >= from) ? (to - from + 1) : 0;
    },

    openCancelLeaf(book) {
        this.cancellingBook = book;
        this.cancelChequeNo = book.next_available || '';
        this.cancelReason = '';
        this.showCancelLeafModal = true;
    },

    openEdit(book) {
        this.editingBook = book;
        this.showEditModal = true;
    }
}">
    {{-- Page Header --}}
    <x-ui.page-header
        title="Cheque Book Management"
        description="Maintain cheque books, configure number ranges, and track leaf utilization for each company bank account."
    >
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('cheques.index')">
                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                Cheque Register
            </x-ui.button>
            <x-ui.button variant="primary" @click="showAddModal = true">
                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Cheque Book
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    {{-- Summary Cards (Section 2 & 7) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Books</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-slate-900">{{ number_format($stats['total_books']) }}</span>
                <span class="text-xs font-medium text-slate-400">Books</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Leaves</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-indigo-700">{{ number_format($stats['total_leaves']) }}</span>
                <span class="text-xs font-medium text-indigo-400">Leaves</span>
            </div>
        </div>

        <div class="rounded-xl border border-blue-200 bg-blue-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Used Leaves</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-blue-900">{{ number_format($stats['total_used']) }}</span>
                <span class="text-xs font-medium text-blue-600">Issued</span>
            </div>
        </div>

        <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">Cancelled / Void</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-rose-900">{{ number_format($stats['total_cancelled']) }}</span>
                <span class="text-xs font-medium text-rose-600">Spoiled</span>
            </div>
        </div>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Remaining</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-emerald-900">{{ number_format($stats['total_remaining']) }}</span>
                <span class="text-xs font-medium text-emerald-600">Available</span>
            </div>
        </div>

        <div class="rounded-xl border border-purple-200 bg-purple-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-purple-700">Utilization</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-purple-900">{{ $stats['utilization_percent'] }}%</span>
                <span class="text-xs font-medium text-purple-600">Used</span>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <x-ui.card>
        <form method="GET" action="{{ route('cheque-books.index') }}" class="flex flex-col sm:flex-row flex-wrap items-center gap-3">
            <div class="w-full sm:w-64">
                <select name="bank_account_no" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Company Bank Accounts</option>
                    @foreach($bankAccounts as $acc)
                        @php $no = $acc['bank_account_no'] ?? ''; @endphp
                        @if(filled($no))
                            <option value="{{ $no }}" @selected($selectedAccount === $no)>
                                {{ $acc['bank_name'] ?: 'Bank' }} — {{ $no }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-40">
                <select name="status" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Statuses</option>
                    <option value="active" @selected($statusFilter === 'active')>Active Only</option>
                    <option value="inactive" @selected($statusFilter === 'inactive')>Inactive Only</option>
                </select>
            </div>

            <div class="flex-1 min-w-[200px] w-full">
                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search by book name, cheque range, bank, remarks..."
                    class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                >
            </div>

            <div class="flex items-center gap-2">
                <x-ui.button type="submit" variant="secondary" size="sm">Filter</x-ui.button>
                @if($selectedAccount || $statusFilter || $search)
                    <a href="{{ route('cheque-books.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">Reset</a>
                @endif
            </div>
        </form>

        {{-- Cheque Books Table --}}
        <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50/80 text-slate-700 font-semibold">
                    <tr>
                        <th class="px-3.5 py-2.5 text-left">Bank Account</th>
                        <th class="px-3.5 py-2.5 text-left">Cheque Book Name</th>
                        <th class="px-3.5 py-2.5 text-left">Number Range</th>
                        <th class="px-3.5 py-2.5 text-center">Leaves</th>
                        <th class="px-3.5 py-2.5 text-left">Utilization</th>
                        <th class="px-3.5 py-2.5 text-center">Next Available</th>
                        <th class="px-3.5 py-2.5 text-center">Issue Date</th>
                        <th class="px-3.5 py-2.5 text-center">Status</th>
                        <th class="px-3.5 py-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $book)
                        @php
                            $used = $book->used_count;
                            $cancelled = $book->cancelled_count;
                            $remaining = $book->remaining_count;
                            $utilization = $book->utilization_percent;
                            $nextNo = $book->next_available_cheque_number;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-3.5 py-2.5">
                                <div class="font-medium text-slate-900">{{ $book->bank_name ?: 'Bank' }}</div>
                                <div class="font-mono text-[11px] text-slate-500">{{ $book->bank_account_no }}</div>
                            </td>
                            <td class="px-3.5 py-2.5">
                                <div class="font-semibold text-slate-800">{{ $book->cheque_book_name ?: 'Cheque Book' }}</div>
                                @if($book->remarks)
                                    <div class="text-[11px] text-slate-400 truncate max-w-xs" title="{{ $book->remarks }}">{{ $book->remarks }}</div>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5">
                                <span class="font-mono font-bold text-slate-800">{{ $book->from_number }}</span>
                                <span class="text-slate-400 mx-1">→</span>
                                <span class="font-mono font-bold text-slate-800">{{ $book->to_number }}</span>
                            </td>
                            <td class="px-3.5 py-2.5 text-center">
                                <div class="flex items-center justify-center gap-1 font-mono text-[11px]">
                                    <span class="font-bold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded" title="Total Leaves">{{ $book->total_cheques }}</span>
                                    <span class="text-slate-300">/</span>
                                    <span class="font-semibold text-blue-700 bg-blue-50 px-1 py-0.5 rounded" title="Used Leaves">{{ $used }}U</span>
                                    <span class="font-semibold text-rose-700 bg-rose-50 px-1 py-0.5 rounded" title="Cancelled Leaves">{{ $cancelled }}C</span>
                                    <span class="font-semibold text-emerald-700 bg-emerald-50 px-1 py-0.5 rounded" title="Remaining Leaves">{{ $remaining }}R</span>
                                </div>
                            </td>
                            <td class="px-3.5 py-2.5 min-w-[130px]">
                                <div class="flex items-center justify-between text-[11px] mb-1">
                                    <span class="font-medium text-slate-600">{{ $remaining }} left</span>
                                    <span class="font-bold {{ $utilization >= 90 ? 'text-rose-600' : 'text-slate-700' }}">{{ $utilization }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-1.5 rounded-full {{ $utilization >= 90 ? 'bg-rose-500' : ($utilization >= 60 ? 'bg-amber-500' : 'bg-indigo-600') }}" style="width: {{ $utilization }}%"></div>
                                </div>
                            </td>
                            <td class="px-3.5 py-2.5 text-center font-mono font-bold">
                                @if($nextNo)
                                    <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 text-xs">
                                        {{ $nextNo }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Exhausted</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5 text-center text-slate-600">
                                {{ $book->issue_date ? $book->issue_date->format('d M Y') : '—' }}
                            </td>
                            <td class="px-3.5 py-2.5 text-center">
                                <form method="POST" action="{{ route('organization.cheque-books.toggle-status', $book) }}" class="inline">
                                    @csrf
                                    <button type="submit" title="Click to toggle status" class="inline-flex items-center">
                                        @if($book->status === 'active')
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 border border-slate-200 hover:bg-slate-200 transition">
                                                Inactive
                                            </span>
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td class="px-3.5 py-2.5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($remaining > 0)
                                        <button
                                            type="button"
                                            @click="openCancelLeaf({
                                                id: {{ $book->id }},
                                                name: '{{ addslashes($book->cheque_book_name ?: 'Book') }}',
                                                range: '{{ $book->from_number }} - {{ $book->to_number }}',
                                                next_available: '{{ $nextNo }}',
                                                available: {{ Illuminate\Support\Js::from($book->availableChequeNumbers()) }}
                                            })"
                                            class="inline-flex items-center rounded bg-rose-50 border border-rose-200 px-2 py-0.5 text-[11px] font-semibold text-rose-700 hover:bg-rose-100 transition"
                                            title="Void / Cancel an unused leaf"
                                        >
                                            Void Leaf
                                        </button>
                                    @endif

                                    <button
                                        type="button"
                                        @click="openEdit({
                                            id: {{ $book->id }},
                                            cheque_book_name: '{{ addslashes($book->cheque_book_name ?? '') }}',
                                            bank_account_no: '{{ $book->bank_account_no }}',
                                            bank_name: '{{ addslashes($book->bank_name ?? '') }}',
                                            from_number: '{{ $book->from_number }}',
                                            to_number: '{{ $book->to_number }}',
                                            issue_date: '{{ $book->issue_date ? $book->issue_date->format('Y-m-d') : '' }}',
                                            status: '{{ $book->status }}',
                                            remarks: '{{ addslashes($book->remarks ?? '') }}'
                                        })"
                                        class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-200 transition"
                                    >
                                        Edit
                                    </button>

                                    @if($used === 0)
                                        <form method="POST" action="{{ route('organization.cheque-books.destroy', $book) }}" class="inline" onsubmit="return confirm('Delete cheque book range {{ $book->from_number }} - {{ $book->to_number }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-rose-600 hover:bg-rose-100 transition" title="Delete Cheque Book">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-500">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <svg class="mx-auto h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    <p class="font-medium text-slate-700">No cheque books configured yet.</p>
                                    <p class="text-[11px] text-slate-500">Add a cheque book for your company bank accounts to track number ranges and leaf utilization.</p>
                                    <button type="button" @click="showAddModal = true" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                                        + Add First Cheque Book
                                    </button>
                                </div>
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

    {{-- Add Cheque Book Modal --}}
    <div
        x-show="showAddModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div
            @click.outside="showAddModal = false"
            class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl space-y-4"
        >
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Add New Cheque Book</h3>
                    <p class="text-xs text-slate-500">Configure cheque leaf number range for a bank account.</p>
                </div>
                <button type="button" @click="showAddModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('organization.cheque-books.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="company_id" value="{{ $company->id }}">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Company Bank Account <span class="text-rose-500">*</span></label>
                    <select name="bank_account_no" required class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                        <option value="">-- Select Bank Account --</option>
                        @foreach($bankAccounts as $acc)
                            @php $no = $acc['bank_account_no'] ?? ''; @endphp
                            @if(filled($no))
                                <option value="{{ $no }}">
                                    {{ $acc['bank_name'] ?: 'Bank' }} — {{ $no }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque Book Name / Ref</label>
                        <input type="text" name="cheque_book_name" placeholder="e.g. HDFC Main A/C" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Bank Name</label>
                        <input type="text" name="bank_name" placeholder="e.g. HDFC Bank" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Starting Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="from_number" x-model="fromNumber" required placeholder="000001" class="w-full font-mono rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ending Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="to_number" x-model="toNumber" required placeholder="000025" class="w-full font-mono rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Total Leaves</label>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-xs font-bold font-mono text-indigo-700" x-text="totalAddCheques + ' leaves'">
                            25 leaves
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Issue Date</label>
                        <input type="date" name="issue_date" value="{{ now()->toDateString() }}" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                        <select name="status" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                            <option value="active">Active (Available for issuance)</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks</label>
                    <textarea name="remarks" rows="2" placeholder="Optional reference notes..." class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showAddModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 shadow-2xs">Save Cheque Book</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Cheque Book Modal --}}
    <div
        x-show="showEditModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div
            @click.outside="showEditModal = false"
            class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl space-y-4"
        >
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Edit Cheque Book</h3>
                    <p class="text-xs text-slate-500">Update cheque book details and status.</p>
                </div>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>

            <form
                method="POST"
                :action="`{{ url('organization/cheque-books') }}/${editingBook?.id}`"
                class="space-y-4"
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="company_id" value="{{ $company->id }}">
                <input type="hidden" name="bank_account_no" :value="editingBook?.bank_account_no">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque Book Name</label>
                        <input type="text" name="cheque_book_name" x-model="editingBook.cheque_book_name" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Bank Name</label>
                        <input type="text" name="bank_name" x-model="editingBook.bank_name" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Starting Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="from_number" x-model="editingBook.from_number" required class="w-full font-mono rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ending Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="to_number" x-model="editingBook.to_number" required class="w-full font-mono rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Issue Date</label>
                        <input type="date" name="issue_date" x-model="editingBook.issue_date" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                        <select name="status" x-model="editingBook.status" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks</label>
                    <textarea name="remarks" rows="2" x-model="editingBook.remarks" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showEditModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 shadow-2xs">Update Cheque Book</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Cancel / Void Leaf Modal --}}
    <div
        x-show="showCancelLeafModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div
            @click.outside="showCancelLeafModal = false"
            class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl space-y-4"
        >
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-rose-900">Void / Cancel Cheque Leaf</h3>
                    <p class="text-xs text-slate-500" x-text="`Book: ${cancellingBook?.name} (${cancellingBook?.range})`"></p>
                </div>
                <button type="button" @click="showCancelLeafModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>

            <form
                method="POST"
                :action="`{{ url('organization/cheque-books') }}/${cancellingBook?.id}/cancel-leaf`"
                class="space-y-4"
            >
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque Number to Void <span class="text-rose-500">*</span></label>
                    <template x-if="cancellingBook?.available?.length > 0">
                        <select name="cheque_number" x-model="cancelChequeNo" required class="w-full font-mono rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-rose-500 focus:border-rose-500 bg-white">
                            <template x-for="num in cancellingBook.available" :key="num">
                                <option :value="num" x-text="num"></option>
                            </template>
                        </select>
                    </template>
                    <template x-if="!cancellingBook?.available?.length">
                        <input type="text" name="cheque_number" x-model="cancelChequeNo" required class="w-full font-mono rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-rose-500 focus:border-rose-500">
                    </template>
                    <p class="text-[11px] text-slate-500 mt-1">This leaf will be marked as spoiled/cancelled and cannot be issued.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Cancellation</label>
                    <textarea name="reason" rows="2" x-model="cancelReason" placeholder="e.g. Leaf torn, printing error, spoiled..." class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-rose-500 focus:border-rose-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showCancelLeafModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-rose-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 shadow-2xs">Confirm Leaf Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
