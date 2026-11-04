<?php $__env->startSection('title', 'Tax Invoice (Direct Billing)'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $company = $company ?? \App\Domains\Organization\Models\Company::resolveCurrent() ?? \App\Domains\Organization\Models\Company::query()->first();
    $companyState = $company?->state ?? '';

    $productsJson = $products->map(function ($p) {
        $isTracked = (bool) ($p->isSerialTracked() || $p->productType?->track_serial);
        return [
            'id' => $p->id,
            'name' => $p->name,
            'brand_id' => $p->brand_id,
            'base_uom_id' => $p->base_uom_id,
            'unit' => $p->baseUom?->code ?? 'PCS',
            'selling_price' => (float) $p->selling_price,
            'tax_rate' => (float) ($p->tax_rate ?? 0),
            'tracking_type' => $isTracked ? 'serial' : ($p->tracking_type ?? 'none'),
            'selling_discount_type' => $p->selling_discount_type ?? 'percent',
            'selling_discount_value' => (float) ($p->selling_discount_value ?? 0),
            'hsn_code' => $p->hsn_code ?? '',
        ];
    })->values()->toArray();

    $uomsJson = $uoms->map(fn($u) => [
        'id' => $u->id,
        'code' => $u->code,
        'name' => $u->name,
    ])->values()->toArray();

    $schemesJson = ($schemes ?? collect())->map(fn($s) => [
        'id' => $s->id,
        'code' => $s->code,
        'name' => $s->name,
        'scheme_type' => $s->scheme_type,
        'brand_id' => $s->brand_id,
        'brand_name' => $s->brand?->name,
        'product_ids' => $s->products->pluck('product_id')->filter()->map(fn($id) => (int)$id)->values()->toArray(),
        'starts_on' => optional($s->starts_on)->toDateString(),
        'ends_on' => optional($s->ends_on)->toDateString(),
        'basis' => $s->basis,
        'net_credit_notes' => (bool) $s->net_credit_notes,
        'slabs' => $s->slabs->map(fn($sl) => [
            'from_value' => (float) $sl->from_value,
            'to_value' => $sl->to_value !== null ? (float) $sl->to_value : null,
            'benefit_percent' => (float) $sl->benefit_percent,
            'benefit_amount' => (float) $sl->benefit_amount,
        ])->values()->toArray(),
    ])->values()->toArray();

    $initialOrdersJson = ($initialOrders ?? collect())->values()->toArray();
    $orderJson = $order ? [
        'id' => $order->id,
        'order_no' => $order->order_no,
        'customer_id' => $order->customer_id,
        'salesperson_id' => $order->salesperson_id,
        'credit_days' => $order->customer?->credit_days ?? 0,
        'reference_no' => $order->reference_no,
        'notes' => $order->notes,
        'billing_address_id' => $order->billing_address_id,
        'shipping_address_id' => $order->shipping_address_id,
        'billing_address' => $order->billing_address,
        'shipping_address' => $order->shipping_address,
        'delivery_state' => $order->shippingAddress?->state ?? $order->billingAddress?->state ?? $order->customer?->state,
        'items' => $order->items->map(fn($it) => [
            'product_id' => $it->product_id,
            'uom_id' => $it->uom_id,
            'quantity' => (float) $it->quantity,
            'unit_price' => (float) $it->unit_price,
            'line_total' => (float) $it->line_total,
            'serial_numbers' => is_array($it->serial_numbers) ? implode(', ', $it->serial_numbers) : ($it->serial_numbers ?? ''),
        ])->values()->toArray(),
    ] : null;

    $customersList = $customers->map(function ($c) {
        $phone = filled($c->phone) ? trim((string)$c->phone) : '';
        return [
            'id' => $c->id,
            'name' => $c->name,
            'phone' => $phone,
            'label' => $c->name . ($phone !== '' ? ' - ' . $phone : ''),
            'credit_days' => (int) ($c->credit_days ?? 0),
            'state' => $c->shipping_state ?? $c->state ?? '',
            'salesperson_id' => $c->salesperson_id,
        ];
    })->values()->toArray();

    $newProductJson = ($newProduct ?? null) ? [
        'id' => $newProduct->id,
        'name' => $newProduct->name,
        'brand_id' => $newProduct->brand_id,
        'base_uom_id' => $newProduct->base_uom_id,
        'unit' => $newProduct->baseUom?->code ?? 'PCS',
        'selling_price' => (float) $newProduct->selling_price,
        'tax_rate' => (float) ($newProduct->tax_rate ?? 0),
        'tracking_type' => (bool) ($newProduct->isSerialTracked() || $newProduct->productType?->track_serial) ? 'serial' : ($newProduct->tracking_type ?? 'none'),
        'selling_discount_type' => $newProduct->selling_discount_type ?? 'percent',
        'selling_discount_value' => (float) ($newProduct->selling_discount_value ?? 0),
        'hsn_code' => $newProduct->hsn_code ?? '',
    ] : null;

    $savedDraftJson = $savedDraft ?? null;
?>

<?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'Tax Invoice (Direct Billing)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Tax Invoice (Direct Billing)']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

     <?php $__env->slot('actions', null, []); ?> 
        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('invoices.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('invoices.index'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Back <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
     <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal91a231a9270579fa1ae9246bd51fb785)): ?>
<?php $attributes = $__attributesOriginal91a231a9270579fa1ae9246bd51fb785; ?>
<?php unset($__attributesOriginal91a231a9270579fa1ae9246bd51fb785); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal91a231a9270579fa1ae9246bd51fb785)): ?>
<?php $component = $__componentOriginal91a231a9270579fa1ae9246bd51fb785; ?>
<?php unset($__componentOriginal91a231a9270579fa1ae9246bd51fb785); ?>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('invoices.store')); ?>"
      x-data="salesInvoiceForm(
          <?php echo \Illuminate\Support\Js::from($productsJson)->toHtml() ?>,
          <?php echo \Illuminate\Support\Js::from($uomsJson)->toHtml() ?>,
          <?php echo \Illuminate\Support\Js::from($schemesJson)->toHtml() ?>,
          <?php echo \Illuminate\Support\Js::from($customersList)->toHtml() ?>,
          <?php echo \Illuminate\Support\Js::from($orderJson)->toHtml() ?>,
          <?php echo \Illuminate\Support\Js::from($initialOrdersJson)->toHtml() ?>,
          <?php echo \Illuminate\Support\Js::from($companyState)->toHtml() ?>,
          <?php echo \Illuminate\Support\Js::from($newProductJson)->toHtml() ?>,
          <?php echo \Illuminate\Support\Js::from($savedDraftJson)->toHtml() ?>,
          <?php echo \Illuminate\Support\Js::from($targetRow ?? null)->toHtml() ?>
      )"
      x-init="init()"
      x-on:product-quick-added.window="onProductAdded($event.detail)"
      x-on:customer-quick-added.window="onCustomerAdded($event.detail)"
      @submit="clearDraftOnSubmit()"
      class="space-y-6">
    <?php echo csrf_field(); ?>

    
    <div class="rounded-xl border-2 border-amber-400 bg-amber-50 p-4">
        <label class="block text-sm font-extrabold uppercase tracking-wide text-amber-950">Remarks & Additional Delivery</label>
        <p class="mt-1 text-xs font-medium text-amber-800">Put delivery instructions, transport notes, and invoice remarks here. Stays at the top so it is never missed.</p>
        <textarea name="notes" rows="2" placeholder="Delivery instructions and invoice remarks..."
                  x-model="notes"
                  class="mt-2 block w-full rounded-lg border-amber-300 bg-white text-sm font-bold text-slate-900 focus:border-amber-500 focus:ring-amber-500"><?php echo e(old('notes')); ?></textarea>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    
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

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="customer_id" class="block text-xs font-semibold text-slate-700 tracking-wider">Customer / Debtor *</label>
                    <button type="button" @click="$dispatch('open-quick-add-customer')"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1">
                        + Add New
                    </button>
                </div>
                <select name="customer_id" id="customer_id" x-model="customerId" @change="onCustomerChange()" required
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select customer</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $cPhone = filled($c->phone) ? trim((string)$c->phone) : (filled($c->mobile_number) ? trim((string)$c->mobile_number) : '');
                        ?>
                        <option value="<?php echo e($c->id); ?>"
                                data-credit-days="<?php echo e($c->credit_days ?? 0); ?>"
                                data-state="<?php echo e($c->shipping_state ?? ($c->state ?? '')); ?>"
                                <?php if(!empty($cPhone)): ?> data-code="<?php echo e($cPhone); ?>" data-subtext="<?php echo e($cPhone); ?>" <?php elseif(!empty($c->code)): ?> data-code="<?php echo e($c->code); ?>" <?php endif; ?>
                                <?php if(old('customer_id', $order?->customer_id) == $c->id): echo 'selected'; endif; ?>>
                            <?php echo e($c->name); ?>

                        </option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['customer_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div>
                <label for="salesperson_id" class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                    Salesperson
                </label>
                <select name="salesperson_id"
                        id="salesperson_id"
                        x-model="salespersonId"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select Salesperson (Optional)</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $salespersons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($sp->id); ?>" <?php if(old('salesperson_id', $order?->salesperson_id) == $sp->id): echo 'selected'; endif; ?>>
                            <?php echo e($sp->name); ?>

                        </option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['salesperson_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Sales Order (Optional)</label>
                    <span x-show="soLoading" class="text-[11px] text-indigo-600 animate-pulse font-medium inline-flex items-center gap-1" x-cloak>
                        <svg class="animate-spin h-3 w-3 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading...
                    </span>
                </div>
                <select name="order_id"
                        id="order_id"
                        x-model="orderId"
                        @change="onSalesOrderChange()"
                        :disabled="!customerId || soLoading"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed">
                    <option value="">Select Sales Order (Direct Billing if empty)</option>
                    <template x-for="so in customerOrders" :key="so.id">
                        <option :value="so.id" x-text="so.label" :selected="String(so.id) === String(orderId)"></option>
                    </template>
                </select>
                <p x-show="customerId && !soLoading && customerOrders.length === 0" class="mt-1 text-[11px] text-amber-600 font-medium" x-cloak>
                    No eligible sales orders found for this customer
                </p>
                <p x-show="!customerId" class="mt-1 text-[11px] text-slate-400">
                    Select a customer first to load sales orders
                </p>
                <p x-show="selectedOrderLabel && orderId" class="mt-1 text-[11px] text-emerald-600 font-medium" x-cloak>
                    Linked to <span x-text="selectedOrderLabel"></span>
                </p>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['order_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Invoice Date *</label>
                <input type="date"
                       name="invoice_date"
                       id="invoice_date"
                       x-model="invoiceDate"
                       @change="recalcDueDate()"
                       @input="recalcDueDate()"
                       value="<?php echo e(old('invoice_date', date('Y-m-d'))); ?>"
                       required
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['invoice_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Credit Days</label>
                    <span class="text-xs text-slate-500" x-show="dueDatePreview">
                        Due: <span class="font-semibold text-indigo-600" x-text="dueDatePreview"></span>
                    </span>
                </div>
                <input type="number"
                       name="credit_days"
                       min="0"
                       placeholder="0"
                       x-model.number="creditDays"
                       @input="recalcDueDate()"
                       value="<?php echo e(old('credit_days', $order?->customer?->credit_days ?? 0)); ?>"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <input type="hidden" name="due_date" :value="dueDateFormattedYmd">

                <div class="flex items-center gap-1.5 mt-2">
                    <span class="text-[11px] font-medium text-slate-400">Quick:</span>
                    <button type="button"
                            @click="setCreditDays(30)"
                            :class="creditDays === 30 ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'"
                            class="px-2 py-0.5 text-xs font-medium rounded border transition-colors">
                        30 Days
                    </button>
                    <button type="button"
                            @click="setCreditDays(45)"
                            :class="creditDays === 45 ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'"
                            class="px-2 py-0.5 text-xs font-medium rounded border transition-colors">
                        45 Days
                    </button>
                    <button type="button"
                            @click="setCreditDays(50)"
                            :class="creditDays === 50 ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'"
                            class="px-2 py-0.5 text-xs font-medium rounded border transition-colors">
                        50 Days
                    </button>
                </div>
                <p class="mt-1 text-[11px] text-slate-500" x-show="dueDatePreview">
                    Due Date Preview: <strong class="text-slate-700" x-text="dueDatePreview"></strong>
                </p>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['credit_days'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Delivery State</label>
                    <span class="text-[11px] font-semibold"
                          :class="isIntraState() ? 'text-indigo-600' : 'text-emerald-600'"
                          x-text="isIntraState() ? 'Intra-State (CGST + SGST)' : 'Inter-State (IGST)'"></span>
                </div>
                <input type="text"
                       name="delivery_state"
                       x-model="deliveryState"
                       @input="recalc()"
                       placeholder="E.g. Maharashtra"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['delivery_state'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

    
    <div x-show="customerId" x-cloak class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Billing &amp; Delivery Addresses</span>
            <span class="text-xs text-slate-500" x-text="addressesLoading ? 'Loading addresses...' : `${partyAddresses.length} address(es) available`"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Billing Address</label>
                    <span class="text-[11px] text-indigo-600 font-medium">Billed To</span>
                </div>
                <select name="billing_address_id"
                        x-model="selectedBillingId"
                        @change="onBillingAddressChange()"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Default Address</option>
                    <template x-for="addr in partyAddresses" :key="addr.id">
                        <option :value="addr.id" x-text="`${addr.label} — ${addr.full_address}`"></option>
                    </template>
                </select>
                <textarea name="billing_address"
                          x-model="billingSnapshot"
                          rows="3"
                          placeholder="Billing Address Snapshot"
                          class="block w-full rounded-lg border-gray-200 text-xs text-slate-700 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['billing_address_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Delivery / Consignee Address</label>
                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 cursor-pointer font-medium">
                        <input type="checkbox"
                               x-model="sameAsBilling"
                               @change="onSameAsBillingToggle()"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Same as Billing Address</span>
                    </label>
                </div>
                <div x-show="!sameAsBilling">
                    <select name="shipping_address_id"
                            x-model="selectedShippingId"
                            @change="onShippingAddressChange()"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Default Delivery Address</option>
                        <template x-for="addr in partyAddresses" :key="addr.id">
                            <option :value="addr.id" x-text="`${addr.label} — ${addr.full_address}`"></option>
                        </template>
                    </select>
                </div>
                <div x-show="sameAsBilling" class="text-xs text-slate-500 italic py-1">
                    Delivery address matches the selected Billing Address.
                </div>
                <textarea name="shipping_address"
                          x-model="shippingSnapshot"
                          rows="3"
                          placeholder="Delivery Address Snapshot"
                          class="block w-full rounded-lg border-gray-200 text-xs text-slate-700 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['shipping_address_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
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

            <div class="space-y-4">
                
                <div class="pb-3 border-b border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">
                            Delivery Note Details
                        </label>
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer font-medium select-none">
                            <input type="checkbox"
                                   name="delivery_note_na"
                                   value="1"
                                   x-model="deliveryNoteNa"
                                   @change="onDeliveryNoteNaToggle()"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Not Applicable</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                            Delivery Note No(s)
                        </label>
                        <input type="text"
                               name="delivery_note_no"
                               x-model="deliveryNoteNo"
                               :disabled="deliveryNoteNa"
                               :placeholder="deliveryNoteNa ? 'Not Applicable' : 'E.g. DN-2024-001, DN-2024-002'"
                               :class="deliveryNoteNa ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white text-slate-900'"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['delivery_note_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 mb-3">Dispatch Details</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Dispatch Doc No.
                            </label>
                            <input type="text"
                                   name="dispatch_doc_no"
                                   x-model="dispatchDocNo"
                                   placeholder="E.g. DISP-1092"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['dispatch_doc_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Dispatched Through
                            </label>
                            <input type="text"
                                   name="dispatched_through"
                                   x-model="dispatchedThrough"
                                   placeholder="E.g. Road, Blue Dart, Express"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <input type="hidden" name="transport_mode" :value="dispatchedThrough">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['dispatched_through'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Destination
                            </label>
                            <input type="text"
                                   name="destination"
                                   x-model="destination"
                                   placeholder="E.g. Mumbai, Pune Warehouse"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['destination'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Carrier Name / Agent
                            </label>
                            <input type="text"
                                   name="carrier_name"
                                   x-model="carrierName"
                                   placeholder="E.g. VRL Logistics, SafeX"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['carrier_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Bill of Lading / LR-RR No.
                            </label>
                            <input type="text"
                                   name="lr_rr_no"
                                   x-model="lrRrNo"
                                   placeholder="E.g. LR-987654"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['lr_rr_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Bill of Lading / LR-RR Date
                            </label>
                            <input type="date"
                                   name="lr_rr_date"
                                   x-model="lrRrDate"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['lr_rr_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Motor Vehicle No.
                            </label>
                            <input type="text"
                                   name="motor_vehicle_no"
                                   x-model="motorVehicleNo"
                                   placeholder="E.g. MH-12-AB-1234"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <input type="hidden" name="vehicle_no" :value="motorVehicleNo">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['motor_vehicle_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

            <div class="space-y-3.5">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Order Details</h4>
                    <span x-show="selectedOrderLabel" class="text-xs font-semibold text-emerald-600" x-cloak>
                        Auto-linked from <span x-text="selectedOrderLabel"></span>
                    </span>
                </div>

                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                        Order No(s)
                    </label>
                    <input type="text"
                           name="order_no"
                           x-model="orderNo"
                           placeholder="E.g. SO-2024-001 or PO-Client-449"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-[11px] text-slate-400">
                        Populated automatically when a Sales Order is selected, or entered manually for direct billing.
                    </p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['order_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                        Mode / Terms of Payment
                    </label>
                    <input type="text"
                           name="payment_terms"
                           x-model="paymentTerms"
                           placeholder="E.g. Net 30, Advance 50% / Balance on Delivery, Immediate"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['payment_terms'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                        Other References
                    </label>
                    <input type="text"
                           name="other_references"
                           x-model="otherReferences"
                           placeholder="E.g. Customer PO No, Quotation Ref, Tender No"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <input type="hidden" name="reference_no" :value="otherReferences">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['other_references'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                        Terms of Delivery
                    </label>
                    <textarea name="terms_of_delivery"
                              x-model="termsOfDelivery"
                              rows="4"
                              placeholder="Enter delivery terms, instructions, or conditions (supports multiple lines)..."
                              class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 resize-y min-h-[105px] leading-relaxed"><?php echo e(old('terms_of_delivery')); ?></textarea>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Supports detailed delivery instructions, timing, and conditions across multiple lines.
                    </p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['terms_of_delivery'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-rose-600 mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
    </div>

    
    <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['padding' => 'false']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['padding' => 'false']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-3">
                    <h3 class="text-sm font-semibold text-slate-700">Line Items</h3>
                    
                    <div class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-100 p-0.5 text-xs font-semibold">
                        <label class="cursor-pointer px-2.5 py-1 rounded-md transition"
                               :class="priceType === 'exclusive' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900'">
                            <input type="radio" name="price_type" value="exclusive" x-model="priceType" @change="recalc()" class="sr-only">
                            Tax Exclusive
                        </label>
                        <label class="cursor-pointer px-2.5 py-1 rounded-md transition"
                               :class="priceType === 'inclusive' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900'">
                            <input type="radio" name="price_type" value="inclusive" x-model="priceType" @change="recalc()" class="sr-only">
                            Tax Inclusive
                        </label>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    <span x-show="priceType === 'exclusive'">Unit price is taxable base price. Applicable GST is added to calculate total.</span>
                    <span x-show="priceType === 'inclusive'" class="text-indigo-600 font-medium" x-cloak>Unit price already includes GST. Tax is extracted without double taxation.</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('masters.products.create', ['return_to' => 'invoices.create', 'return_url' => route('invoices.create')])); ?>"
                   @click="saveDraftAndRedirectToProduct($event)"
                   class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition inline-flex items-center gap-1">
                    + Quick Add Product
                </a>
                <button type="button" @click="addItem()"
                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                    + Add Item
                </button>
            </div>
        </div>

        <div class="overflow-x-auto border-t border-slate-200">
            <table class="w-full min-w-[1360px] text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-600 tracking-wider border-b border-slate-200 font-semibold text-[11px]">
                    <tr>
                        <th class="p-3 min-w-[340px] lg:min-w-[380px]">Product / Description</th>
                        <th class="p-3 min-w-[85px] w-24 text-center">Unit</th>
                        <th class="p-3 min-w-[110px] w-28 text-right">Qty *</th>
                        <th class="p-3 min-w-[140px] w-36 text-right" x-text="priceType === 'inclusive' ? 'Unit Price (Inc GST) *' : 'Unit Price (₹) *'"></th>
                        <th class="p-3 min-w-[140px] w-36 text-left">Discount</th>
                        <th class="p-3 min-w-[90px] w-24 text-right" x-show="isIntraState()">CGST %</th>
                        <th class="p-3 min-w-[90px] w-24 text-right" x-show="isIntraState()">SGST %</th>
                        <th class="p-3 min-w-[100px] w-28 text-center" x-text="isIntraState() ? 'Total Tax %' : 'IGST %'"></th>
                        <th class="p-3 min-w-[130px] w-36">Batch No</th>
                        <th class="p-3 min-w-[140px] w-36 text-right">Line Total (₹)</th>
                        <th class="p-3 min-w-[50px] w-14 text-center"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(row, i) in lines" :key="row._key">
                        <tr class="align-top hover:bg-slate-50/50 transition-colors">
                            
                            <td class="p-3 min-w-[340px] lg:min-w-[380px]">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-slate-600">Product <span class="text-red-500">*</span></span>
                                    <a href="<?php echo e(route('masters.products.create', ['return_to' => 'invoices.create', 'return_url' => route('invoices.create')])); ?>"
                                       @click="saveDraftAndRedirectToProduct($event, i)"
                                       class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-0.5"
                                       title="Add a new product to master catalog">
                                        + Quick Add
                                    </a>
                                </div>
                                <select :name="`items[${i}][product_id]`"
                                        x-model="row.product_id"
                                        @change="onProductChange(i)"
                                        required
                                        class="block w-full rounded-md border-gray-300 text-xs sm:text-sm font-medium text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 min-h-[38px] px-3 py-2 bg-white">
                                    <option value="">Select product...</option>
                                    <template x-for="p in products" :key="p.id">
                                        <option :value="p.id" x-text="p.name" :selected="String(p.id) === String(row.product_id)"></option>
                                    </template>
                                </select>

                                
                                <div class="mt-2" x-show="isSerialTracked(row)" x-cloak>
                                    <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-amber-800">
                                        Serial Numbers
                                        <span class="text-[10px] font-bold uppercase text-amber-700 bg-amber-100 px-1.5 py-0.2 rounded">Tracked</span>
                                    </label>
                                    <input type="text"
                                           :name="`items[${i}][serial_numbers]`"
                                           x-model="row.serial_numbers"
                                           placeholder="Enter serial number(s) comma separated"
                                           class="block w-full rounded-md border-gray-300 px-2.5 py-2 text-xs font-mono text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                </div>
                            </td>

                            
                            <td class="p-3 align-top min-w-[85px] w-24 text-center pt-5">
                                <select :name="`items[${i}][uom_id]`"
                                        x-model="row.uom_id"
                                        required
                                        class="block w-full rounded-md border-gray-300 text-xs font-semibold text-slate-700 py-1.5 px-2 bg-white">
                                    <template x-for="u in uoms" :key="u.id">
                                        <option :value="u.id" x-text="u.code" :selected="String(u.id) === String(row.uom_id)"></option>
                                    </template>
                                </select>
                            </td>

                            
                            <td class="p-3 align-top min-w-[110px] w-28">
                                <input type="number"
                                       step="0.0001"
                                       min="0.0001"
                                       :name="`items[${i}][quantity]`"
                                       x-model.number="row.quantity"
                                       @input="recalc()"
                                       required
                                       class="block w-full min-h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <template x-if="row.ordered_quantity">
                                    <p class="mt-1 text-[10px] text-slate-400 text-right" x-text="`Order Qty: ${row.ordered_quantity}`"></p>
                                </template>
                            </td>

                            
                            <td class="p-3 align-top min-w-[140px] w-36">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       :name="`items[${i}][unit_price]`"
                                       x-model.number="row.unit_price"
                                       @input="recalc()"
                                       required
                                       class="block w-full min-h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-semibold text-slate-900">
                            </td>

                            
                            <td class="p-3 align-top min-w-[140px] w-36">
                                <div class="flex items-center gap-1">
                                    <select :name="`items[${i}][discount_type]`"
                                            x-model="row.discount_type"
                                            @change="recalc()"
                                            class="rounded-md border-gray-300 text-xs px-1.5 py-2 w-14">
                                        <option value="percent">%</option>
                                        <option value="flat">₹</option>
                                    </select>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           :name="`items[${i}][discount_value]`"
                                           x-model.number="row.discount_value"
                                           @input="recalc()"
                                           placeholder="0"
                                           class="block w-full min-h-[38px] px-2 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                </div>
                                <template x-if="lineDiscount(row) > 0">
                                    <p class="mt-1 text-[10px] text-slate-500 text-right" x-text="`- ${formatCurrency(lineDiscount(row))}`"></p>
                                </template>
                            </td>

                            
                            <td class="p-3 align-top min-w-[90px] w-24 text-right pt-5 font-mono text-xs text-slate-700" x-show="isIntraState()">
                                <span x-text="((row.tax_rate / 2).toFixed(2) + '%')"></span>
                            </td>

                            
                            <td class="p-3 align-top min-w-[90px] w-24 text-right pt-5 font-mono text-xs text-slate-700" x-show="isIntraState()">
                                <span x-text="((row.tax_rate / 2).toFixed(2) + '%')"></span>
                            </td>

                            
                            <td class="p-3 align-top min-w-[100px] w-28 text-center pt-5 font-semibold text-xs text-slate-700">
                                <span x-text="isIntraState() ? ((row.tax_rate || 0).toFixed(2) + '%') : ((row.tax_rate || 0).toFixed(2) + '% (IGST)')"></span>
                            </td>

                            
                            <td class="p-3 align-top min-w-[130px] w-36">
                                <div class="flex items-center gap-1">
                                    <input type="text"
                                           :name="`items[${i}][batch_no]`"
                                           x-model="row.batch_no"
                                           placeholder="Optional"
                                           class="block w-full min-h-[38px] px-2.5 py-2 text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <button type="button"
                                            @click="openBatchPicker(i)"
                                            x-show="row.product_id"
                                            title="Pick batch"
                                            class="rounded-md border border-slate-300 bg-white px-2 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 shrink-0">
                                        Pick
                                    </button>
                                </div>
                                <template x-if="row.batch_meta">
                                    <p class="mt-1 text-[10px] text-slate-500" x-text="`Avail: ${row.batch_meta.available_qty} @ ₹${row.batch_meta.selling_price ?? row.batch_meta.unit_cost}` + (row.batch_meta.expiry_date ? ` · exp ${row.batch_meta.expiry_date}` : '')"></p>
                                </template>
                            </td>

                            
                            <td class="p-3 align-top min-w-[140px] w-36 text-right pt-5 font-bold font-mono text-slate-900 text-sm whitespace-nowrap"
                                x-text="formatCurrency(lineTotal(row))"></td>

                            
                            <td class="p-3 align-top min-w-[50px] w-14 text-center pt-4">
                                <button type="button"
                                        @click="removeItem(i)"
                                        x-show="lines.length > 1"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-md text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition cursor-pointer"
                                        title="Remove row">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        
        <div class="p-4 bg-slate-50/50 border-t border-slate-200 grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            
            <div class="space-y-4">
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-2xs space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Universal Invoice Discount</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Discount Type</label>
                            <select name="universal_discount_type" x-model="universalType" @change="recalc()"
                                    class="block w-full rounded-md border-gray-300 text-xs">
                                <option value="flat">Flat ₹</option>
                                <option value="percent">Percent %</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Discount Value</label>
                            <input type="number" step="0.01" min="0" name="universal_discount_value" x-model.number="universalValue" @input="recalc()"
                                   class="block w-full rounded-md border-gray-300 text-xs font-mono">
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400">Applied on the invoice taxable subtotal in addition to per-line discounts.</p>
                </div>

                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-2xs space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Terms &amp; Conditions</label>
                    <textarea name="terms_and_conditions" rows="4"
                              class="block w-full rounded-md border-gray-300 text-xs"><?php echo e(old('terms_and_conditions', $sellingTermsAndConditions ?? '')); ?></textarea>
                </div>
            </div>

            
            <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-2xs space-y-3 text-xs lg:max-w-md lg:justify-self-end w-full">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 border-b pb-2">Invoice Summary</h4>

                <div class="flex justify-between items-center text-slate-600">
                    <span class="font-medium" x-text="priceType === 'inclusive' ? 'Total Before Tax & Discounts:' : 'Taxable Subtotal:'"></span>
                    <span class="font-mono font-semibold text-slate-800 text-sm" x-text="formatCurrency(subTotal())"></span>
                </div>

                <div class="flex justify-between items-center text-slate-600" x-show="priceType === 'inclusive'" x-cloak>
                    <span class="font-medium">Net Taxable Value:</span>
                    <span class="font-mono font-semibold text-indigo-700 text-sm" x-text="formatCurrency(netTaxableSubtotal())"></span>
                </div>

                <div class="flex justify-between items-center text-slate-600" x-show="itemDiscountTotal() > 0" x-cloak>
                    <span class="font-medium">Item Discounts Total:</span>
                    <span class="font-mono font-semibold text-amber-700 text-sm" x-text="`- ${formatCurrency(itemDiscountTotal())}`"></span>
                </div>

                <div class="flex justify-between items-center text-slate-600" x-show="universalDiscountAmount() > 0" x-cloak>
                    <span class="font-medium">Universal Discount:</span>
                    <span class="font-mono font-semibold text-amber-700 text-sm" x-text="`- ${formatCurrency(universalDiscountAmount())}`"></span>
                </div>

                
                <template x-if="isIntraState()">
                    <div class="space-y-1.5 pt-1 border-t border-dashed border-slate-200">
                        <div class="flex justify-between items-center text-slate-500 text-[11px]">
                            <span>CGST Total:</span>
                            <span class="font-mono" x-text="formatCurrency(cgstTotal())"></span>
                        </div>
                        <div class="flex justify-between items-center text-slate-500 text-[11px]">
                            <span>SGST Total:</span>
                            <span class="font-mono" x-text="formatCurrency(sgstTotal())"></span>
                        </div>
                        <div class="flex justify-between items-center text-slate-700 font-medium">
                            <span>Total Tax (CGST + SGST):</span>
                            <span class="font-mono font-semibold text-sm" x-text="formatCurrency(totalTaxAmount())"></span>
                        </div>
                    </div>
                </template>

                <template x-if="!isIntraState()">
                    <div class="space-y-1.5 pt-1 border-t border-dashed border-slate-200">
                        <div class="flex justify-between items-center text-slate-700 font-medium">
                            <span>Total Tax (IGST - Inter-State):</span>
                            <span class="font-mono font-semibold text-sm" x-text="formatCurrency(totalTaxAmount())"></span>
                        </div>
                    </div>
                </template>

                <template x-if="selectedSchemeBenefit() > 0">
                    <div class="flex justify-between items-center text-emerald-700 bg-emerald-50 p-2 rounded border border-emerald-100 font-medium">
                        <span>Scheme Benefit (Rebate):</span>
                        <span class="font-mono font-bold" x-text="formatCurrency(selectedSchemeBenefit())"></span>
                    </div>
                </template>

                
                <div class="flex items-center justify-between text-slate-600 pt-2 border-t border-slate-200">
                    <div class="flex items-center gap-1.5">
                        <span class="font-medium">Round Off (₹):</span>
                        <button type="button" @click="autoRoundOff()" class="text-[10px] text-indigo-600 hover:text-indigo-800 underline" title="Re-calculate automatic round off">Auto</button>
                    </div>
                    <input type="number" step="0.01" min="-500" max="500" name="round_off" x-model.number="roundOff" @input="isManualRoundOff = true; recalc()"
                           placeholder="0.00"
                           class="w-28 min-h-[32px] px-2 py-1 rounded-md border-gray-300 text-xs font-mono text-right shadow-2xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                
                <div class="flex justify-between items-center font-bold text-base text-slate-900 border-t border-slate-200 pt-3 mt-1">
                    <span>Grand Total:</span>
                    <span class="font-mono text-indigo-700 text-xl font-extrabold" x-text="formatCurrency(grandTotal())"></span>
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

    
    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-3">
        <div class="flex items-center justify-between border-b border-slate-200 pb-2">
            <div>
                <label class="block text-sm font-bold text-slate-800">Commercial Schemes</label>
                <p class="text-xs text-slate-500">Optionally apply an eligible commercial scheme to accrue rebate benefits for this invoice.</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-200 text-slate-700"
                  x-text="`${eligibleSchemes().length} scheme(s) available`"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
            <div>
                <label for="scheme_id" class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Select Scheme</label>
                <select name="scheme_id"
                        id="scheme_id"
                        x-model="selectedSchemeId"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">No Scheme</option>
                    <template x-for="s in eligibleSchemes()" :key="s.id">
                        <option :value="s.id" x-text="`${s.name} (${s.code}) · ${s.basis === 'quantity' ? 'Quantity Basis' : 'Value Basis'}${s.brand_name ? ' · ' + s.brand_name : ''}`"></option>
                    </template>
                </select>

                <template x-if="eligibleSchemes().length === 0">
                    <p class="mt-2 text-xs text-amber-700 bg-amber-50 p-2.5 rounded-lg border border-amber-200">
                        No eligible schemes available for the current invoice date and selected items. You can proceed without selecting a scheme.
                    </p>
                </template>
            </div>

            <div>
                <template x-if="currentSchemeDetails()">
                    <div class="bg-white p-3.5 rounded-lg border border-indigo-100 shadow-sm space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-indigo-900" x-text="currentSchemeDetails().scheme.name"></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700" x-text="currentSchemeDetails().scheme.basis === 'quantity' ? 'Quantity Basis' : 'Value Basis'"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100">
                            <div>
                                <span class="text-slate-500">Qualified:</span>
                                <span class="font-medium text-slate-800 ml-1" x-text="currentSchemeDetails().scheme.basis === 'quantity' ? `${currentSchemeDetails().qualified} Units` : formatCurrency(currentSchemeDetails().qualified)"></span>
                            </div>
                            <div>
                                <span class="text-slate-500">Calculated Benefit:</span>
                                <span class="font-bold text-emerald-600 ml-1" x-text="formatCurrency(currentSchemeDetails().benefit)"></span>
                            </div>
                        </div>

                        <template x-if="currentSchemeDetails().slab">
                            <div class="text-[11px] text-slate-600 bg-slate-50 p-2 rounded border border-slate-100">
                                <span class="font-medium">Applied Slab:</span>
                                <span x-text="`From ${currentSchemeDetails().scheme.basis === 'quantity' ? currentSchemeDetails().slab.from_value + ' units' : formatCurrency(currentSchemeDetails().slab.from_value)}` + (currentSchemeDetails().slab.to_value ? ` to ${currentSchemeDetails().scheme.basis === 'quantity' ? currentSchemeDetails().slab.to_value + ' units' : formatCurrency(currentSchemeDetails().slab.to_value)}` : ' onwards')"></span>
                                <span class="text-slate-500" x-text="` · Benefit: ${currentSchemeDetails().slab.benefit_amount > 0 ? formatCurrency(currentSchemeDetails().slab.benefit_amount) : currentSchemeDetails().slab.benefit_percent + '%'}`"></span>
                            </div>
                        </template>

                        <template x-if="!currentSchemeDetails().slab && currentSchemeDetails().qualifyingCount > 0">
                            <div class="text-[11px] text-amber-700 bg-amber-50 p-2 rounded border border-amber-200">
                                <span class="font-medium">Minimum slab not reached:</span>
                                <span x-text="`Requires minimum ${currentSchemeDetails().scheme.basis === 'quantity' ? currentSchemeDetails().minSlab + ' units' : formatCurrency(currentSchemeDetails().minSlab)}. Current benefit: ₹0.00`"></span>
                            </div>
                        </template>

                        <p class="text-[10px] text-slate-400 italic">
                            * Scheme rebate will be credited via commercial credit note / settlement as per scheme policy.
                        </p>
                    </div>
                </template>

                <template x-if="!currentSchemeDetails()">
                    <div class="h-full flex items-center justify-center p-3 border border-dashed border-slate-200 rounded-lg text-xs text-slate-400 text-center">
                        Select a scheme from the dropdown to view calculated rebate benefits.
                    </div>
                </template>
            </div>
        </div>
    </div>

    
    <input type="hidden" name="discount_amount" :value="itemDiscountTotal()">

    
    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('invoices.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('invoices.index'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Cancel <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'submit','variant' => 'primary','class' => 'px-6 py-2.5 text-sm font-semibold shadow-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary','class' => 'px-6 py-2.5 text-sm font-semibold shadow-sm']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            Create Invoice
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
    </div>
</form>


<?php if (isset($component)) { $__componentOriginaleda41c2464e95dfd4e350eb2c95ad90d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaleda41c2464e95dfd4e350eb2c95ad90d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.quick-add-customer','data' => ['targetSelect' => 'customer_id']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('quick-add-customer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['target-select' => 'customer_id']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaleda41c2464e95dfd4e350eb2c95ad90d)): ?>
<?php $attributes = $__attributesOriginaleda41c2464e95dfd4e350eb2c95ad90d; ?>
<?php unset($__attributesOriginaleda41c2464e95dfd4e350eb2c95ad90d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaleda41c2464e95dfd4e350eb2c95ad90d)): ?>
<?php $component = $__componentOriginaleda41c2464e95dfd4e350eb2c95ad90d; ?>
<?php unset($__componentOriginaleda41c2464e95dfd4e350eb2c95ad90d); ?>
<?php endif; ?>


<?php if (isset($component)) { $__componentOriginal8b15c7ee99f745d34af76cd06b392b0d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8b15c7ee99f745d34af76cd06b392b0d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.quick-add-product','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('quick-add-product'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8b15c7ee99f745d34af76cd06b392b0d)): ?>
<?php $attributes = $__attributesOriginal8b15c7ee99f745d34af76cd06b392b0d; ?>
<?php unset($__attributesOriginal8b15c7ee99f745d34af76cd06b392b0d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8b15c7ee99f745d34af76cd06b392b0d)): ?>
<?php $component = $__componentOriginal8b15c7ee99f745d34af76cd06b392b0d; ?>
<?php unset($__componentOriginal8b15c7ee99f745d34af76cd06b392b0d); ?>
<?php endif; ?>


<div x-show="batchPicker.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="batchPicker.open = false">
    <div class="w-full max-w-2xl rounded-xl bg-white p-5 shadow-xl">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-800">Pick Available Batch</h3>
            <button type="button" @click="batchPicker.open = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
        </div>
        <template x-if="batchPicker.loading">
            <p class="py-6 text-center text-slate-500">Loading batches…</p>
        </template>
        <template x-if="!batchPicker.loading && batchPicker.batches.length === 0">
            <p class="py-6 text-center text-slate-500">No active batches available for this product.</p>
        </template>
        <template x-if="!batchPicker.loading && batchPicker.batches.length > 0">
            <div class="max-h-96 overflow-y-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="sticky top-0 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Batch No</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Warehouse</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Expiry</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Available</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Selling Price (₹)</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="b in batchPicker.batches" :key="b.id">
                            <tr>
                                <td class="px-3 py-2 font-mono font-medium text-slate-800" x-text="b.batch_no || '—'"></td>
                                <td class="px-3 py-2 text-slate-600" x-text="b.warehouse || '—'"></td>
                                <td class="px-3 py-2 text-slate-500" x-text="b.expiry_date || '—'"></td>
                                <td class="px-3 py-2 text-right font-mono font-semibold text-slate-800" x-text="b.available_qty"></td>
                                <td class="px-3 py-2 text-right font-mono text-slate-700" x-text="formatCurrency(b.selling_price ?? b.unit_cost)"></td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" @click="applyBatch(b)" class="rounded-lg bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                                        Use
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function salesInvoiceForm(products, uoms, schemes, customers, initialOrder, initialOrders, companyState, newProduct, savedDraft, targetRow) {
    return {
        products: products || [],
        uoms: uoms || [],
        schemes: schemes || [],
        customers: customers || [],
        companyState: companyState || '',
        newProduct: newProduct || null,
        savedDraft: savedDraft || null,
        targetRow: targetRow !== undefined ? targetRow : null,
        _seq: 0,
        lines: [],

        // Header models
        priceType: '<?php echo e(old('price_type', 'exclusive')); ?>',
        roundOff: <?php echo e(old('round_off', 0)); ?>,
        isManualRoundOff: <?php echo e(old('round_off') !== null ? 'true' : 'false'); ?>,
        customerId: initialOrder ? initialOrder.customer_id : '<?php echo e(old('customer_id', '')); ?>',
        salespersonId: initialOrder && initialOrder.salesperson_id ? String(initialOrder.salesperson_id) : '<?php echo e(old('salesperson_id', '')); ?>',

        orderId: initialOrder ? initialOrder.id : '<?php echo e(old('order_id', '')); ?>',
        customerOrders: initialOrders || [],
        soLoading: false,
        selectedOrderLabel: initialOrder ? (initialOrder.order_no || initialOrder.order_number || '') : '',
        linkedOrderId: initialOrder ? initialOrder.id : null,

        invoiceDate: '<?php echo e(old('invoice_date', date('Y-m-d'))); ?>',
        deliveryState: '<?php echo e(old('delivery_state', '')); ?>',
        notes: '<?php echo e(old('notes', '')); ?>',

        // Delivery Note Details
        deliveryNoteNo: initialOrder ? (initialOrder.delivery_note_no || '') : '<?php echo e(old('delivery_note_no', '')); ?>',
        deliveryNoteNa: <?php echo e(old('delivery_note_na') ? 'true' : 'false'); ?>,

        // Dispatch Details
        dispatchDocNo: initialOrder ? (initialOrder.dispatch_doc_no || '') : '<?php echo e(old('dispatch_doc_no', '')); ?>',
        dispatchedThrough: initialOrder ? (initialOrder.dispatched_through || initialOrder.transport_mode || '') : '<?php echo e(old('dispatched_through', old('transport_mode', ''))); ?>',
        destination: initialOrder ? (initialOrder.destination || '') : '<?php echo e(old('destination', '')); ?>',
        carrierName: initialOrder ? (initialOrder.carrier_name || '') : '<?php echo e(old('carrier_name', '')); ?>',
        lrRrNo: initialOrder ? (initialOrder.lr_rr_no || '') : '<?php echo e(old('lr_rr_no', '')); ?>',
        lrRrDate: initialOrder ? (initialOrder.lr_rr_date || '') : '<?php echo e(old('lr_rr_date', '')); ?>',
        motorVehicleNo: initialOrder ? (initialOrder.vehicle_no || '') : '<?php echo e(old('motor_vehicle_no', old('vehicle_no', ''))); ?>',

        // Order Details
        orderNo: initialOrder ? (initialOrder.order_no || initialOrder.order_number || '') : '<?php echo e(old('order_no', '')); ?>',
        paymentTerms: initialOrder ? (initialOrder.payment_terms || '') : '<?php echo e(old('payment_terms', '')); ?>',
        otherReferences: initialOrder ? (initialOrder.other_references || initialOrder.reference_no || '') : '<?php echo e(old('other_references', old('reference_no', ''))); ?>',
        termsOfDelivery: initialOrder ? (initialOrder.terms_of_delivery || '') : '<?php echo e(old('terms_of_delivery', '')); ?>',

        creditDays: <?php echo e(old('credit_days', $order?->customer?->credit_days ?? 0)); ?>,
        dueDatePreview: '',
        dueDateFormattedYmd: '',

        partyAddresses: [],
        addressesLoading: false,
        selectedBillingId: initialOrder ? (initialOrder.billing_address_id || '') : '<?php echo e(old('billing_address_id', '')); ?>',
        selectedShippingId: initialOrder ? (initialOrder.shipping_address_id || '') : '<?php echo e(old('shipping_address_id', '')); ?>',
        billingSnapshot: initialOrder ? (initialOrder.billing_address || '') : '<?php echo e(old('billing_address', '')); ?>',
        shippingSnapshot: initialOrder ? (initialOrder.shipping_address || '') : '<?php echo e(old('shipping_address', '')); ?>',
        sameAsBilling: true,

        universalType: '<?php echo e(old('universal_discount_type', 'flat')); ?>',
        universalValue: <?php echo e(old('universal_discount_value', 0)); ?>,
        selectedSchemeId: '<?php echo e(old('scheme_id', '')); ?>',

        batchPicker: { open: false, loading: false, targetIdx: null, batches: [] },

        onDeliveryNoteNaToggle() {
            if (this.deliveryNoteNa) {
                this.deliveryNoteNo = '';
            }
        },

        async init() {
            this.recalcDueDate();

            const urlParams = new URLSearchParams(window.location.search);
            const returnedNewProductId = (newProduct && newProduct.id ? String(newProduct.id) : '') || urlParams.get('new_product_id');
            const isReturnedFromProduct = Boolean(returnedNewProductId) || urlParams.get('returned_from') === 'product_create';
            const hasServerOldValues = <?php echo json_encode(old('_token') !== null, 15, 512) ?>;

            let draft = savedDraft || null;
            if (!draft) {
                try {
                    const raw = sessionStorage.getItem('dms_sales_invoice_draft');
                    if (raw) draft = JSON.parse(raw);
                } catch (e) {
                    draft = null;
                }
            }

            if (draft && (isReturnedFromProduct || !hasServerOldValues)) {
                if (draft.customerId) {
                    this.customerId = String(draft.customerId);
                    const custSel = document.querySelector('select[name="customer_id"]');
                    if (custSel) {
                        custSel.value = this.customerId;
                        custSel.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
                if (draft.salespersonId !== undefined && draft.salespersonId !== null && draft.salespersonId !== '') {
                    this.salespersonId = String(draft.salespersonId);
                    const spSel = document.querySelector('select[name="salesperson_id"]');
                    if (spSel) {
                        spSel.value = this.salespersonId;
                        spSel.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
                if (draft.orderId) {
                    this.orderId = draft.orderId;
                    this.selectedOrderLabel = draft.selectedOrderLabel || '';
                    this.linkedOrderId = draft.linkedOrderId || null;
                }
                if (draft.invoiceDate) this.invoiceDate = draft.invoiceDate;
                if (draft.deliveryState) this.deliveryState = draft.deliveryState;
                if (draft.notes) this.notes = draft.notes;
                if (draft.deliveryNoteNo) this.deliveryNoteNo = draft.deliveryNoteNo;
                this.deliveryNoteNa = Boolean(draft.deliveryNoteNa);
                if (draft.dispatchDocNo) this.dispatchDocNo = draft.dispatchDocNo;
                if (draft.dispatchedThrough) this.dispatchedThrough = draft.dispatchedThrough;
                if (draft.destination) this.destination = draft.destination;
                if (draft.carrierName) this.carrierName = draft.carrierName;
                if (draft.lrRrNo) this.lrRrNo = draft.lrRrNo;
                if (draft.lrRrDate) this.lrRrDate = draft.lrRrDate;
                if (draft.motorVehicleNo) this.motorVehicleNo = draft.motorVehicleNo;
                if (draft.orderNo) this.orderNo = draft.orderNo;
                if (draft.paymentTerms) this.paymentTerms = draft.paymentTerms;
                if (draft.otherReferences) this.otherReferences = draft.otherReferences;
                if (draft.termsOfDelivery) this.termsOfDelivery = draft.termsOfDelivery;
                if (draft.creditDays) {
                    this.creditDays = Number(draft.creditDays) || 0;
                    this.recalcDueDate();
                }
                if (draft.selectedBillingId) this.selectedBillingId = draft.selectedBillingId;
                if (draft.selectedShippingId) this.selectedShippingId = draft.selectedShippingId;
                if (draft.billingSnapshot) this.billingSnapshot = draft.billingSnapshot;
                if (draft.shippingSnapshot) this.shippingSnapshot = draft.shippingSnapshot;
                if (draft.sameAsBilling !== undefined) this.sameAsBilling = Boolean(draft.sameAsBilling);
                if (draft.universalType) this.universalType = draft.universalType;
                if (draft.universalValue !== undefined) this.universalValue = draft.universalValue;
                if (draft.selectedSchemeId) this.selectedSchemeId = draft.selectedSchemeId;

                if (Array.isArray(draft.lines) && draft.lines.length > 0) {
                    this.lines = draft.lines.map(it => {
                        const p = this.products.find(prod => String(prod.id) === String(it.product_id));
                        const u = this.uoms.find(uom => String(uom.id) === String(it.uom_id));
                        return {
                            _key: ++this._seq,
                            product_id: it.product_id || '',
                            uom_id: it.uom_id || (p ? p.base_uom_id : ''),
                            unit: it.unit || (u ? u.code : (p ? p.unit : 'PCS')),
                            tracking_type: it.tracking_type || (p ? p.tracking_type : 'none'),
                            quantity: Number(it.quantity) || 1,
                            ordered_quantity: Number(it.ordered_quantity || it.quantity) || 1,
                            unit_price: Number(it.unit_price) || 0,
                            discount_type: it.discount_type || 'flat',
                            discount_value: Number(it.discount_value) || 0,
                            tax_rate: (it.tax_rate !== undefined && it.tax_rate !== null) ? Number(it.tax_rate) : (p ? Number(p.tax_rate) : 0),
                            batch_no: it.batch_no || '',
                            serial_numbers: it.serial_numbers || '',
                            batch_meta: null,
                        };
                    });
                } else {
                    this.addItem();
                }

                if (this.customerId) {
                    await this.loadCustomerAddresses(this.customerId);
                    await this.loadCustomerOrders(this.customerId, this.orderId);
                }
            } else if (initialOrder) {
                this.orderId = initialOrder.id;
                this.selectedOrderLabel = initialOrder.order_no || initialOrder.order_number || '';
                this.linkedOrderId = initialOrder.id;
                if (initialOrder.order_no && !this.orderNo) {
                    this.orderNo = initialOrder.order_no;
                }
                if (initialOrder.payment_terms && !this.paymentTerms) {
                    this.paymentTerms = initialOrder.payment_terms;
                }
                if (initialOrder.terms_of_delivery && !this.termsOfDelivery) {
                    this.termsOfDelivery = initialOrder.terms_of_delivery;
                }
                if ((initialOrder.other_references || initialOrder.reference_no) && !this.otherReferences) {
                    this.otherReferences = initialOrder.other_references || initialOrder.reference_no;
                }
                if (initialOrder.destination && !this.destination) {
                    this.destination = initialOrder.destination;
                }
                if (initialOrder.notes && !this.notes) {
                    this.notes = initialOrder.notes;
                }
                if (initialOrder.delivery_state && !this.deliveryState) {
                    this.deliveryState = initialOrder.delivery_state;
                }

                if (this.customerId) {
                    await this.loadCustomerAddresses(this.customerId);
                    if (!this.customerOrders.length) {
                        await this.loadCustomerOrders(this.customerId, this.orderId);
                    }
                }

                if (initialOrder.items && initialOrder.items.length) {
                    this.lines = initialOrder.items.map(it => {
                        const p = this.products.find(prod => String(prod.id) === String(it.product_id));
                        const u = this.uoms.find(uom => String(uom.id) === String(it.uom_id));
                        return {
                            _key: ++this._seq,
                            product_id: it.product_id,
                            uom_id: it.uom_id || (p ? p.base_uom_id : ''),
                            unit: u ? u.code : (p ? p.unit : 'PCS'),
                            tracking_type: p ? p.tracking_type : 'none',
                            quantity: Number(it.quantity) || 1,
                            ordered_quantity: Number(it.ordered_quantity || it.quantity) || 1,
                            unit_price: Number(it.unit_price) || 0,
                            discount_type: it.discount_type || 'flat',
                            discount_value: Number(it.discount_value) || 0,
                            tax_rate: p ? Number(p.tax_rate) : 0,
                            batch_no: it.batch_no || '',
                            serial_numbers: it.serial_numbers || '',
                            batch_meta: null,
                        };
                    });
                } else {
                    this.addItem();
                }
            } else {
                if (this.customerId) {
                    await this.loadCustomerAddresses(this.customerId);
                    await this.loadCustomerOrders(this.customerId, this.orderId);
                }
                this.addItem();
            }

            // Ensure new product is registered in this.products
            if (newProduct && !this.products.some(p => String(p.id) === String(newProduct.id))) {
                this.products.push(newProduct);
            }

            // Automatically select newly created product
            if (returnedNewProductId) {
                const targetIdx = (targetRow !== null && targetRow !== undefined && targetRow !== '') ? Number(targetRow) : (draft && draft.targetRow !== null && draft.targetRow !== undefined ? Number(draft.targetRow) : null);
                const newProdIdStr = String(returnedNewProductId);

                let placed = false;
                if (targetIdx !== null && this.lines[targetIdx] && !this.lines[targetIdx].product_id) {
                    this.lines[targetIdx].product_id = newProdIdStr;
                    this.onProductChange(targetIdx);
                    placed = true;
                }

                if (!placed) {
                    const emptyIdx = this.lines.findIndex(r => !r.product_id);
                    if (emptyIdx !== -1) {
                        this.lines[emptyIdx].product_id = newProdIdStr;
                        this.onProductChange(emptyIdx);
                    } else {
                        this.addItem();
                        const lastIdx = this.lines.length - 1;
                        this.lines[lastIdx].product_id = newProdIdStr;
                        this.onProductChange(lastIdx);
                    }
                }
            }

            this.recalc();

            // Clear sessionStorage draft
            try {
                sessionStorage.removeItem('dms_sales_invoice_draft');
            } catch (e) {}

            // Clean query parameters from URL so refreshes don't re-trigger
            if (isReturnedFromProduct && window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        },

        saveDraftAndRedirectToProduct(event, targetRowIndex = null) {
            if (event) {
                event.preventDefault();
            }

            const custEl = document.querySelector('select[name="customer_id"]');
            const custId = custEl ? custEl.value : this.customerId;

            const draft = {
                customerId: custId || '',
                salespersonId: this.salespersonId || '',
                orderId: this.orderId || '',
                selectedOrderLabel: this.selectedOrderLabel || '',
                linkedOrderId: this.linkedOrderId || null,
                invoiceDate: this.invoiceDate || '',
                deliveryState: this.deliveryState || '',
                notes: this.notes || '',
                deliveryNoteNo: this.deliveryNoteNo || '',
                deliveryNoteNa: Boolean(this.deliveryNoteNa),
                dispatchDocNo: this.dispatchDocNo || '',
                dispatchedThrough: this.dispatchedThrough || '',
                destination: this.destination || '',
                carrierName: this.carrierName || '',
                lrRrNo: this.lrRrNo || '',
                lrRrDate: this.lrRrDate || '',
                motorVehicleNo: this.motorVehicleNo || '',
                orderNo: this.orderNo || '',
                paymentTerms: this.paymentTerms || '',
                otherReferences: this.otherReferences || '',
                termsOfDelivery: this.termsOfDelivery || '',
                creditDays: this.creditDays || 0,
                selectedBillingId: this.selectedBillingId || '',
                selectedShippingId: this.selectedShippingId || '',
                billingSnapshot: this.billingSnapshot || '',
                shippingSnapshot: this.shippingSnapshot || '',
                sameAsBilling: Boolean(this.sameAsBilling),
                universalType: this.universalType || 'flat',
                universalValue: this.universalValue || 0,
                selectedSchemeId: this.selectedSchemeId || '',
                lines: (this.lines || []).map(r => ({
                    product_id: r.product_id || '',
                    uom_id: r.uom_id || '',
                    unit: r.unit || 'PCS',
                    tracking_type: r.tracking_type || 'none',
                    quantity: Number(r.quantity) || 1,
                    ordered_quantity: Number(r.ordered_quantity || r.quantity) || 1,
                    unit_price: Number(r.unit_price) || 0,
                    discount_type: r.discount_type || 'flat',
                    discount_value: Number(r.discount_value) || 0,
                    tax_rate: Number(r.tax_rate) || 0,
                    batch_no: r.batch_no || '',
                    serial_numbers: r.serial_numbers || '',
                })),
                targetRow: targetRowIndex !== null ? targetRowIndex : null,
                timestamp: Date.now()
            };

            try {
                sessionStorage.setItem('dms_sales_invoice_draft', JSON.stringify(draft));
            } catch (e) {
                console.error('Failed to save invoice draft to sessionStorage', e);
            }

            let targetUrl = <?php echo json_encode(route('masters.products.create', ['return_to' => 'invoices.create', 'return_url' => route('invoices.create')])) ?>;
            if (targetRowIndex !== null && targetRowIndex !== undefined) {
                targetUrl += (targetUrl.includes('?') ? '&' : '?') + 'target_row=' + encodeURIComponent(targetRowIndex);
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                              document.querySelector('input[name="_token"]')?.value;

            if (csrfToken && window.fetch) {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 1200);
                fetch(<?php echo json_encode(route('invoices.draft.save'), 15, 512) ?>, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ draft }),
                    signal: controller.signal
                }).finally(() => {
                    clearTimeout(timeoutId);
                    window.location.href = targetUrl;
                });
            } else {
                window.location.href = targetUrl;
            }
        },

        clearDraftOnSubmit() {
            try {
                sessionStorage.removeItem('dms_sales_invoice_draft');
            } catch (e) {}
        },

        // Customer selection helpers
        clearCustomer() {
            this.customerId = '';
            const sel = document.querySelector('select[name="customer_id"]');
            if (sel && sel.value !== '') {
                sel.value = '';
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            }
            this.customerOrders = [];
            this.orderId = '';
            this.selectedOrderLabel = '';
            this.partyAddresses = [];
            this.selectedBillingId = '';
            this.selectedShippingId = '';
            this.billingSnapshot = '';
            this.shippingSnapshot = '';
            this.deliveryState = '';
        },

        async onCustomerChange(id) {
            const sel = document.querySelector('select[name="customer_id"]');
            if (sel && sel.value !== undefined) {
                this.customerId = sel.value;
                id = sel.value;
            } else if (id !== undefined) {
                this.customerId = id;
            } else {
                id = this.customerId;
            }

            const hadLinkedOrder = !!this.linkedOrderId;
            this.orderId = '';
            this.selectedOrderLabel = '';
            this.customerOrders = [];

            if (hadLinkedOrder) {
                this.lines = [];
                this.addItem();
                this.linkedOrderId = null;
                this.recalc();
            }

            if (!id) {
                this.clearCustomer();
                return;
            }

            const custOpt = document.querySelector(`select[name="customer_id"] option[value="${id}"]`);
            if (custOpt) {
                if (custOpt.dataset.creditDays && (!this.creditDays || this.creditDays === 0)) {
                    const cDays = parseInt(custOpt.dataset.creditDays, 10);
                    if (cDays > 0) {
                        this.setCreditDays(cDays);
                    }
                }
                if (custOpt.dataset.state && !this.deliveryState) {
                    this.deliveryState = custOpt.dataset.state;
                }
            }

            const custData = this.customers.find(c => String(c.id) === String(id));
            if (custData && custData.salesperson_id && !this.salespersonId) {
                this.salespersonId = String(custData.salesperson_id);
                const spSel = document.querySelector('select[name="salesperson_id"]');
                if (spSel) {
                    spSel.value = this.salespersonId;
                    spSel.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            await Promise.all([
                this.loadCustomerAddresses(id),
                this.loadCustomerOrders(id)
            ]);
        },

        async loadCustomerAddresses(id) {
            this.addressesLoading = true;
            try {
                const res = await fetch(`<?php echo e(url('/masters/customers')); ?>/${id}/addresses`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.partyAddresses = data.addresses || [];
                    this.selectedBillingId = data.default_billing_id || (this.partyAddresses[0]?.id ?? '');
                    this.selectedShippingId = data.default_delivery_id || this.selectedBillingId;

                    this.onBillingAddressChange();
                    if (!this.sameAsBilling) {
                        this.onShippingAddressChange();
                    } else {
                        this.shippingSnapshot = this.billingSnapshot;
                    }
                }
            } catch (e) {
                console.error('Failed to load party addresses:', e);
            } finally {
                this.addressesLoading = false;
            }
        },

        onBillingAddressChange() {
            const addr = this.partyAddresses.find(a => String(a.id) === String(this.selectedBillingId));
            this.billingSnapshot = addr ? (addr.snapshot || addr.full_address) : '';
            if (this.sameAsBilling) {
                this.selectedShippingId = this.selectedBillingId;
                this.shippingSnapshot = this.billingSnapshot;
                if (addr?.state) {
                    this.deliveryState = addr.state;
                    this.recalc();
                }
            }
        },

        onShippingAddressChange() {
            const addr = this.partyAddresses.find(a => String(a.id) === String(this.selectedShippingId));
            this.shippingSnapshot = addr ? (addr.snapshot || addr.full_address) : '';
            if (addr?.state) {
                this.deliveryState = addr.state;
                this.recalc();
            }
        },

        onSameAsBillingToggle() {
            if (this.sameAsBilling) {
                this.selectedShippingId = this.selectedBillingId;
                this.shippingSnapshot = this.billingSnapshot;
                const addr = this.partyAddresses.find(a => String(a.id) === String(this.selectedBillingId));
                if (addr?.state) {
                    this.deliveryState = addr.state;
                    this.recalc();
                }
            } else {
                this.onShippingAddressChange();
            }
        },

        // Sales Orders loading & selection
        async loadCustomerOrders(custId, preselectId = null) {
            if (!custId) {
                this.customerOrders = [];
                this.orderId = '';
                this.selectedOrderLabel = '';
                return;
            }

            this.soLoading = true;
            try {
                const res = await fetch(`<?php echo e(url('/invoices/customers')); ?>/${custId}/sales-orders`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.customerOrders = data.orders || [];

                if (preselectId) {
                    const found = this.customerOrders.find(o => String(o.id) === String(preselectId));
                    if (found) {
                        this.orderId = found.id;
                        this.selectedOrderLabel = found.order_no;
                    }
                } else if (this.orderId) {
                    const found = this.customerOrders.find(o => String(o.id) === String(this.orderId));
                    if (!found) {
                        this.orderId = '';
                        this.selectedOrderLabel = '';
                    }
                }
            } catch (e) {
                console.error('Failed to load customer sales orders:', e);
                this.customerOrders = [];
            } finally {
                this.soLoading = false;
            }
        },

        async onSalesOrderChange() {
            if (!this.orderId) {
                if (this.linkedOrderId) {
                    this.lines = [];
                    this.addItem();
                    this.linkedOrderId = null;
                    this.selectedOrderLabel = '';
                    this.recalc();
                }
                return;
            }

            this.soLoading = true;
            try {
                const res = await fetch(`<?php echo e(url('/invoices/sales-orders')); ?>/${this.orderId}/data`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.order) {
                    const ord = data.order;
                    this.linkedOrderId = ord.id;
                    this.selectedOrderLabel = ord.order_no || ord.order_number;

                    if (ord.salesperson_id) {
                        this.salespersonId = String(ord.salesperson_id);
                        const spSel = document.querySelector('select[name="salesperson_id"]');
                        if (spSel) {
                            spSel.value = this.salespersonId;
                            spSel.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }

                    if (ord.order_no) {
                        this.orderNo = ord.order_no;
                    }
                    if (ord.payment_terms && !this.paymentTerms) {
                        this.paymentTerms = ord.payment_terms;
                    }
                    if (ord.terms_of_delivery && !this.termsOfDelivery) {
                        this.termsOfDelivery = ord.terms_of_delivery;
                    }
                    if ((ord.other_references || ord.reference_no) && !this.otherReferences) {
                        this.otherReferences = ord.other_references || ord.reference_no;
                    }
                    if (ord.destination && !this.destination) {
                        this.destination = ord.destination;
                    }

                    if (ord.credit_days) {
                        this.setCreditDays(ord.credit_days);
                    }
                    if (ord.notes && !this.notes) {
                        this.notes = ord.notes;
                    }
                    if (ord.delivery_state) {
                        this.deliveryState = ord.delivery_state;
                    }

                    if (ord.billing_address_id) {
                        this.selectedBillingId = ord.billing_address_id;
                    }
                    if (ord.shipping_address_id) {
                        this.selectedShippingId = ord.shipping_address_id;
                    }
                    if (ord.billing_address) {
                        this.billingSnapshot = ord.billing_address;
                    }
                    if (ord.shipping_address) {
                        this.shippingSnapshot = ord.shipping_address;
                    }
                    this.sameAsBilling = (!ord.shipping_address_id || ord.shipping_address_id === ord.billing_address_id);

                    if (ord.items && ord.items.length) {
                        this.lines = ord.items.map(it => {
                            const p = this.products.find(prod => String(prod.id) === String(it.product_id));
                            const u = this.uoms.find(uom => String(uom.id) === String(it.uom_id));
                            return {
                                _key: ++this._seq,
                                product_id: it.product_id,
                                uom_id: it.uom_id || (p ? p.base_uom_id : ''),
                                unit: it.unit || (u ? u.code : (p ? p.unit : 'PCS')),
                                tracking_type: it.tracking_type || (p ? p.tracking_type : 'none'),
                                quantity: Number(it.quantity) || 1,
                                ordered_quantity: Number(it.ordered_quantity || it.quantity) || 1,
                                unit_price: Number(it.unit_price) || 0,
                                discount_type: it.discount_type || 'flat',
                                discount_value: Number(it.discount_value) || 0,
                                tax_rate: Number(it.tax_rate ?? p?.tax_rate ?? 0),
                                batch_no: it.batch_no || '',
                                serial_numbers: it.serial_numbers || '',
                                batch_meta: null,
                            };
                        });
                    }
                    this.recalc();
                }
            } catch (e) {
                console.error('Failed to load sales order data:', e);
            } finally {
                this.soLoading = false;
            }
        },

        // Due date calculation
        setCreditDays(days) {
            this.creditDays = days;
            this.recalcDueDate();
        },

        recalcDueDate() {
            if (!this.invoiceDate) {
                this.dueDatePreview = '';
                this.dueDateFormattedYmd = '';
                return;
            }
            const date = new Date(this.invoiceDate);
            if (isNaN(date.getTime())) {
                this.dueDatePreview = '';
                this.dueDateFormattedYmd = '';
                return;
            }
            const days = parseInt(this.creditDays, 10) || 0;
            date.setDate(date.getDate() + days);

            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');

            this.dueDateFormattedYmd = `${yyyy}-${mm}-${dd}`;
            this.dueDatePreview = `${dd}-${mm}-${yyyy}`;
        },

        // Place of supply / GST determination
        isIntraState() {
            if (!this.companyState || !this.deliveryState) return true;
            return this.companyState.trim().toLowerCase() === this.deliveryState.trim().toLowerCase();
        },

        // Items and lines management
        addItem() {
            const firstUom = this.uoms[0]?.id || '';
            this.lines.push({
                _key: ++this._seq,
                product_id: '',
                uom_id: firstUom,
                unit: 'PCS',
                tracking_type: 'none',
                quantity: 1,
                ordered_quantity: null,
                unit_price: 0,
                discount_type: 'percent',
                discount_value: 0,
                tax_rate: 0,
                batch_no: '',
                serial_numbers: '',
                batch_meta: null,
            });
            this.recalc();
        },

        removeItem(index) {
            if (this.lines.length > 1) {
                this.lines.splice(index, 1);
                this.recalc();
            }
        },

        isSerialTracked(row) {
            if (!row || !row.tracking_type) return false;
            const tt = String(row.tracking_type).toLowerCase();
            return tt === 'serial' || tt.includes('serial');
        },

        onProductChange(index) {
            const row = this.lines[index];
            const p = this.products.find(item => String(item.id) === String(row.product_id));
            if (p) {
                row.uom_id = p.base_uom_id || row.uom_id;
                row.unit = p.unit || 'PCS';
                row.tracking_type = p.tracking_type || 'none';
                row.unit_price = Number(p.selling_price) || 0;
                row.discount_type = p.selling_discount_type || 'percent';
                row.discount_value = Number(p.selling_discount_value) || 0;
                row.tax_rate = Number(p.tax_rate) || 0;
            }
            this.recalc();
        },

        onProductAdded(p) {
            this.products.push({
                id: p.id,
                name: p.name,
                brand_id: p.brand_id,
                base_uom_id: p.base_uom_id,
                unit: p.base_uom_name || 'PCS',
                selling_price: Number(p.selling_price) || 0,
                tax_rate: Number(p.tax_rate) || 0,
                tracking_type: p.tracking_type || 'none',
                selling_discount_type: 'percent',
                selling_discount_value: 0,
            });
            const empty = this.lines.find(r => !r.product_id) || this.lines[this.lines.length - 1];
            if (empty) {
                empty.product_id = p.id;
                this.onProductChange(this.lines.indexOf(empty));
            }
        },

        onCustomerAdded(c) {
            const phone = c.phone ? String(c.phone).trim() : '';
            const newCust = {
                id: c.id,
                name: c.name,
                phone: phone,
                label: c.name + (phone ? ' - ' + phone : ''),
                credit_days: Number(c.credit_days) || 0,
                state: c.state || '',
            };
            this.customers.push(newCust);
            this.customerId = String(c.id);

            const sel = document.querySelector('select[name="customer_id"]');
            if (sel) {
                let opt = sel.querySelector(`option[value="${c.id}"]`);
                if (!opt) {
                    opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name + (phone ? ' — ' + phone : '');
                    opt.dataset.creditDays = c.credit_days || 0;
                    opt.dataset.state = c.state || '';
                    if (phone) {
                        opt.dataset.code = phone;
                        opt.dataset.subtext = phone;
                    }
                    sel.appendChild(opt);
                }
                sel.value = c.id;
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            }
            this.onCustomerChange(this.customerId);
        },

        // Batch Picker Modal Handlers
        async openBatchPicker(idx) {
            const row = this.lines[idx];
            if (!row.product_id) return;
            this.batchPicker.open = true;
            this.batchPicker.loading = true;
            this.batchPicker.targetIdx = idx;
            this.batchPicker.batches = [];
            try {
                const params = new URLSearchParams();
                if (row.uom_id) params.set('uom_id', row.uom_id);
                const res = await fetch(`<?php echo e(url('/inventory/batches')); ?>/${row.product_id}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.batchPicker.batches = data.batches || [];
            } catch (e) {
                console.error(e);
            } finally {
                this.batchPicker.loading = false;
            }
        },

        applyBatch(b) {
            const row = this.lines[this.batchPicker.targetIdx];
            if (!row) return;
            row.batch_no = b.batch_no || '';
            row.batch_meta = b;
            if (b.uom_id) row.uom_id = b.uom_id;
            const price = b.selling_price ?? b.unit_cost;
            if (price) row.unit_price = Number(price);
            if (b.available_qty && Number(row.quantity) > Number(b.available_qty)) {
                row.quantity = Number(b.available_qty);
            }
            this.batchPicker.open = false;
            this.recalc();
        },

        // Mathematical calculations
        lineGross(row) {
            const qty = Number(row.quantity) || 0;
            const entered = Number(row.unit_price) || 0;
            return Math.max(0, qty * entered);
        },

        lineDiscount(row) {
            const g = this.lineGross(row);
            const v = Number(row.discount_value) || 0;
            if (v <= 0) return 0;
            return row.discount_type === 'flat' ? Math.min(g, v * Math.max(Number(row.quantity) || 1, 1)) : (g * v / 100);
        },

        lineTaxable(row) {
            const g = this.lineGross(row);
            const d = this.lineDiscount(row);
            const net = Math.max(0, g - d);
            const rate = Number(row.tax_rate) || 0;

            if (this.priceType === 'inclusive') {
                return rate > 0 ? (net * 100 / (100 + rate)) : net;
            }
            return net;
        },

        lineTax(row) {
            const taxable = this.lineTaxable(row);
            const rate = Number(row.tax_rate) || 0;
            return (taxable * rate) / 100;
        },

        lineTotal(row) {
            if (this.priceType === 'inclusive') {
                // If tax-inclusive, total after discount is gross - discount (tax is inside)
                return Math.max(0, this.lineGross(row) - this.lineDiscount(row));
            }
            return this.lineTaxable(row) + this.lineTax(row);
        },

        subTotal() {
            // Gross amount before taxes and discounts
            return this.lines.reduce((sum, r) => sum + this.lineGross(r), 0);
        },

        netTaxableSubtotal() {
            return this.lines.reduce((sum, r) => sum + this.lineTaxable(r), 0);
        },

        itemDiscountTotal() {
            return this.lines.reduce((sum, r) => sum + this.lineDiscount(r), 0);
        },

        universalDiscountAmount() {
            const sub = this.netTaxableSubtotal();
            const v = Number(this.universalValue) || 0;
            if (v <= 0) return 0;
            return this.universalType === 'flat' ? Math.min(sub, v) : (sub * v / 100);
        },

        totalTaxAmount() {
            const netSub = this.netTaxableSubtotal();
            const uDisc = this.universalDiscountAmount();
            if (uDisc <= 0 || netSub <= 0) {
                return this.lines.reduce((sum, r) => sum + this.lineTax(r), 0);
            }
            // Pro-rate tax reduction when universal discount is applied
            const factor = Math.max(0, (netSub - uDisc) / netSub);
            return this.lines.reduce((sum, r) => sum + (this.lineTax(r) * factor), 0);
        },

        cgstTotal() {
            if (!this.isIntraState()) return 0;
            return this.totalTaxAmount() / 2;
        },

        sgstTotal() {
            if (!this.isIntraState()) return 0;
            return this.totalTaxAmount() / 2;
        },

        igstTotal() {
            if (this.isIntraState()) return 0;
            return this.totalTaxAmount();
        },

        totalBeforeRoundOff() {
            const netTaxable = Math.max(0, this.netTaxableSubtotal() - this.universalDiscountAmount());
            return netTaxable + this.totalTaxAmount();
        },

        autoRoundOff() {
            const total = this.totalBeforeRoundOff();
            this.roundOff = Math.round((Math.round(total) - total) * 100) / 100;
            this.isManualRoundOff = false;
        },

        grandTotal() {
            const total = this.totalBeforeRoundOff();
            if (!this.isManualRoundOff) {
                this.roundOff = Math.round((Math.round(total) - total) * 100) / 100;
            }
            const r = Number(this.roundOff) || 0;
            return Math.max(0, Math.round((total + r) * 100) / 100);
        },

        recalc() {
            // Trigger Alpine reactivity across all dependent getters
        },

        formatCurrency(n) {
            return '₹' + Number(n || 0).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        // Commercial Scheme Helpers (preserved)
        eligibleSchemes() {
            const date = this.invoiceDate;
            const validLines = this.lines.filter(l => l.product_id && (Number(l.quantity) || 0) > 0);

            return this.schemes.filter(s => {
                if (s.starts_on && date < s.starts_on) return false;
                if (s.ends_on && date > s.ends_on) return false;
                if (validLines.length === 0) return true;

                if (s.product_ids && s.product_ids.length > 0) {
                    return validLines.some(l => s.product_ids.includes(Number(l.product_id)));
                }

                if (s.brand_id) {
                    return validLines.some(l => {
                        const p = this.products.find(x => String(x.id) === String(l.product_id));
                        return p && Number(p.brand_id) === Number(s.brand_id);
                    });
                }

                return true;
            });
        },

        currentSchemeDetails() {
            if (!this.selectedSchemeId) return null;
            const s = this.schemes.find(x => String(x.id) === String(this.selectedSchemeId));
            if (!s) return null;

            const validLines = this.lines.filter(l => l.product_id && (Number(l.quantity) || 0) > 0);
            let qualifying = [];

            if (s.product_ids && s.product_ids.length > 0) {
                qualifying = validLines.filter(l => s.product_ids.includes(Number(l.product_id)));
            } else if (s.brand_id) {
                qualifying = validLines.filter(l => {
                    const p = this.products.find(x => String(x.id) === String(l.product_id));
                    return p && Number(p.brand_id) === Number(s.brand_id);
                });
            } else {
                qualifying = validLines;
            }

            let qualified = 0;
            if (s.basis === 'quantity') {
                qualified = qualifying.reduce((sum, l) => sum + (Number(l.quantity) || 0), 0);
            } else {
                qualified = qualifying.reduce((sum, l) => sum + this.lineTotal(l), 0);
            }

            const sortedSlabs = (s.slabs || []).slice().sort((a, b) => Number(b.from_value) - Number(a.from_value));
            const matchedSlab = sortedSlabs.find(sl => {
                const from = Number(sl.from_value) || 0;
                const to = (sl.to_value !== null && sl.to_value !== '') ? Number(sl.to_value) : null;
                return qualified >= from && (to === null || qualified <= to);
            });

            let benefit = 0;
            if (matchedSlab) {
                if (Number(matchedSlab.benefit_amount) > 0) {
                    benefit = Number(matchedSlab.benefit_amount);
                } else {
                    benefit = qualified * (Number(matchedSlab.benefit_percent) || 0) / 100;
                }
            }

            const minSlab = sortedSlabs.length > 0 ? Math.min(...sortedSlabs.map(x => Number(x.from_value))) : 0;

            return {
                scheme: s,
                qualifyingCount: qualifying.length,
                qualified: Math.round(qualified * 100) / 100,
                slab: matchedSlab,
                benefit: Math.round(benefit * 100) / 100,
                minSlab: minSlab,
            };
        },

        selectedSchemeBenefit() {
            const det = this.currentSchemeDetails();
            return det ? det.benefit : 0;
        },
    };
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/sales/invoices/create.blade.php ENDPATH**/ ?>