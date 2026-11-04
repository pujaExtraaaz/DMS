<?php
    $initialBillingId = $initialBillingId ?? '';
    $initialBillingText = $initialBillingText ?? '';
    $initialDeliveryId = $initialDeliveryId ?? '';
    $initialShippingText = $initialShippingText ?? '';
?>


<div id="supplier-addresses-card" class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-4">
    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Party Details</span>
        <span id="address-status-text" class="text-xs text-slate-500"></span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
            <div class="flex items-center justify-between gap-2">
                <label class="block text-xs font-semibold text-slate-700 tracking-wider">Supplier (Bill From)</label>
                <span class="text-[11px] text-indigo-600 font-medium">Auto From Vendor</span>
            </div>
            <input type="hidden" name="billing_address_id" id="billing_address_id" value="<?php echo e($initialBillingId); ?>">
            <textarea name="billing_address" id="billing_address" rows="2" readonly
                      class="hidden"><?php echo e($initialBillingText); ?></textarea>
            <div id="billing-details-panel" class="rounded-md border border-slate-100 bg-slate-50 px-3 py-2 text-xs text-slate-700 space-y-1.5 min-h-[8.5rem]">
                <p class="text-slate-400 italic" id="billing-empty-hint">Select a vendor to load billing details.</p>
                <dl id="billing-details-list" class="hidden space-y-1">
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">Supplier</dt><dd id="billing_party_name" class="font-medium text-slate-900"></dd></div>
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">Address Type</dt><dd id="billing_address_type"></dd></div>
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">Mailing Name</dt><dd id="billing_mailing_name"></dd></div>
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">Address</dt><dd id="billing_address_lines" class="whitespace-pre-line"></dd></div>
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">State</dt><dd id="billing_state"></dd></div>
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">Pincode</dt><dd id="billing_pincode"></dd></div>
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">GSTIN/UIN</dt><dd id="billing_gstin"></dd></div>
                </dl>
            </div>
        </div>

        
        <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
            <div class="flex items-center justify-between gap-2">
                <label class="block text-xs font-semibold text-slate-700 tracking-wider">Consignee (Ship To)</label>
                <button type="button" id="btn-new-delivery-address"
                        class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100">
                    + Create New
                </button>
            </div>
            <select name="company_delivery_address_id" id="company_delivery_address_id" onchange="handleDeliveryChange()"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select Delivery Address</option>
            </select>
            <textarea name="shipping_address" id="shipping_address" rows="2" readonly
                      class="hidden"><?php echo e($initialShippingText); ?></textarea>
            <div id="delivery-details-panel" class="rounded-md border border-slate-100 bg-slate-50 px-3 py-2 text-xs text-slate-700 space-y-1.5 min-h-[8.5rem]">
                <p class="text-slate-400 italic" id="delivery-empty-hint">Choose a delivery address, or create a new one.</p>
                <dl id="delivery-details-list" class="hidden space-y-1">
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">Consignee</dt><dd id="delivery_party_name" class="font-medium text-slate-900"></dd></div>
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">Address Type</dt><dd id="delivery_address_type"></dd></div>
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">Mailing Name</dt><dd id="delivery_mailing_name"></dd></div>
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">Address</dt><dd id="delivery_address_lines" class="whitespace-pre-line"></dd></div>
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">State</dt><dd id="delivery_state"></dd></div>
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">Country</dt><dd id="delivery_country"></dd></div>
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">Pincode</dt><dd id="delivery_pincode"></dd></div>
                    <div class="grid grid-cols-[7.5rem_1fr] gap-2"><dt class="text-slate-500">GSTIN/UIN</dt><dd id="delivery_gstin"></dd></div>
                </dl>
            </div>
        </div>
    </div>
</div>


<div id="delivery-address-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4" aria-hidden="true">
    <div class="w-full max-w-lg rounded-xl bg-white shadow-xl border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h3 class="text-sm font-semibold text-slate-900">Create Delivery Address</h3>
            <button type="button" class="text-slate-400 hover:text-slate-600" onclick="closeDeliveryAddressModal()" aria-label="Close">&times;</button>
        </div>
        <div class="px-4 py-3 space-y-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Address Type <span class="text-rose-500">*</span></label>
                <input type="text" id="new_delivery_address_type" placeholder="E.g. Delivery Address Office"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Mailing Name</label>
                <input type="text" id="new_delivery_mailing_name" placeholder="Company mailing name"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Address <span class="text-rose-500">*</span></label>
                <textarea id="new_delivery_address" rows="3" placeholder="Full delivery address"
                          class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">State</label>
                    <input type="text" id="new_delivery_state" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Pincode</label>
                    <input type="text" id="new_delivery_pincode" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Country</label>
                    <input type="text" id="new_delivery_country" value="India" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">GSTIN/UIN</label>
                    <input type="text" id="new_delivery_gstin" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                <input type="checkbox" id="new_delivery_is_default" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Set as default delivery address
            </label>
            <p id="new-delivery-error" class="hidden text-xs text-rose-600"></p>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-200 px-4 py-3">
            <button type="button" onclick="closeDeliveryAddressModal()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
            <button type="button" onclick="saveDeliveryAddress()" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700">Save Address</button>
        </div>
    </div>
</div>
<?php /**PATH D:\xampp\htdocs\dms\resources\views/purchasing/orders/partials/party-details.blade.php ENDPATH**/ ?>