@php
    $selectedSupplier = (string) ($selected ?? '');
@endphp
<div class="relative" id="supplier-picker">
    <select name="supplier_id" id="supplier_id" class="sr-only" onchange="handleSupplierChange(this.value); syncSupplierSearchLabel()">
        <option value="">Select supplier</option>
        @foreach($suppliers as $s)
            <option value="{{ $s->id }}" data-credit-days="{{ $s->credit_days ?? 0 }}" @selected($selectedSupplier === (string) $s->id)>{{ $s->name }}</option>
        @endforeach
    </select>
    <input type="text"
           id="supplier_search"
           autocomplete="off"
           placeholder="Type to search supplier..."
           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
           onfocus="openSupplierSearch()"
           oninput="filterSupplierSearch()"
           onkeydown="supplierSearchKeydown(event)">
    <div id="supplier_search_list" class="absolute z-40 left-0 right-0 mt-1 hidden max-h-60 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl"></div>
</div>
<script>
function supplierOptions() {
    const select = document.getElementById('supplier_id');
    return select ? [...select.options].filter((option) => option.value) : [];
}

function syncSupplierSearchLabel() {
    const select = document.getElementById('supplier_id');
    const input = document.getElementById('supplier_search');
    if (!select || !input) return;
    const selected = select.selectedOptions[0];
    input.value = selected && selected.value ? selected.textContent.trim() : '';
}

function renderSupplierSearch(query) {
    const list = document.getElementById('supplier_search_list');
    if (!list) return;
    const needle = (query || '').trim().toLowerCase();
    const matches = supplierOptions().filter((option) => !needle || option.textContent.toLowerCase().includes(needle)).slice(0, 40);
    list.innerHTML = '';
    if (!matches.length) {
        list.innerHTML = '<div class="px-3 py-2 text-xs text-slate-500">No matching supplier</div>';
    } else {
        matches.forEach((option) => {
            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'block w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-indigo-50';
            row.textContent = option.textContent.trim();
            row.addEventListener('mousedown', (event) => {
                event.preventDefault();
                pickSupplier(option.value);
            });
            list.appendChild(row);
        });
    }
    list.classList.remove('hidden');
}

function openSupplierSearch() {
    const input = document.getElementById('supplier_search');
    renderSupplierSearch(input ? input.value : '');
}

function filterSupplierSearch() {
    const select = document.getElementById('supplier_id');
    const input = document.getElementById('supplier_search');
    if (select && input) {
        const exact = supplierOptions().find((option) => option.textContent.trim().toLowerCase() === input.value.trim().toLowerCase());
        select.value = exact ? exact.value : '';
    }
    renderSupplierSearch(input ? input.value : '');
}

function pickSupplier(id) {
    const select = document.getElementById('supplier_id');
    const list = document.getElementById('supplier_search_list');
    if (!select) return;
    select.value = id;
    select.dispatchEvent(new Event('change', { bubbles: true }));
    if (list) list.classList.add('hidden');
}

function supplierSearchKeydown(event) {
    const list = document.getElementById('supplier_search_list');
    if (!list || list.classList.contains('hidden')) return;
    const buttons = [...list.querySelectorAll('button')];
    const current = buttons.findIndex((button) => button.classList.contains('bg-indigo-50'));
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        const next = event.key === 'ArrowDown'
            ? Math.min(current + 1, buttons.length - 1)
            : Math.max(current - 1, 0);
        buttons.forEach((button) => button.classList.remove('bg-indigo-50'));
        if (buttons[next]) buttons[next].classList.add('bg-indigo-50');
    }
    if (event.key === 'Enter') {
        const choice = buttons[current] || buttons[0];
        if (choice) {
            event.preventDefault();
            choice.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
        }
    }
    if (event.key === 'Escape' && list) {
        list.classList.add('hidden');
    }
}

document.addEventListener('click', (event) => {
    const picker = document.getElementById('supplier-picker');
    const list = document.getElementById('supplier_search_list');
    if (picker && list && !picker.contains(event.target)) {
        list.classList.add('hidden');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    syncSupplierSearchLabel();
    const form = document.getElementById('supplier_id')?.closest('form');
    form?.addEventListener('submit', (event) => {
        const select = document.getElementById('supplier_id');
        const input = document.getElementById('supplier_search');
        if (select && !select.value) {
            event.preventDefault();
            if (input) {
                input.setCustomValidity('Select a supplier from the list.');
                input.reportValidity();
            }
        } else if (input) {
            input.setCustomValidity('');
        }
    });
});
</script>
