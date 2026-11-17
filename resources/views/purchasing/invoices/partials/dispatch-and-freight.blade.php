<x-ui.card>
    <div class="space-y-4">
        <h3 class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2">Receipt / Courier Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Courier / Docket Number</label>
                <input type="text" name="receipt_doc_no" value="{{ old('receipt_doc_no', $receiptDocNo ?? ($courierDocketNo ?? '')) }}" maxlength="100"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. DKT-12345">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Courier Name</label>
                <input type="text" name="dispatched_through" value="{{ old('dispatched_through', $dispatchedThrough ?? ($courierName ?? '')) }}" maxlength="150"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Blue Dart, DTDC">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Motor Vehicle No.</label>
                <input type="text" name="vehicle_number" value="{{ old('vehicle_number', $vehicleNumber ?? '') }}" maxlength="50"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. MH-12-AB-1234">
            </div>
        </div>
    </div>
</x-ui.card>

<x-ui.card>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h3 class="text-sm font-semibold text-slate-800">Freight Charges</h3>
            <p class="text-xs text-slate-500">Fixed amount or a percentage of the item total before GST.</p>
        </div>
        <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-700">Freight Applicable?</label>
            <select name="is_freight_applicable" x-model="freightApplicable" @change="recalc()"
                    class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select>
        </div>
    </div>
    <div x-show="freightApplicable === '1'" x-cloak class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Freight basis</label>
                <select name="freight_mode" x-model="freightMode" @change="recalc()"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="amount">Fixed amount (₹)</option>
                    <option value="percent">Percentage (%)</option>
                </select>
                <p class="mt-1 text-[11px] text-slate-500">Percentage is calculated on the item total before GST.</p>
            </div>
            <div x-show="freightMode === 'percent'">
                <label class="block text-xs font-medium text-slate-700 mb-1">Freight (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="freight_percent" x-model.number="freightPercent" @input="recalc()"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.50">
            </div>
            <div x-show="freightMode === 'amount'">
                <label class="block text-xs font-medium text-slate-700 mb-1">Freight amount (₹)</label>
                <input type="number" step="0.01" min="0" name="freight_amount" x-model.number="freightAmount" @input="recalc()"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Freight tax rate (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="freight_tax_rate" x-model.number="freightTaxRate" @input="recalc()"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Freight tax amount (₹)</label>
                <input type="text" readonly :value="Number(freightTaxAmount || 0).toFixed(2)"
                       class="block w-full rounded-lg border-gray-200 bg-slate-100 text-slate-600 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Freight total (₹)</label>
                <input type="text" readonly :value="Number(freightTotal || 0).toFixed(2)"
                       class="block w-full rounded-lg border-gray-200 bg-slate-100 text-slate-700 font-semibold text-sm">
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Freight notes / transporter details (optional)</label>
            <input type="text" name="freight_notes" value="{{ old('freight_notes', $freightNotes ?? '') }}" maxlength="1000"
                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                   placeholder="E.G. Transporter Name, Terms (Paid/To-Pay), Dispatch Terms...">
        </div>
    </div>
</x-ui.card>
