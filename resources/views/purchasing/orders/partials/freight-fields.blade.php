@php
    $freightOn = (string) ($freightApplicable ?? '0') === '1' || ($freightApplicable ?? false) === true || ($freightApplicable ?? null) === 1;
    $freightMode = old('freight_mode', $freightMode ?? 'amount');
    if (! in_array($freightMode, ['amount', 'percent'], true)) {
        $freightMode = 'amount';
    }
@endphp
<div id="freight_fields_container" style="{{ $freightOn ? 'display: block;' : 'display: none;' }}" class="space-y-4 pt-1">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Freight basis</label>
            <select name="freight_mode" id="po_freight_mode" onchange="toggleFreightMode(); recalculatePoTotals();" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="percent" @selected($freightMode === 'percent')>Percentage (%)</option>
                <option value="amount" @selected($freightMode === 'amount')>Fixed amount (₹)</option>
            </select>
            <p class="mt-1 text-[11px] text-slate-500">Percentage is calculated on the item total before GST.</p>
        </div>
        <div id="freight_percent_wrap" style="{{ $freightMode === 'percent' ? '' : 'display: none;' }}">
            <label class="block text-xs font-medium text-slate-700 mb-1">Freight (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="freight_percent" id="po_freight_percent"
                   value="{{ old('freight_percent', $freightPercent ?? '0.00') }}"
                   oninput="recalculatePoTotals()"
                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.50">
            <p class="mt-1 text-[11px] text-slate-500">Calculated freight: <strong id="freight_calculated_amount">₹0.00</strong></p>
        </div>
        <div id="freight_amount_wrap" style="{{ $freightMode === 'amount' ? '' : 'display: none;' }}">
            <label class="block text-xs font-medium text-slate-700 mb-1">Freight amount (₹)</label>
            <input type="number" step="0.01" min="0" name="freight_amount" id="po_freight_amount"
                   value="{{ old('freight_amount', $freightAmount ?? '0.00') }}"
                   oninput="recalculatePoTotals()"
                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Freight tax rate (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="freight_tax_rate" id="po_freight_tax_rate"
                   value="{{ old('freight_tax_rate', $freightTaxRate ?? '0.00') }}"
                   oninput="recalculatePoTotals()"
                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Freight tax amount (₹)</label>
            <input type="number" step="0.01" min="0" name="freight_tax_amount" id="po_freight_tax_amount"
                   value="{{ old('freight_tax_amount', $freightTaxAmount ?? '0.00') }}" readonly
                   class="block w-full rounded-lg border-gray-200 bg-slate-100 text-slate-600 text-sm cursor-not-allowed">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Freight total (₹)</label>
            <input type="number" step="0.01" min="0" name="freight_total" id="po_freight_total"
                   value="{{ old('freight_total', $freightTotal ?? '0.00') }}" readonly
                   class="block w-full rounded-lg border-gray-200 bg-slate-100 text-slate-700 font-semibold text-sm cursor-not-allowed">
        </div>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-700 mb-1">Freight notes / transporter details (optional)</label>
        <input type="text" name="freight_notes" id="po_freight_notes"
               value="{{ old('freight_notes', $freightNotes ?? '') }}"
               maxlength="1000"
               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="E.G. Transporter Name, Terms (Paid/To-Pay), Dispatch Terms...">
    </div>
</div>
