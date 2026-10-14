@extends('layouts.dms')
@section('title', 'Financial Years')
@section('content')
<div id="listing-container" data-dynamic-container x-data="financialYearIndex()">
    <x-ui.page-header title="Financial Years">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('organization.financial-years.create')">+ Add Financial Year</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search financial year name, company..."
            :reset-url="route('organization.financial-years.index')"
        />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Name</x-ui.sortable-th>
                        <x-ui.sortable-th column="company" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Company</x-ui.sortable-th>
                        <x-ui.sortable-th column="starts_on" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Date Range</x-ui.sortable-th>
                        <x-ui.sortable-th column="is_current" :current-sort="$sort ?? 'starts_on'" :current-direction="$direction ?? 'desc'">Status</x-ui.sortable-th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ $item->name }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->company?->name }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $item->starts_on?->format('d M Y') }} – {{ $item->ends_on?->format('d M Y') }}</td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="$item->is_current ? 'emerald' : ($item->is_closed ? 'gray' : 'blue')">
                                    {{ $item->is_current ? 'Current' : ($item->is_closed ? 'Closed' : 'Open') }}
                                </x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-ui.button variant="secondary" size="sm" :href="route('organization.financial-years.edit', $item)" title="Edit Financial Year" aria-label="Edit Financial Year">Edit</x-ui.button>
                                    @unless($item->is_current)
                                        <form method="POST" action="{{ route('organization.financial-years.set-current', $item) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-indigo-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 shadow-xs" onclick="return confirm('Switch current period to {{ $item->name }}?')">Set Current</button>
                                        </form>
                                    @endunless
                                    @if($item->is_closed)
                                        <form method="POST" action="{{ route('organization.financial-years.reopen', $item) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-amber-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 shadow-xs" onclick="return confirm('Re-open {{ $item->name }}?')">Re-open</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('organization.financial-years.close', $item) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-red-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-red-700 shadow-xs" onclick="return confirm('Close {{ $item->name }}? No back-dated postings will be allowed.')">Close</button>
                                        </form>
                                    @endif
                                    <button type="button"
                                            @click="promptDelete('{{ $item->id }}', '{{ addslashes($item->name) }}', '{{ addslashes($item->company?->name ?? '—') }}', '{{ $item->starts_on?->format('d M Y') }}', '{{ $item->ends_on?->format('d M Y') }}')"
                                            class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-xs px-2.5 py-1.5 text-xs"
                                            title="Delete Financial Year"
                                            aria-label="Delete Financial Year"
                                            data-delete-financial-year="{{ $item->id }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-slate-500" data-empty>
                                <x-ui.empty-state title="No financial years found" description="Try adjusting your search query." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true"
         @keydown.escape.window="cancelDelete()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
                 @click="cancelDelete()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Dialog panel -->
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <div class="bg-white p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10 text-red-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Delete Financial Year?</h3>
                            <div class="mt-2 space-y-3">
                                <div class="bg-slate-50 rounded-lg p-3 border border-slate-100 space-y-1.5 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 font-medium">Financial Year:</span>
                                        <span class="text-slate-800 font-semibold" x-text="deletingFyName"></span>
                                    </div>
                                    <div class="flex items-center justify-between" x-show="deletingCompanyName && deletingCompanyName !== '—'">
                                        <span class="text-slate-500 font-medium">Associated Company:</span>
                                        <span class="text-slate-800 font-semibold" x-text="deletingCompanyName"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 font-medium">Start Date:</span>
                                        <span class="text-slate-800 font-semibold" x-text="deletingStartsOn"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 font-medium">End Date:</span>
                                        <span class="text-slate-800 font-semibold" x-text="deletingEndsOn"></span>
                                    </div>
                                </div>
                                <div class="rounded-lg bg-amber-50 p-3 border border-amber-200">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-4 w-4 text-amber-600" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-2.5">
                                            <p class="text-xs text-amber-800 leading-relaxed">
                                                Deleting this financial year may affect accounting records associated with this period. Please confirm that you want to continue.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 flex flex-row-reverse gap-2.5 border-t border-slate-100">
                    <button type="button"
                            @click="executeDelete()"
                            :disabled="isDeleting"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isDeleting">Delete Financial Year</span>
                        <span x-show="isDeleting" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Deleting...
                        </span>
                    </button>
                    <button type="button"
                            @click="cancelDelete()"
                            :disabled="isDeleting"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div x-show="notification.show"
         x-cloak
         x-transition
         class="fixed bottom-4 right-4 z-50 max-w-sm rounded-lg p-4 shadow-lg text-white text-sm"
         :class="notification.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
        <div class="flex items-center gap-2">
            <span x-text="notification.message"></span>
            <button type="button" @click="notification.show = false" class="ml-auto text-white/80 hover:text-white">&times;</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function financialYearIndex() {
    return {
        showDeleteModal: false,
        isDeleting: false,
        deletingFyId: null,
        deletingFyName: '',
        deletingCompanyName: '',
        deletingStartsOn: '',
        deletingEndsOn: '',
        notification: {
            show: false,
            type: 'success',
            message: '',
        },

        promptDelete(id, name, companyName, startsOn, endsOn) {
            this.deletingFyId = id;
            this.deletingFyName = name;
            this.deletingCompanyName = companyName || '—';
            this.deletingStartsOn = startsOn || '—';
            this.deletingEndsOn = endsOn || '—';
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingFyId = null;
            this.deletingFyName = '';
            this.deletingCompanyName = '';
            this.deletingStartsOn = '';
            this.deletingEndsOn = '';
        },

        async executeDelete() {
            if (!this.deletingFyId || this.isDeleting) return;
            this.isDeleting = true;

            const url = `{{ url('organization/financial-years') }}/${this.deletingFyId}`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showDeleteModal = false;
                    this.showNotification('success', data.message || 'Financial year deleted successfully.');

                    const rows = document.querySelectorAll('#listing-container tbody tr:not([data-empty])');
                    const currentUrl = new URL(window.location.href);
                    let currentPage = parseInt(currentUrl.searchParams.get('page') || '1', 10);

                    if (rows.length <= 1 && currentPage > 1) {
                        currentUrl.searchParams.set('page', currentPage - 1);
                        window.history.replaceState(null, '', currentUrl.toString());
                    }

                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to delete financial year.');
                    this.showDeleteModal = false;
                }
            } catch (err) {
                console.error('Delete error:', err);
                this.showNotification('error', 'A network error occurred while deleting the financial year.');
                this.showDeleteModal = false;
            } finally {
                this.isDeleting = false;
                this.deletingFyId = null;
            }
        },

        async refreshList() {
            const container = document.querySelector('#listing-container');
            if (!container) return;
            const currentUrl = new URL(window.location.href);

            try {
                const refreshRes = await fetch(currentUrl.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-DMS-Dynamic': '1',
                    }
                });

                if (refreshRes.ok) {
                    const html = await refreshRes.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newContainer = doc.querySelector('#listing-container');
                    if (newContainer) {
                        container.innerHTML = newContainer.innerHTML;
                        window.history.replaceState(null, '', currentUrl.toString());
                        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                            window.Alpine.initTree(container);
                        }
                    } else {
                        window.location.href = currentUrl.toString();
                    }
                } else {
                    window.location.href = currentUrl.toString();
                }
            } catch (e) {
                console.error('Refresh list error:', e);
                window.location.href = currentUrl.toString();
            }
        },

        showNotification(type, message) {
            this.notification = { show: true, type, message };
            setTimeout(() => {
                this.notification.show = false;
            }, 6000);
        }
    };
}
</script>
@endpush
@endsection
