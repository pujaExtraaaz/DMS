<?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-3">
            <h3 class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2">Receipt Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Receipt Note No(s)</label>
                    <input type="text" name="receipt_note_nos" value="<?php echo e(old('receipt_note_nos', $receiptNoteNos ?? '')); ?>" maxlength="255"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Not applicable if none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Receipt Doc No.</label>
                    <input type="text" name="receipt_doc_no" value="<?php echo e(old('receipt_doc_no', $receiptDocNo ?? '')); ?>" maxlength="100"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Dispatched Through</label>
                    <input type="text" name="dispatched_through" value="<?php echo e(old('dispatched_through', $dispatchedThrough ?? '')); ?>" maxlength="150"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Carrier Name / Agent</label>
                    <input type="text" name="carrier_name" value="<?php echo e(old('carrier_name', $carrierName ?? '')); ?>" maxlength="150"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Bill of Lading / LR-RR No.</label>
                    <input type="text" name="lr_number" value="<?php echo e(old('lr_number', $lrNumber ?? '')); ?>" maxlength="100"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">LR Date</label>
                    <input type="date" name="lr_date" value="<?php echo e(old('lr_date', $lrDate ?? '')); ?>"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Motor Vehicle No.</label>
                    <input type="text" name="vehicle_number" value="<?php echo e(old('vehicle_number', $vehicleNumber ?? '')); ?>" maxlength="50"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
        </div>
        <div class="space-y-3">
            <h3 class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2">Order Details</h3>
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Order No(s)</label>
                    <input type="text" name="order_nos" x-model="orderNos" maxlength="255"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Mode / Terms of Payment</label>
                    <input type="text" name="payment_terms" value="<?php echo e(old('payment_terms', $paymentTerms ?? '')); ?>" maxlength="255"
                           placeholder="E.G. 15"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Other References</label>
                    <input type="text" name="other_references" value="<?php echo e(old('other_references', $otherReferences ?? '')); ?>" maxlength="255"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Terms of Delivery</label>
                    <input type="text" name="terms_of_delivery" value="<?php echo e(old('terms_of_delivery', $termsOfDelivery ?? '')); ?>" maxlength="255"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $attributes = $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $component = $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>

<?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

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
            <input type="text" name="freight_notes" value="<?php echo e(old('freight_notes', $freightNotes ?? '')); ?>" maxlength="1000"
                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                   placeholder="E.G. Transporter Name, Terms (Paid/To-Pay), Dispatch Terms...">
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $attributes = $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $component = $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
<?php /**PATH D:\xampp\htdocs\DMS\resources\views/purchasing/invoices/partials/dispatch-and-freight.blade.php ENDPATH**/ ?>