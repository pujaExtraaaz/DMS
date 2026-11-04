<?php $__env->startSection('title', 'Book Order'); ?>

<?php $__env->startSection('content'); ?>

<?php
    $customersList = $customers->map(function ($c) {
        $phone = filled($c->phone) ? trim((string)$c->phone) : '';
        $label = $c->name . ($phone !== '' ? ' - ' . $phone : '');
        return [
            'id' => (string) $c->id,
            'name' => $c->name,
            'phone' => $phone,
            'label' => $label,
        ];
    })->values()->all();
?>

<div
    x-data="orderBuilder()"
    @keydown.window="handleShortcut($event)"
    @click.window="closeProductDropdowns($event)"
    class="space-y-6 pb-28 lg:pb-8"
>

    
    <?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'Book Order','description' => 'Fast order entry with live customer pricing.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Book Order','description' => 'Fast order entry with live customer pricing.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('actions', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('orders.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('orders.index'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                Back
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


    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
        <?php if (isset($component)) { $__componentOriginal746de018ded8594083eb43be3f1332e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal746de018ded8594083eb43be3f1332e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.alert','data' => ['type' => 'error']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'error']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <ul class="list-disc list-inside space-y-1 text-sm">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <li><?php echo e($error); ?></li>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </ul>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $attributes = $__attributesOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__attributesOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $component = $__componentOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__componentOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


    
    <form
        method="POST"
        action="<?php echo e(route('orders.store')); ?>"
        @submit="beforeSubmit"
        class="space-y-6"
    >

        <?php echo csrf_field(); ?>


        
        <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Order Details']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Order Details']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>


            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                
                <div class="space-y-1 relative" @click.outside="closeCustomerDropdown()">
                    <div class="flex items-center justify-between mb-1">
                        <label for="customer_search_input" class="block text-sm font-medium text-slate-700">
                            Customer <span class="text-red-500">*</span>
                        </label>
                        <a href="<?php echo e(route('masters.customers.create', ['return_to' => 'orders.create'])); ?>"
                           @click="saveDraftAndRedirect($event, '<?php echo e(route('masters.customers.create', ['return_to' => 'orders.create'])); ?>')"
                           class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            + Add Customer
                        </a>
                    </div>

                    
                    <select
                        name="customer_id"
                        id="customer_id"
                        x-model="customerId"
                        @change="customerChanged()"
                        data-no-search="true"
                        class="hidden"
                        tabindex="-1"
                        aria-hidden="true"
                    >
                        <option value="">Select customer</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <?php
                                $cPhone = filled($customer->phone) ? trim((string)$customer->phone) : '';
                                $cLabel = $customer->name . ($cPhone !== '' ? ' - ' . $cPhone : '');
                            ?>
                            <option
                                value="<?php echo e($customer->id); ?>"
                                <?php if(
                                    old('customer_id') == $customer->id ||
                                    (!old('customer_id') && request('customer_id') == $customer->id)
                                ): echo 'selected'; endif; ?>
                            >
                                <?php echo e($cLabel); ?>

                            </option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>

                    
                    <div class="relative">
                        <div class="relative">
                            <input
                                type="text"
                                id="customer_search_input"
                                x-model="customerSearch"
                                @focus="openCustomerDropdown()"
                                @click="openCustomerDropdown()"
                                @input="onCustomerInput($event.target.value)"
                                @keydown.escape="closeCustomerDropdown()"
                                @keydown.arrow-down.prevent="navigateCustomerOptions(1)"
                                @keydown.arrow-up.prevent="navigateCustomerOptions(-1)"
                                @keydown.enter.prevent="selectHighlightedCustomer()"
                                placeholder="Search by customer name or mobile number..."
                                autocomplete="off"
                                class="block w-full rounded-lg border-gray-300 shadow-sm text-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 pr-16 <?php $__errorArgs = ['customer_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            />

                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                                
                                <svg x-show="customersLoading" x-cloak class="w-4 h-4 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>

                                
                                <button
                                    type="button"
                                    x-show="customerId && !customersLoading"
                                    x-cloak
                                    @click="clearCustomer()"
                                    title="Clear selected customer"
                                    class="p-1 text-slate-400 hover:text-slate-600 rounded transition"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>

                                
                                <button
                                    type="button"
                                    @click="toggleCustomerDropdown()"
                                    tabindex="-1"
                                    title="Toggle customer list"
                                    class="p-1 text-slate-400 hover:text-slate-600 rounded transition"
                                >
                                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': customerDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        
                        <div
                            x-show="customerDropdownOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100"
                        >
                            
                            <div x-show="customersLoading" class="p-3 text-center text-xs text-slate-500 flex items-center justify-center gap-2">
                                <svg class="w-4 h-4 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Loading customers...</span>
                            </div>

                            
                            <div x-show="customersError && !customersLoading" class="p-3 text-center text-xs text-red-600 bg-red-50">
                                <p x-text="customersError"></p>
                                <button type="button" @click="fetchCustomers(customerSearch)" class="mt-1 text-xs font-semibold text-indigo-600 hover:underline">
                                    Retry
                                </button>
                            </div>

                            <template x-for="(c, index) in filteredCustomers" :key="c.id">
                                <div
                                    @click="selectCustomer(c)"
                                    @mouseenter="customerHighlightedIndex = index"
                                    :class="{
                                        'bg-indigo-50 text-indigo-900 font-semibold': String(customerId) === String(c.id),
                                        'bg-slate-50': customerHighlightedIndex === index && String(customerId) !== String(c.id)
                                    }"
                                    class="p-2.5 hover:bg-indigo-50 hover:text-indigo-900 cursor-pointer text-xs flex items-center justify-between transition-colors"
                                >
                                    <span class="font-medium text-slate-800" x-text="c.label"></span>
                                    <span x-show="c.phone" class="font-mono text-[11px] text-slate-400" x-text="c.phone"></span>
                                </div>
                            </template>
                            <div x-show="!customersLoading && !customersError && filteredCustomers.length === 0" class="p-3 text-center text-xs text-slate-500 italic">
                                No matching customers found.
                            </div>
                        </div>
                    </div>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['customer_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>


                
                <div class="space-y-1">
                    <label for="order_date" class="block text-sm font-medium text-slate-700">
                        Order Date <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="date"
                        name="order_date"
                        id="order_date"
                        x-model="orderDate"
                        @change="orderDateChanged()"
                        @input="orderDateChanged()"
                        value="<?php echo e(old('order_date', now()->toDateString())); ?>"
                        required
                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['order_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    />
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['order_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div class="space-y-1">
                    <div class="flex items-center justify-between mb-1">
                        <label for="due_date" class="block text-sm font-medium text-slate-700">
                            Due Date
                        </label>
                        <span x-show="customerCreditDays[customerId] && Number(customerCreditDays[customerId]) > 0" x-cloak
                              class="text-[11px] font-medium text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-100">
                            Terms: <span x-text="customerCreditDays[customerId]"></span> Days
                        </span>
                    </div>

                    <input
                        type="date"
                        name="due_date"
                        id="due_date"
                        x-model="dueDate"
                        @input="manualDueDateChanged()"
                        @change="manualDueDateChanged()"
                        value="<?php echo e(old('due_date')); ?>"
                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['due_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    />

                    
                    <div class="flex items-center gap-1.5 pt-1.5 flex-wrap">
                        <span class="text-xs font-medium text-slate-400">Quick:</span>
                        <button
                            type="button"
                            @click="setCreditDays(30)"
                            :class="creditDays === 30 && !isManualDueDate
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs ring-1 ring-indigo-500'
                                : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'"
                            class="px-2.5 py-1 text-xs font-semibold rounded-md border transition-colors inline-flex items-center justify-center"
                        >
                            30 Days
                        </button>
                        <button
                            type="button"
                            @click="setCreditDays(45)"
                            :class="creditDays === 45 && !isManualDueDate
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs ring-1 ring-indigo-500'
                                : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'"
                            class="px-2.5 py-1 text-xs font-semibold rounded-md border transition-colors inline-flex items-center justify-center"
                        >
                            45 Days
                        </button>
                        <button
                            type="button"
                            @click="setCreditDays(50)"
                            :class="creditDays === 50 && !isManualDueDate
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs ring-1 ring-indigo-500'
                                : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'"
                            class="px-2.5 py-1 text-xs font-semibold rounded-md border transition-colors inline-flex items-center justify-center"
                        >
                            50 Days
                        </button>

                        
                        <template x-if="customerCreditDays[customerId] && Number(customerCreditDays[customerId]) > 0 && ![30, 45, 50].includes(Number(customerCreditDays[customerId]))">
                            <button
                                type="button"
                                @click="setCreditDays(Number(customerCreditDays[customerId]))"
                                :class="creditDays === Number(customerCreditDays[customerId]) && !isManualDueDate
                                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs ring-1 ring-indigo-500'
                                    : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border-indigo-200'"
                                class="px-2.5 py-1 text-xs font-semibold rounded-md border transition-colors inline-flex items-center justify-center"
                            >
                                <span x-text="`${customerCreditDays[customerId]} Days (Party)`"></span>
                            </button>
                        </template>
                    </div>

                    
                    <div class="pt-1 flex items-center justify-between text-xs">
                        <p class="text-slate-500">
                            Due Date Preview:
                            <strong class="font-semibold text-slate-800" x-text="dueDatePreview || '—'"></strong>
                        </p>
                        <span x-show="isManualDueDate" x-cloak class="text-[10px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200 font-medium">
                            Manual
                        </span>
                    </div>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['due_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

            </div>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">

                
                <div class="space-y-1">
                    <?php if (isset($component)) { $__componentOriginal65bd7e7dbd93cec773ad6501ce127e46 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.input','data' => ['name' => 'created_by_name','label' => 'Created By','value' => old('created_by_name'),'maxlength' => '100','placeholder' => 'Person Who Created This Order (Optional)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'created_by_name','label' => 'Created By','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('created_by_name')),'maxlength' => '100','placeholder' => 'Person Who Created This Order (Optional)']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46)): ?>
<?php $attributes = $__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46; ?>
<?php unset($__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal65bd7e7dbd93cec773ad6501ce127e46)): ?>
<?php $component = $__componentOriginal65bd7e7dbd93cec773ad6501ce127e46; ?>
<?php unset($__componentOriginal65bd7e7dbd93cec773ad6501ce127e46); ?>
<?php endif; ?>
                </div>

                
                <div class="w-full md:max-w-lg">

                    <div class="mb-2 flex items-center justify-between">
                        <label class="block text-sm font-semibold text-gray-800">
                            Discount
                        </label>

                        <span
                            class="text-xs font-medium text-gray-400"
                            x-text="discountType === 'flat'
                                ? 'Fixed amount'
                                : 'Percentage'"
                        ></span>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-1.5 shadow-sm">

                        
                        <div class="grid grid-cols-2 gap-1 rounded-lg bg-gray-100 p-1">

                            
                            <button
                                type="button"
                                @click="
                                    discountType = 'flat';
                                    discountValue = 0;
                                    discountChanged();
                                "
                                :class="
                                    discountType === 'flat'
                                        ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-gray-200'
                                        : 'text-gray-500 hover:text-gray-700'
                                "
                                class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md px-3 text-xs font-semibold transition"
                            >
                                <span
                                    class="text-sm font-bold"
                                >
                                    ₹
                                </span>

                                <span>Flat</span>
                            </button>

                            
                            <button
                                type="button"
                                @click="
                                    discountType = 'percent';
                                    discountValue = 0;
                                    discountChanged();
                                "
                                :class="
                                    discountType === 'percent'
                                        ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-gray-200'
                                        : 'text-gray-500 hover:text-gray-700'
                                "
                                class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md px-3 text-xs font-semibold transition"
                            >
                                <span
                                    class="text-sm font-bold"
                                >
                                    %
                                </span>

                                <span>Percent</span>
                            </button>

                        </div>

                        
                        <div class="mt-2">

                            <div
                                class="relative flex items-center overflow-hidden rounded-lg border border-gray-300 bg-white transition focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-100"
                            >

                                
                                <span
                                    x-show="discountType === 'flat'"
                                    x-cloak
                                    class="flex h-12 w-12 shrink-0 items-center justify-center border-r border-gray-200 bg-gray-50 text-base font-semibold text-gray-600"
                                >
                                    ₹
                                </span>

                                
                                <input
                                    type="number"
                                    name="discount_value"
                                    x-model="discountValue"
                                    min="0"
                                    :max="discountType === 'percent' ? 100 : null"
                                    step="0.01"
                                    class="discount-value-input h-12 w-full border-0 bg-transparent px-4 text-base font-semibold text-gray-900 outline-none focus:ring-0"
                                    :class="discountType === 'percent' ? 'pl-12 pr-12' : ''"
                                    :placeholder="discountType === 'flat' ? 'Enter Discount Amount' : 'Enter Discount Percentage'"
                                    @input="discountChanged()"
                                >

                                
                                <span
                                    x-show="discountType === 'percent'"
                                    x-cloak
                                    class="flex h-12 w-12 shrink-0 items-center justify-center border-l border-gray-200 bg-gray-50 text-base font-semibold text-gray-600"
                                >
                                    %
                                </span>

                            </div>

</div>

                        
                        <div class="mt-2 flex items-center gap-2 px-1">

                            <svg
                                class="h-3.5 w-3.5 shrink-0 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z"
                                />
                            </svg>

                            <p class="text-xs text-gray-500">
                                <template x-if="discountType === 'flat'">
                                    <span>Enter a fixed rupee amount.</span>
                                </template>

                                <template x-if="discountType === 'percent'">
                                    <span>Enter a percentage between 0% and 100%.</span>
                                </template>
                            </p>

                        </div>

                        
                        <div
                            x-show="discountAmount > 0"
                            x-cloak
                            class="mt-3 flex items-center justify-between rounded-lg bg-indigo-50 px-3 py-2.5"
                        >

                            <span class="text-xs font-medium text-indigo-700">
                                Applied discount
                            </span>

                            <span class="text-sm font-bold text-indigo-700">
                                ₹<span x-text="formatMoney(discountAmount)"></span>
                            </span>

                        </div>

                    </div>

                    
                    <input
                        type="hidden"
                        name="discount_amount"
                        x-model="discountAmount"
                    >

                </div>

            </div>

            <!-- Party Addresses Selection -->
            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-4 mt-6" x-show="customerId" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-200">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Billing &amp; Delivery Addresses</span>
                        <span x-show="addressesLoading" class="inline-flex items-center gap-1 text-xs text-indigo-600 font-medium">
                            <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Loading...
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-500" x-text="addressesLoading ? 'Fetching customer addresses...' : `${partyAddresses.length} address(es) available`"></span>
                        <button type="button" @click="fetchCustomerAddresses(true)" title="Refresh addresses" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium inline-flex items-center gap-1">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Refresh</span>
                        </button>
                    </div>
                </div>

                
                <div x-show="addressesErrorMessage" x-cloak class="p-3 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span x-text="addressesErrorMessage"></span>
                    </div>
                    <button type="button" @click="fetchCustomerAddresses(true)" class="underline font-semibold hover:text-red-900">Retry</button>
                </div>

                
                <div x-show="!addressesLoading && partyAddresses.length === 0" x-cloak class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-800 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>No saved addresses found for this customer. Please add an address to customer profile.</span>
                    </div>
                    <a :href="`<?php echo e(url('/masters/customers')); ?>/${customerId}/edit#addresses`" target="_blank" class="inline-flex items-center gap-1 font-semibold text-indigo-700 hover:text-indigo-900 underline">
                        <span>+ Add Address</span>
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Billing Address Card -->
                    <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <label for="billing_address_id" class="block text-xs font-semibold text-slate-700 tracking-wider">
                                Billing Address
                            </label>
                            <span class="text-[11px] text-indigo-600 font-medium">Billed to</span>
                        </div>

                        <select name="billing_address_id" id="billing_address_id" x-model="selectedBillingId" @change="onBillingAddressChange()" data-no-search="true"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['billing_address_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <option value="">Select Billing Address</option>
                            <template x-for="addr in billingAddresses" :key="'bill-' + addr.id">
                                <option :value="addr.id" :selected="String(addr.id) === String(selectedBillingId)" x-text="`${addr.label} — ${addr.full_address}`"></option>
                            </template>
                        </select>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['billing_address_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <div x-show="selectedBillingAddress" x-cloak class="rounded-lg border border-slate-200 bg-slate-50/80 p-3 text-xs space-y-1.5 mt-2">
                            <template x-if="selectedBillingAddress">
                                <div>
                                    <div class="flex items-center justify-between gap-2 pb-1 border-b border-slate-200/60">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800" x-text="selectedBillingAddress.label || selectedBillingAddress.name || 'Billing Address'"></span>
                                            <span x-show="selectedBillingAddress.is_default_billing" class="px-1.5 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-800 font-medium">Default</span>
                                        </div>
                                        <span x-show="selectedBillingAddress.type" class="px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200" x-text="selectedBillingAddress.type"></span>
                                    </div>
                                    <div x-show="selectedBillingAddress.contact_person || selectedBillingAddress.contact_phone" class="text-slate-600 font-medium pt-1">
                                        <span x-text="selectedBillingAddress.contact_person || ''"></span>
                                        <span x-show="selectedBillingAddress.contact_phone" class="text-slate-500" x-text="` (Ph: ${selectedBillingAddress.contact_phone})`"></span>
                                    </div>
                                    <div class="text-slate-700 pt-0.5">
                                        <span x-text="selectedBillingAddress.full_address"></span>
                                    </div>
                                    <div x-show="selectedBillingAddress.gstin" class="text-slate-600 font-mono text-[11px] pt-1">
                                        <span class="font-semibold text-slate-500">GSTIN:</span> <span class="font-bold text-slate-800" x-text="selectedBillingAddress.gstin"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        
                        <div x-show="!selectedBillingAddress && billingSnapshot" x-cloak class="rounded-lg border border-slate-200 bg-slate-50/80 p-3 text-xs text-slate-700 whitespace-pre-line mt-2" x-text="billingSnapshot"></div>

                        <input type="hidden" name="billing_address" :value="billingSnapshot">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['billing_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <!-- Delivery Address Card -->
                    <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <label for="shipping_address_id" class="block text-xs font-semibold text-slate-700 tracking-wider">
                                Delivery / Consignee Address
                            </label>
                            <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 cursor-pointer font-medium">
                                <input type="checkbox" id="same_as_billing" x-model="sameAsBilling" @change="onSameAsBillingToggle()"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>Same as Billing Address</span>
                            </label>
                        </div>

                        <div x-show="!sameAsBilling" x-cloak>
                            <select name="shipping_address_id" id="shipping_address_id" x-model="selectedShippingId" @change="onShippingAddressChange()" data-no-search="true"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['shipping_address_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <option value="">Select Delivery Address</option>
                                <template x-for="addr in deliveryAddresses" :key="'deliv-' + addr.id">
                                    <option :value="addr.id" :selected="String(addr.id) === String(selectedShippingId)" x-text="`${addr.label} — ${addr.full_address}`"></option>
                                </template>
                            </select>
                        </div>

                        
                        <template x-if="sameAsBilling">
                            <input type="hidden" name="shipping_address_id" :value="selectedBillingId">
                        </template>

                        <div x-show="sameAsBilling" class="text-xs text-slate-500 italic py-1 bg-slate-50 px-2.5 rounded border border-slate-200/50">
                            Delivery address matches the selected Billing Address.
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['shipping_address_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <div x-show="selectedShippingAddress" x-cloak class="rounded-lg border border-slate-200 bg-slate-50/80 p-3 text-xs space-y-1.5 mt-2">
                            <template x-if="selectedShippingAddress">
                                <div>
                                    <div class="flex items-center justify-between gap-2 pb-1 border-b border-slate-200/60">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800" x-text="selectedShippingAddress.label || selectedShippingAddress.name || 'Delivery Address'"></span>
                                            <span x-show="selectedShippingAddress.is_default_delivery" class="px-1.5 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-800 font-medium">Default</span>
                                        </div>
                                        <span x-show="selectedShippingAddress.type" class="px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200" x-text="selectedShippingAddress.type"></span>
                                    </div>
                                    <div x-show="selectedShippingAddress.contact_person || selectedShippingAddress.contact_phone" class="text-slate-600 font-medium pt-1">
                                        <span x-text="selectedShippingAddress.contact_person || ''"></span>
                                        <span x-show="selectedShippingAddress.contact_phone" class="text-slate-500" x-text="` (Ph: ${selectedShippingAddress.contact_phone})`"></span>
                                    </div>
                                    <div class="text-slate-700 pt-0.5">
                                        <span x-text="selectedShippingAddress.full_address"></span>
                                    </div>
                                    <div x-show="selectedShippingAddress.gstin" class="text-slate-600 font-mono text-[11px] pt-1">
                                        <span class="font-semibold text-slate-500">GSTIN:</span> <span class="font-bold text-slate-800" x-text="selectedShippingAddress.gstin"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        
                        <div x-show="!selectedShippingAddress && shippingSnapshot" x-cloak class="rounded-lg border border-slate-200 bg-slate-50/80 p-3 text-xs text-slate-700 whitespace-pre-line mt-2" x-text="shippingSnapshot"></div>

                        <input type="hidden" name="shipping_address" :value="shippingSnapshot">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['shipping_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Payment Terms & Advance Details']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Payment Terms & Advance Details']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>


            <div class="space-y-4">

                <div class="space-y-1">
                    <label for="payment_terms" class="block text-sm font-medium text-slate-700">
                        Payment Terms / Payment Schedule
                    </label>
                    <textarea
                        name="payment_terms"
                        id="payment_terms"
                        rows="3"
                        x-model="paymentTerms"
                        placeholder="e.g. 50% advance against Proforma Invoice, 30% after delivery, and the remaining 20% after completion."
                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['payment_terms'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    ><?php echo e(old('payment_terms')); ?></textarea>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['payment_terms'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">

                    
                    <div class="space-y-1">
                        <label for="advance_amount" class="block text-sm font-medium text-slate-700">
                            Advance Amount Received (₹)
                        </label>
                        <div class="relative rounded-lg shadow-sm">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 sm:text-sm">₹</span>
                            <input
                                type="number"
                                name="advance_amount"
                                id="advance_amount"
                                step="0.01"
                                min="0"
                                :max="grandTotal > 0 ? grandTotal : null"
                                x-model="advanceAmount"
                                @input="advanceAmountChanged()"
                                placeholder="0.00"
                                class="block w-full rounded-lg border-gray-300 pl-8 pr-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['advance_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            />
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['advance_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    
                    <div class="space-y-1">
                        <label for="advance_percentage" class="block text-sm font-medium text-slate-700">
                            Advance Percentage (%)
                        </label>
                        <div class="relative rounded-lg shadow-sm">
                            <input
                                type="number"
                                name="advance_percentage"
                                id="advance_percentage"
                                step="0.01"
                                min="0"
                                max="100"
                                x-model="advancePercentage"
                                @input="advancePercentageChanged()"
                                placeholder="0.00"
                                class="block w-full rounded-lg border-gray-300 pr-8 pl-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['advance_percentage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            />
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 sm:text-sm">%</span>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['advance_percentage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">
                            Balance Amount Pending (₹)
                        </label>
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm h-10">
                            <span class="font-bold text-indigo-700 text-base">
                                ₹<span x-text="formatMoney(balanceAmount)"></span>
                            </span>
                            <span class="text-xs text-slate-500" x-text="grandTotal > 0 ? `Total: ₹${formatMoney(grandTotal)}` : 'Auto-calculated'"></span>
                        </div>
                        <input type="hidden" name="balance_amount" :value="balanceAmount">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['balance_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                </div>

                
                <div class="rounded-lg bg-slate-50 p-3 border border-slate-200 flex flex-wrap items-center justify-between gap-4 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500">Order Grand Total:</span>
                        <strong class="text-slate-800 text-sm">₹<span x-text="formatMoney(grandTotal)"></span></strong>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500">Advance Received:</span>
                        <strong class="text-emerald-700 text-sm">₹<span x-text="formatMoney(advanceAmount)"></span></strong>
                        <span x-show="advancePercentage && Number(advancePercentage) > 0" x-cloak class="text-[11px] text-emerald-600">
                            (<span x-text="Number(advancePercentage).toFixed(1)"></span>%)
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500">Balance Pending:</span>
                        <strong class="text-indigo-700 text-sm">₹<span x-text="formatMoney(balanceAmount)"></span></strong>
                    </div>
                </div>

                
                <div x-show="grandTotal > 0 && Number(advanceAmount) > Number(grandTotal)" x-cloak class="p-2.5 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700 flex items-center gap-2">
                    <svg class="h-4 w-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Advance amount cannot exceed the grand total (₹<span x-text="formatMoney(grandTotal)"></span>).</span>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Sales & Reference Details']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Sales & Reference Details']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                
                <div class="space-y-1">
                    <label for="salesperson_id" class="block text-sm font-medium text-slate-700">
                        Salesperson Name
                    </label>
                    <select
                        name="salesperson_id"
                        id="salesperson_id"
                        x-model="salespersonId"
                        data-no-search="true"
                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['salesperson_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    >
                        <option value="">Select Salesperson (Optional)</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $salespersons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option
                                value="<?php echo e($sp->id); ?>"
                                <?php if(old('salesperson_id') == $sp->id): echo 'selected'; endif; ?>
                            >
                                <?php echo e($sp->name); ?>

                            </option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['salesperson_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div class="space-y-1">
                    <label for="reference_no" class="block text-sm font-medium text-slate-700">
                        Reference / Quotation Ref / Enquiry #
                    </label>
                    <input
                        type="text"
                        name="reference_no"
                        id="reference_no"
                        maxlength="100"
                        x-model="referenceNo"
                        value="<?php echo e(old('reference_no')); ?>"
                        placeholder="e.g. Quotation reference, enquiry number, customer PO"
                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['reference_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    />
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['reference_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
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



        
        <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Delivery Details']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Delivery Details']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                
                <div class="space-y-1">
                    <label for="terms_of_delivery" class="block text-sm font-medium text-slate-700">
                        Terms of Delivery
                    </label>
                    <select
                        name="terms_of_delivery"
                        id="terms_of_delivery"
                        x-model="termsOfDelivery"
                        data-no-search="true"
                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['terms_of_delivery'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    >
                        <option value="">Select Terms of Delivery</option>
                        <option value="freight_inclusive" <?php if(old('terms_of_delivery') === 'freight_inclusive'): echo 'selected'; endif; ?>>Freight Inclusive</option>
                        <option value="freight_exclusive" <?php if(old('terms_of_delivery') === 'freight_exclusive'): echo 'selected'; endif; ?>>Freight Exclusive</option>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['terms_of_delivery'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div class="space-y-1">
                    <label for="fulfilment_mode" class="block text-sm font-medium text-slate-700">
                        Fulfilment Mode
                    </label>
                    <select
                        name="fulfilment_mode"
                        id="fulfilment_mode"
                        data-no-search="true"
                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['fulfilment_mode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    >
                        <option value="">Select Fulfilment Mode (Optional)</option>
                        <option value="van" <?php if(old('fulfilment_mode') === 'van'): echo 'selected'; endif; ?>>Van / Load Sheet</option>
                        <option value="warehouse" <?php if(old('fulfilment_mode') === 'warehouse'): echo 'selected'; endif; ?>>Warehouse Reservation</option>
                    </select>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['fulfilment_mode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
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



        
        <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Supervisor / Caretaker Details']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Supervisor / Caretaker Details']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                
                <div class="space-y-1">
                    <label for="caretaker_phone" class="block text-sm font-medium text-slate-700">
                        Supervisor / Caretaker Contact Number
                    </label>
                    <input
                        type="tel"
                        name="caretaker_phone"
                        id="caretaker_phone"
                        maxlength="20"
                        x-model="caretakerPhone"
                        value="<?php echo e(old('caretaker_phone')); ?>"
                        placeholder="e.g. +91 98765 43210"
                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['caretaker_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    />
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['caretaker_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
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



        
        <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Architect Details']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Architect Details']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                
                <div class="space-y-1">
                    <label for="architect_name" class="block text-sm font-medium text-slate-700">
                        Architect Name / Firm
                    </label>
                    <input
                        type="text"
                        name="architect_name"
                        id="architect_name"
                        maxlength="255"
                        x-model="architectName"
                        value="<?php echo e(old('architect_name')); ?>"
                        placeholder="e.g. Ar. Rajesh Sharma / Studio Designs"
                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['architect_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    />
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['architect_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div class="space-y-1">
                    <label for="architect_phone" class="block text-sm font-medium text-slate-700">
                        Architect Contact Number
                    </label>
                    <input
                        type="tel"
                        name="architect_phone"
                        id="architect_phone"
                        maxlength="20"
                        x-model="architectPhone"
                        value="<?php echo e(old('architect_phone')); ?>"
                        placeholder="e.g. +91 98765 43210"
                        class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['architect_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 text-red-900 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    />
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['architect_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
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



        
        <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Products']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Products']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>


            <div class="mb-4 rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-700 flex items-center justify-between">
                <span>Default prices are fetched automatically for the selected customer and product. You can modify the unit price directly for any product line.</span>
            </div>


            
            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead>
                        <tr class="border-b bg-gray-50">

                            <th class="px-3 py-3 text-left">
                                Product
                            </th>

                            <th class="px-3 py-3 text-left">
                                Unit
                            </th>

                            <th class="px-3 py-3 text-right">
                                Qty
                            </th>

                            <th class="px-3 py-3 text-right">
                                Price
                            </th>

                            <th class="px-3 py-3 text-right">
                                Total
                            </th>

                            <th class="px-3 py-3"></th>

                        </tr>
                    </thead>


                    <tbody>

                        <template
                            x-for="(row, index) in rows"
                            :key="row.key"
                        >

                            <tr
                                class="border-b"
                                :class="row.productResultsOpen ? 'relative z-[100]' : 'relative z-0'"
                            >

                                
                                <td class="px-3 py-3 min-w-[300px]">

                                    <div class="relative">

                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs font-medium text-slate-600">Product <span class="text-red-500">*</span></span>
                                            <a
                                                href="<?php echo e(route('masters.products.create', ['return_to' => 'orders.create'])); ?>"
                                                @click="saveDraftAndRedirectToProduct($event, index)"
                                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1"
                                                title="Add a new product to master catalog"
                                            >
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                                <span>Add Product</span>
                                            </a>
                                        </div>

                                        
                                        <div class="relative">

                                            <input
                                                type="text"
                                                autocomplete="off"
                                                class="product-search block w-full rounded-lg border-gray-300 pr-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                x-model="row.productLabel"
                                                @focus="openProductDropdown(row, $event.target)"
                                                @click="openProductDropdown(row, $event.target)"
                                                @input="
                                                    searchProducts(row, $event.target.value);
                                                    positionProductDropdown(row, $event.target)
                                                "
                                                placeholder="Select Or Search Product..."
                                            >

                                            
                                            <button
                                                type="button"
                                                @mousedown.prevent="toggleProductDropdown(row, $event)"
                                                class="product-dropdown-arrow absolute inset-y-0 right-0 flex w-10 items-center justify-center text-gray-400 hover:text-gray-600"
                                                tabindex="-1"
                                            >
                                                <svg
                                                    class="h-4 w-4 transition-transform"
                                                    :class="row.productResultsOpen ? 'rotate-180' : ''"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 9l-7 7-7-7"
                                                    />
                                                </svg>
                                            </button>

                                        </div>

                                        
                                        <input
                                            type="hidden"
                                            :name="`items[${index}][product_id]`"
                                            x-model="row.productId"
                                        >

                                        
                                        <div 
                                            x-cloak 
                                            x-show="row.productResultsOpen"
                                            :style="`
                                                position: fixed;
                                                top: ${row.productDropdownTop}px;
                                                left: ${row.productDropdownLeft}px;
                                                width: ${row.productDropdownWidth}px;
                                                z-index: 999999;
                                            `"
                                            class="product-dropdown max-h-72 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-2xl ring-1 ring-black/5"
                                        >

                                            
                                            <div
                                                x-show="row.productResults.length === 0"
                                                class="px-4 py-3 text-sm text-gray-500 text-center"
                                            >
                                                <p>No products found.</p>
                                                <a
                                                    href="<?php echo e(route('masters.products.create', ['return_to' => 'orders.create'])); ?>"
                                                    @mousedown.prevent="saveDraftAndRedirectToProduct($event, index)"
                                                    class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                                >
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    <span>+ Add Product</span>
                                                </a>
                                            </div>

                                            
                                            <template
                                                x-for="product in row.productResults"
                                                :key="product.id"
                                            >

                                                <button
                                                    type="button"
                                                    @mousedown.prevent="selectProduct(row, product)"
                                                    class="block w-full border-b border-gray-100 px-4 py-2.5 text-left transition last:border-b-0 hover:bg-indigo-50"
                                                >

                                                    <div class="flex items-center justify-between gap-3">

                                                        <div class="min-w-0">

                                                            <div
                                                                class="truncate font-medium text-gray-900"
                                                                x-text="product.name"
                                                            ></div>

                                                            <div
                                                                class="mt-0.5 text-xs text-gray-500"
                                                                x-text="product.sku
                                                                    ? 'Part No: ' + product.sku
                                                                    : 'No Part Number'"
                                                            ></div>

                                                        </div>

                                                        <span
                                                            class="shrink-0 rounded bg-gray-100 px-2 py-1 text-xs text-gray-500"
                                                            x-text="product.base_uom_name || ''"
                                                        ></span>

                                                    </div>

                                                </button>

                                            </template>

                                            
                                            <div class="sticky bottom-0 border-t border-slate-100 bg-slate-50 px-3 py-2 text-center">
                                                <a
                                                    href="<?php echo e(route('masters.products.create', ['return_to' => 'orders.create'])); ?>"
                                                    @mousedown.prevent="saveDraftAndRedirectToProduct($event, index)"
                                                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    <span>+ Add Product</span>
                                                </a>
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                
                                <td class="px-3 py-3 min-w-[180px]">

                                    <select
                                        :name="`items[${index}][uom_id]`"
                                        x-model="row.uomId"
                                        @change="resolvePrice(row)"
                                        :disabled="!row.productId || row.uoms.length === 0"
                                        data-no-search="true"
                                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                    >

                                        <option value="">
                                            Select Unit
                                        </option>

                                        <template
                                            x-for="uom in row.uoms"
                                            :key="uom.id"
                                        >

                                            <option
                                                :value="uom.id"
                                                x-text="uom.label"
                                            ></option>

                                        </template>

                                    </select>

                                </td>


                                
                                <td class="px-3 py-3 min-w-[120px]">

                                    <input
                                        type="number"
                                        min="0.0001"
                                        step="0.0001"
                                        :name="`items[${index}][quantity]`"
                                        x-model="row.quantity"
                                        @input="quantityChanged(row)"
                                        class="block w-full rounded-lg border-gray-300 text-sm text-right focus:border-indigo-500 focus:ring-indigo-500"
                                    >

                                </td>


                                
                                <td class="px-3 py-3 min-w-[140px]">
                                    <div class="relative rounded-lg shadow-sm">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span class="text-gray-400 text-sm">₹</span>
                                        </div>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :name="`items[${index}][unit_price]`"
                                            x-model="row.unitPrice"
                                            @input="priceChanged(row)"
                                            placeholder="0.00"
                                            class="block w-full rounded-lg border-gray-300 pl-7 pr-3 text-sm text-right font-mono focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                        <span
                                            x-show="row.priceLoading"
                                            x-cloak
                                            class="absolute right-2 top-2 text-[10px] font-medium text-indigo-500 bg-indigo-50 px-1 rounded pointer-events-none"
                                        >
                                            ...
                                        </span>
                                    </div>
                                </td>


                                
                                <td class="px-3 py-3 text-right whitespace-nowrap">

                                    ₹<span
                                        x-text="formatMoney(row.lineTotal)"
                                    ></span>

                                </td>


                                
                                <td class="px-3 py-3 text-right">

                                    <button
                                        type="button"
                                        @click="removeRow(index)"
                                        :disabled="rows.length === 1"
                                        class="text-sm font-medium text-red-600 hover:text-red-800 disabled:opacity-30 disabled:cursor-not-allowed"
                                    >
                                        Remove
                                    </button>

                                </td>

                            </tr>

                        </template>

                    </tbody>

                </table>

            </div>


            
            <div class="mt-5 flex flex-wrap items-center justify-between gap-4">

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        id="btn-add-line-table"
                        @click="addRow()"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        title="Add product line"
                    >
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Line</span>
                    </button>
                </div>


                <div class="text-right">

                    <p class="text-xs uppercase font-medium text-slate-500 tracking-wider">
                        Order Total Preview
                    </p>

                    <p class="text-2xl font-bold text-slate-900">
                        ₹<span x-text="formatMoney(grandTotal)">0.00</span>
                    </p>

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


            <?php if (isset($component)) { $__componentOriginal65bd7e7dbd93cec773ad6501ce127e46 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.input','data' => ['name' => 'notes','label' => 'Notes','value' => old('notes'),'placeholder' => 'Optional Order Notes...']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'notes','label' => 'Notes','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('notes')),'placeholder' => 'Optional Order Notes...']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46)): ?>
<?php $attributes = $__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46; ?>
<?php unset($__attributesOriginal65bd7e7dbd93cec773ad6501ce127e46); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal65bd7e7dbd93cec773ad6501ce127e46)): ?>
<?php $component = $__componentOriginal65bd7e7dbd93cec773ad6501ce127e46; ?>
<?php unset($__componentOriginal65bd7e7dbd93cec773ad6501ce127e46); ?>
<?php endif; ?>


            <div class="mt-6 flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-slate-100">

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        id="btn-save-order"
                        :disabled="submitting"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                        title="Save Order (Ctrl + Enter)"
                    >
                        <svg x-show="!submitting" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-show="!submitting">Save Order</span>
                        <span x-show="submitting" x-cloak>Saving Order...</span>
                    </button>

                    <a
                        href="<?php echo e(route('orders.index')); ?>"
                        class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-900 transition"
                    >
                        Cancel
                    </a>
                </div>

                <div class="text-xs text-slate-500 flex items-center gap-3">
                    <span><kbd class="px-1.5 py-0.5 rounded bg-slate-100 border text-slate-600 font-mono text-[11px]">Ctrl + Enter</kbd> to save</span>
                    <span><kbd class="px-1.5 py-0.5 rounded bg-slate-100 border text-slate-600 font-mono text-[11px]">A</kbd> to add line</span>
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
<?php $component->withAttributes(['targetSelect' => 'customer_id']); ?>
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

</div>

<?php $__env->stopSection(); ?>


<?php $__env->startPush('scripts'); ?>

<script>

function orderBuilder() {
    return {

            customerId: <?php echo json_encode(old('customer_id', request('customer_id')), 512) ?>,
            customersList: <?php echo json_encode($customersList, 15, 512) ?>,
            customersLoading: false,
            customersError: '',
            customerSearchDebounce: null,
            customerSearch: '',
            customerLabel: '',
            customerDropdownOpen: false,
            customerHighlightedIndex: -1,
            orderDate: <?php echo json_encode(old('order_date', now()->toDateString()), 512) ?>,
            dueDate: <?php echo json_encode(old('due_date', ''), 512) ?>,
            creditDays: null,
            isManualDueDate: false,
            dueDatePreview: '',
            customerCreditDays: <?php echo json_encode($customers->mapWithKeys(fn ($c) => [(string)$c->id => (int) ($c->credit_days ?? 0)]), 15, 512) ?>,
            productsLoading: false,
            productsError: '',

            async fetchCustomers(query = '') {
                this.customersLoading = true;
                this.customersError = '';
                try {
                    const url = <?php echo json_encode(route('orders.search.customers'), 15, 512) ?> + (query ? '?q=' + encodeURIComponent(query) : '');
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) {
                        throw new Error(`Failed to load customers (HTTP ${res.status})`);
                    }
                    const data = await res.json();
                    if (Array.isArray(data)) {
                        const map = new Map(this.customersList.map(c => [String(c.id), c]));
                        data.forEach(c => {
                            map.set(String(c.id), {
                                id: String(c.id),
                                name: c.name,
                                phone: c.phone || '',
                                label: c.label || (c.name + (c.phone ? ' - ' + c.phone : '')),
                                credit_days: c.credit_days !== undefined ? c.credit_days : 0,
                            });
                            if (c.credit_days !== undefined) {
                                this.customerCreditDays[String(c.id)] = Number(c.credit_days);
                            }
                        });
                        this.customersList = Array.from(map.values());

                        if (this.customerId) {
                            const found = this.customersList.find(c => String(c.id) === String(this.customerId));
                            if (found) {
                                this.customerLabel = found.label;
                                if (!this.customerSearch || this.customerSearch === found.name) {
                                    this.customerSearch = found.label;
                                }
                            }
                        }
                    }
                } catch (e) {
                    console.error('Customer fetch error:', e);
                    this.customersError = 'Failed to load customers. Please check network and retry.';
                } finally {
                    this.customersLoading = false;
                }
            },

            onCustomerInput(val) {
                this.customerSearch = val;
                this.openCustomerDropdown();
                clearTimeout(this.customerSearchDebounce);
                this.customerSearchDebounce = setTimeout(() => {
                    this.fetchCustomers(this.customerSearch);
                }, 250);
            },

            async fetchProducts(query = '') {
                this.productsLoading = true;
                this.productsError = '';
                try {
                    const url = <?php echo json_encode(route('orders.search.products'), 15, 512) ?> + (query ? '?q=' + encodeURIComponent(query) : '');
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (Array.isArray(data)) {
                            const map = new Map(this.products.map(p => [String(p.id), p]));
                            data.forEach(p => {
                                map.set(String(p.id), {
                                    id: String(p.id),
                                    name: p.name,
                                    sku: p.sku || '',
                                    tax_rate: Number(p.tax_rate || 0),
                                    base_uom_name: p.base_uom_name || '',
                                    selling_price: p.selling_price || null,
                                });
                            });
                            this.products = Array.from(map.values());
                        }
                    }
                } catch (e) {
                    console.error('Products fetch error:', e);
                } finally {
                    this.productsLoading = false;
                }
            },

            paymentTerms: <?php echo json_encode(old('payment_terms', ''), 512) ?>,
            advanceAmount: <?php echo json_encode(old('advance_amount', 0), 512) ?>,
            advancePercentage: <?php echo json_encode(old('advance_percentage', ''), 512) ?>,
            balanceAmount: 0,
            salespersonId: <?php echo json_encode(old('salesperson_id', ''), 512) ?>,
            referenceNo: <?php echo json_encode(old('reference_no', ''), 512) ?>,
            termsOfDelivery: <?php echo json_encode(old('terms_of_delivery', ''), 512) ?>,
            caretakerPhone: <?php echo json_encode(old('caretaker_phone', ''), 512) ?>,
            architectName: <?php echo json_encode(old('architect_name', ''), 512) ?>,
            architectPhone: <?php echo json_encode(old('architect_phone', ''), 512) ?>,

            products: <?php echo e(Illuminate\Support\Js::from(
                $products->map(fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'base_uom_name' => $product->baseUom?->name,
                ])->values()
            )); ?>,

            discountType: <?php echo json_encode(old('discount_type', 'flat'), 512) ?>,

            discountValue: <?php echo json_encode(
                old(
                    'discount_value', old('discount_amount', 0)
                )) ?>,

            discountAmount: <?php echo json_encode(
                old('discount_amount', 0), 512) ?>,

            rows: [],

            submitting: false,

            nextKey: 1,

            partyAddresses: [],
            addressesLoading: false,
            addressesErrorMessage: '',
            defaultBillingId: '',
            defaultDeliveryId: '',
            selectedBillingId: <?php echo json_encode(old('billing_address_id', ''), 512) ?>,
            selectedShippingId: <?php echo json_encode(old('shipping_address_id', ''), 512) ?>,
            sameAsBilling: <?php echo json_encode(old('_token') !== null ? (old('billing_address_id') == old('shipping_address_id')) : true, 15, 512) ?>,
            billingSnapshot: <?php echo json_encode(old('billing_address', ''), 512) ?>,
            shippingSnapshot: <?php echo json_encode(old('shipping_address', ''), 512) ?>,

            get filteredCustomers() {
                const q = (this.customerSearch || '').trim().toLowerCase();
                if (!q || (this.customerId && this.customerLabel && q === this.customerLabel.toLowerCase())) {
                    return this.customersList;
                }
                return this.customersList.filter(c => {
                    const nameMatch = c.name && c.name.toLowerCase().includes(q);
                    const phoneMatch = c.phone && c.phone.toLowerCase().includes(q);
                    const labelMatch = c.label && c.label.toLowerCase().includes(q);
                    return nameMatch || phoneMatch || labelMatch;
                });
            },

            openCustomerDropdown() {
                this.customerDropdownOpen = true;
                this.customerHighlightedIndex = -1;
            },

            closeCustomerDropdown() {
                this.customerDropdownOpen = false;
                this.customerHighlightedIndex = -1;
                if (this.customerId && this.customerLabel) {
                    this.customerSearch = this.customerLabel;
                } else if (!this.customerId) {
                    this.customerSearch = '';
                }
            },

            toggleCustomerDropdown() {
                if (this.customerDropdownOpen) {
                    this.closeCustomerDropdown();
                } else {
                    this.openCustomerDropdown();
                    this.$nextTick(() => {
                        document.getElementById('customer_search_input')?.focus();
                    });
                }
            },

            selectCustomer(c) {
                this.customerId = String(c.id);
                this.customerLabel = c.label;
                this.customerSearch = c.label;
                this.customerDropdownOpen = false;
                this.customerHighlightedIndex = -1;
                this.customerChanged();
            },

            clearCustomer() {
                this.customerId = '';
                this.customerLabel = '';
                this.customerSearch = '';
                this.customerDropdownOpen = false;
                this.customerHighlightedIndex = -1;
                this.customerChanged();
            },

            navigateCustomerOptions(dir) {
                if (!this.customerDropdownOpen) {
                    this.openCustomerDropdown();
                    return;
                }
                const count = this.filteredCustomers.length;
                if (count === 0) return;
                this.customerHighlightedIndex += dir;
                if (this.customerHighlightedIndex < 0) this.customerHighlightedIndex = count - 1;
                if (this.customerHighlightedIndex >= count) this.customerHighlightedIndex = 0;
            },

            selectHighlightedCustomer() {
                if (!this.customerDropdownOpen) {
                    this.openCustomerDropdown();
                    return;
                }
                if (this.customerHighlightedIndex >= 0 && this.customerHighlightedIndex < this.filteredCustomers.length) {
                    this.selectCustomer(this.filteredCustomers[this.customerHighlightedIndex]);
                } else if (this.filteredCustomers.length > 0) {
                    this.selectCustomer(this.filteredCustomers[0]);
                }
            },

            get billingAddresses() {
                if (!this.partyAddresses || this.partyAddresses.length === 0) return [];
                const filtered = this.partyAddresses.filter(a => !a.type || a.type === 'billing' || a.type === 'both');
                return filtered.length > 0 ? filtered : this.partyAddresses;
            },

            get deliveryAddresses() {
                if (!this.partyAddresses || this.partyAddresses.length === 0) return [];
                const filtered = this.partyAddresses.filter(a => !a.type || a.type === 'delivery' || a.type === 'shipping' || a.type === 'both');
                return filtered.length > 0 ? filtered : this.partyAddresses;
            },

            get selectedBillingAddress() {
                return this.partyAddresses.find(a => String(a.id) === String(this.selectedBillingId)) || null;
            },

            get selectedShippingAddress() {
                return this.partyAddresses.find(a => String(a.id) === String(this.selectedShippingId)) || null;
            },

        async init() {
            this.fetchCustomers();
            this.fetchProducts();

            const urlParams = new URLSearchParams(window.location.search);
            const newProductId = urlParams.get('new_product_id');
            const targetRowParam = urlParams.get('target_row');
            const isReturnedFromProduct = newProductId !== null || urlParams.get('returned_from') === 'product_create';
            const isReturnedFromCustomer = urlParams.has('customer_id');
            const isReturningFlow = isReturnedFromProduct || isReturnedFromCustomer;

            if (this.customerId) {
                const found = this.customersList.find(c => String(c.id) === String(this.customerId));
                if (found) {
                    this.customerLabel = found.label;
                    this.customerSearch = found.label;
                }
            }

            window.addEventListener('customer-quick-added', (e) => {
                if (e.detail && e.detail.id) {
                    const newId = String(e.detail.id);
                    const phone = (e.detail.phone || '').trim();
                    const label = e.detail.name + (phone ? ' - ' + phone : '');
                    const existing = this.customersList.find(c => String(c.id) === newId);
                    if (!existing) {
                        this.customersList.push({
                            id: newId,
                            name: e.detail.name,
                            phone: phone,
                            label: label,
                        });
                    }
                    this.customerId = newId;
                    this.customerLabel = label;
                    this.customerSearch = label;
                    this.customerChanged();
                }
            });

            // Restore draft if returning from Create Party / Create Product or if draft exists and no old() server values
            const savedDraftRaw = sessionStorage.getItem('dms_book_order_draft');
            let savedDraft = null;
            if (savedDraftRaw) {
                try {
                    savedDraft = JSON.parse(savedDraftRaw);
                } catch (e) {
                    savedDraft = null;
                }
            }

            const hasServerOldValues = <?php echo json_encode(old('_token') !== null, 15, 512) ?>;

            if (savedDraft && (isReturningFlow || !hasServerOldValues)) {
                if (savedDraft.customer_id && (!this.customerId || isReturnedFromProduct)) {
                    this.customerId = String(savedDraft.customer_id);
                    const found = this.customersList.find(c => String(c.id) === String(this.customerId));
                    if (found) {
                        this.customerLabel = found.label;
                        this.customerSearch = found.label;
                    } else if (savedDraft.customer_label) {
                        this.customerLabel = savedDraft.customer_label;
                        this.customerSearch = savedDraft.customer_label;
                    }
                }
                if (savedDraft.order_date) {
                    this.orderDate = savedDraft.order_date;
                }
                if (savedDraft.due_date) {
                    this.dueDate = savedDraft.due_date;
                    this.creditDays = savedDraft.credit_days !== undefined ? savedDraft.credit_days : null;
                    this.isManualDueDate = Boolean(savedDraft.is_manual_due_date);
                    this.dueDatePreview = this.formatDatePreview(this.dueDate);
                }
                if (savedDraft.fulfilment_mode) {
                    const el = document.querySelector('select[name="fulfilment_mode"]');
                    if (el) el.value = savedDraft.fulfilment_mode;
                }
                if (savedDraft.created_by_name) {
                    const el = document.querySelector('input[name="created_by_name"]');
                    if (el) el.value = savedDraft.created_by_name;
                }
                if (savedDraft.notes) {
                    const el = document.querySelector('textarea[name="notes"]');
                    if (el) el.value = savedDraft.notes;
                }
                if (savedDraft.discount_type) {
                    this.discountType = savedDraft.discount_type;
                }
                if (savedDraft.discount_value !== undefined && savedDraft.discount_value !== null) {
                    this.discountValue = savedDraft.discount_value;
                }
                if (savedDraft.discount_amount !== undefined && savedDraft.discount_amount !== null) {
                    this.discountAmount = savedDraft.discount_amount;
                }
                if (savedDraft.payment_terms) {
                    this.paymentTerms = savedDraft.payment_terms;
                }
                if (savedDraft.advance_amount !== undefined) {
                    this.advanceAmount = savedDraft.advance_amount;
                }
                if (savedDraft.advance_percentage !== undefined) {
                    this.advancePercentage = savedDraft.advance_percentage;
                }
                if (savedDraft.salesperson_id) {
                    this.salespersonId = savedDraft.salesperson_id;
                }
                if (savedDraft.reference_no) {
                    this.referenceNo = savedDraft.reference_no;
                }
                if (savedDraft.terms_of_delivery) {
                    this.termsOfDelivery = savedDraft.terms_of_delivery;
                }
                if (savedDraft.caretaker_phone) {
                    this.caretakerPhone = savedDraft.caretaker_phone;
                }
                if (savedDraft.architect_name) {
                    this.architectName = savedDraft.architect_name;
                }
                if (savedDraft.architect_phone) {
                    this.architectPhone = savedDraft.architect_phone;
                }
                if (savedDraft.billing_address_id) {
                    this.selectedBillingId = savedDraft.billing_address_id;
                }
                if (savedDraft.shipping_address_id) {
                    this.selectedShippingId = savedDraft.shipping_address_id;
                }
                if (savedDraft.same_as_billing !== undefined) {
                    this.sameAsBilling = Boolean(savedDraft.same_as_billing);
                }
                if (savedDraft.billing_address) {
                    this.billingSnapshot = savedDraft.billing_address;
                }
                if (savedDraft.shipping_address) {
                    this.shippingSnapshot = savedDraft.shipping_address;
                }
            }

            if (!this.dueDate && this.orderDate) {
                const partyDays = Number(this.customerCreditDays[this.customerId] || 0);
                if (partyDays > 0) {
                    this.setCreditDays(partyDays);
                } else {
                    this.setCreditDays(30);
                }
            } else if (this.dueDate) {
                this.dueDatePreview = this.formatDatePreview(this.dueDate);
            }
            this.computeBalance();

            if (this.customerId) {
                this.fetchCustomerAddresses();
            }

            const oldItems = <?php echo json_encode($initialItems, 15, 512) ?>;

            if (oldItems.length > 0) {
                oldItems.forEach(item => {
                    const row = this.emptyRow();
                    row.productId = item.product_id || '';
                    row.productLabel = item.product_name
                        ? `${item.product_name}${item.product_sku ? ' (' + item.product_sku + ')' : ''}`
                        : '';
                    row.selectedProductLabel = row.productLabel;
                    row.uomId = item.uom_id || '';
                    row.quantity = item.quantity || 1;
                    row.unitPrice = (item.unit_price !== undefined && item.unit_price !== '' && item.unit_price !== null)
                        ? Number(item.unit_price).toFixed(2)
                        : '';
                    row.isManualPrice = (row.unitPrice !== '');
                    row.taxRate = Number(item.tax_rate || 0);
                    this.rows.push(row);
                    if (row.productId) {
                        this.loadUoms(row, row.uomId);
                    }
                });
            } else if (savedDraft && Array.isArray(savedDraft.rows) && savedDraft.rows.length > 0) {
                savedDraft.rows.forEach(item => {
                    const row = this.emptyRow();
                    row.productId = item.productId || '';
                    row.productLabel = item.productLabel || '';
                    row.selectedProductLabel = item.selectedProductLabel || item.productLabel || '';
                    row.uomId = item.uomId || '';
                    row.quantity = item.quantity || 1;
                    row.unitPrice = item.unitPrice !== undefined ? item.unitPrice : '';
                    row.isManualPrice = item.isManualPrice !== undefined ? Boolean(item.isManualPrice) : (row.unitPrice !== '');
                    row.lineTotal = item.lineTotal || 0;
                    row.taxRate = Number(item.taxRate || 0);
                    this.rows.push(row);
                    if (row.productId) {
                        this.loadUoms(row, row.uomId);
                    }
                });
            } else {
                this.rows.push(this.emptyRow());
            }

            // If a newly created product is returned, select it in the target row
            if (newProductId) {
                let targetIndex = -1;
                if (targetRowParam !== null && targetRowParam !== '') {
                    targetIndex = parseInt(targetRowParam, 10);
                } else if (savedDraft && savedDraft.target_row_index !== undefined) {
                    targetIndex = parseInt(savedDraft.target_row_index, 10);
                }

                if (isNaN(targetIndex) || targetIndex < 0 || targetIndex >= this.rows.length) {
                    if (this.rows.length > 0 && !this.rows[this.rows.length - 1].productId) {
                        targetIndex = this.rows.length - 1;
                    } else {
                        this.addRow();
                        targetIndex = this.rows.length - 1;
                    }
                }

                const targetRow = this.rows[targetIndex];
                let productObj = <?php echo json_encode($newProduct ?? null, 15, 512) ?>;
                if (!productObj) {
                    productObj = this.products.find(p => String(p.id) === String(newProductId));
                }
                if (!productObj) {
                    try {
                        const searchUrl = <?php echo json_encode(route('orders.search.products'), 15, 512) ?> + '?q=' + encodeURIComponent(newProductId);
                        const resp = await fetch(searchUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (resp.ok) {
                            const resList = await resp.json();
                            productObj = resList.find(p => String(p.id) === String(newProductId)) || resList[0];
                        }
                    } catch (e) {}
                }

                if (productObj) {
                    if (!this.products.some(p => String(p.id) === String(productObj.id))) {
                        this.products.push({
                            id: productObj.id,
                            name: productObj.name,
                            sku: productObj.sku || '',
                            base_uom_name: productObj.base_uom?.name || productObj.base_uom_name || '',
                        });
                    }
                    await this.selectProduct(targetRow, productObj);
                }

                this.$nextTick(() => {
                    const inputs = document.querySelectorAll('.product-search');
                    if (inputs && inputs[targetIndex]) {
                        inputs[targetIndex].focus();
                        inputs[targetIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            }

            // Clear temporary draft from storage so it does not persist across future independent sessions
            if (savedDraft) {
                try {
                    sessionStorage.removeItem('dms_book_order_draft');
                } catch (e) {}
            }

            // Clean query parameters from URL without reloading if returned from flow
            if (isReturningFlow && window.history && window.history.replaceState) {
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }

            window.addEventListener('scroll', () => {
                this.rows.forEach((row, idx) => {
                    if (!row.productResultsOpen) {
                        return;
                    }
                    const inputs = this.$root.querySelectorAll('.product-search');
                    if (inputs && inputs[idx]) {
                        this.positionProductDropdown(row, inputs[idx]);
                    }
                });
            }, true);

            window.addEventListener('resize', () => {
                this.rows.forEach((row, idx) => {
                    if (!row.productResultsOpen) {
                        return;
                    }
                    const inputs = this.$root.querySelectorAll('.product-search');
                    if (inputs && inputs[idx]) {
                        this.positionProductDropdown(row, inputs[idx]);
                    }
                });
            });

        },


        emptyRow() {
            return {
                key: this.nextKey++,
                productId: '',
                productLabel: '',
                selectedProductLabel: '',
                productResults: [],
                productResultsOpen: false,
                productDropdownTop: 0,
                productDropdownLeft: 0,
                productDropdownWidth: 0,
                uomId: '',
                uoms: [],
                quantity: 1,
                unitPrice: '',
                lineTotal: 0,
                taxRate: 0,
                isManualPrice: false,
                priceLoading: false,
                priceTimer: null,
                searchTimer: null,
            };
        },


        customerChanged() {

            if (this.customerId) {
                const found = this.customersList.find(c => String(c.id) === String(this.customerId));
                if (found) {
                    this.customerLabel = found.label;
                    this.customerSearch = found.label;
                }
            } else {
                this.customerLabel = '';
                this.customerSearch = '';
            }

            // Immediately clear previously selected addresses to prevent stale data
            this.partyAddresses = [];
            this.selectedBillingId = '';
            this.selectedShippingId = '';
            this.billingSnapshot = '';
            this.shippingSnapshot = '';
            this.sameAsBilling = true;
            this.addressesErrorMessage = '';

            this.fetchCustomerAddresses(true);

            if (!this.isManualDueDate) {
                const partyDays = Number(this.customerCreditDays[this.customerId] || 0);
                if (partyDays > 0) {
                    this.setCreditDays(partyDays);
                } else if (!this.creditDays) {
                    this.setCreditDays(30);
                } else {
                    this.recalcDueDate();
                }
            }

            this.rows.forEach(row => {
                if (
                    row.productId &&
                    row.uomId &&
                    Number(row.quantity) > 0 &&
                    !row.isManualPrice
                ) {
                    this.resolvePrice(row);
                }
            });

        },

        async fetchCustomerAddresses(forceReset = false) {
            if (!this.customerId) {
                this.partyAddresses = [];
                this.selectedBillingId = '';
                this.selectedShippingId = '';
                this.billingSnapshot = '';
                this.shippingSnapshot = '';
                this.addressesErrorMessage = '';
                return;
            }

            this.addressesLoading = true;
            this.addressesErrorMessage = '';

            try {
                const res = await fetch(`<?php echo e(url('/masters/customers')); ?>/${this.customerId}/addresses`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) {
                    throw new Error(`Failed to load addresses (HTTP ${res.status})`);
                }
                const data = await res.json();
                if (data.success) {
                    this.partyAddresses = data.addresses || [];
                    this.defaultBillingId = data.default_billing_id || '';
                    this.defaultDeliveryId = data.default_delivery_id || '';

                    if (forceReset || !this.selectedBillingId) {
                        this.selectedBillingId = data.default_billing_id || (this.billingAddresses[0]?.id ?? '');
                    }
                    if (forceReset || !this.selectedShippingId) {
                        this.selectedShippingId = (this.sameAsBilling ? this.selectedBillingId : (data.default_delivery_id || (this.deliveryAddresses[0]?.id ?? this.selectedBillingId)));
                    }

                    this.onBillingAddressChange();
                    if (!this.sameAsBilling) {
                        this.onShippingAddressChange();
                    } else {
                        this.selectedShippingId = this.selectedBillingId;
                        this.shippingSnapshot = this.billingSnapshot;
                    }
                } else {
                    this.addressesErrorMessage = data.message || 'Unable to retrieve customer addresses.';
                }
            } catch (e) {
                console.error('Failed to load party addresses:', e);
                this.addressesErrorMessage = 'Network error while loading addresses. Please try again.';
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
            }
        },

        onShippingAddressChange() {
            const addr = this.partyAddresses.find(a => String(a.id) === String(this.selectedShippingId));
            this.shippingSnapshot = addr ? (addr.snapshot || addr.full_address) : '';
        },

        onSameAsBillingToggle() {
            if (this.sameAsBilling) {
                this.selectedShippingId = this.selectedBillingId;
                this.shippingSnapshot = this.billingSnapshot;
            } else {
                if (this.defaultDeliveryId && String(this.defaultDeliveryId) !== String(this.selectedBillingId)) {
                    this.selectedShippingId = this.defaultDeliveryId;
                } else if (!this.selectedShippingId && this.deliveryAddresses.length > 0) {
                    this.selectedShippingId = this.deliveryAddresses[0].id;
                }
                this.onShippingAddressChange();
            }
        },


        addRow() {

            this.rows.push(this.emptyRow());

            this.$nextTick(() => {

                const inputs =
                    document.querySelectorAll('.product-search');

                const lastInput =
                    inputs[inputs.length - 1];

                lastInput?.focus();

            });

        },


        removeRow(index) {

            if (this.rows.length <= 1) {
                return;
            }

            this.rows.splice(index, 1);
            this.discountChanged();
            this.computeBalance();
        },

        openProductDropdown(row, input = null) {

            row.productResults = [
                ...this.products
            ];

            row.productResultsOpen = true;

            this.$nextTick(() => {
                this.positionProductDropdown(row, input);
            });
        },

        positionProductDropdown(row, input = null) {
            if (!input) {
                input = this.$root.querySelector('.product-search:focus');
            }
            if (!input) {
                const idx = this.rows.findIndex(r => r.key === row.key);
                if (idx !== -1) {
                    const inputs = this.$root.querySelectorAll('.product-search');
                    input = inputs[idx] || null;
                }
            }
            if (!input) {
                return;
            }

            const rect = input.getBoundingClientRect();
            row.productDropdownTop = rect.bottom + 4;
            row.productDropdownLeft = rect.left;
            row.productDropdownWidth = Math.max(rect.width, 320);
        },

        toggleProductDropdown(row, event) {

            if (row.productResultsOpen) {

                row.productResultsOpen = false;

                return;
            }

            this.openProductDropdown(
                row,
                event?.currentTarget?.closest('td')
                    ?.querySelector('.product-search')
            );
        },

        closeProductDropdowns(event) {
            const clickedProductInput = event.target.closest('.product-search');
            const clickedProductDropdown = event.target.closest('.product-dropdown');
            const clickedProductArrow = event.target.closest('.product-dropdown-arrow');

            if (
                clickedProductInput ||
                clickedProductDropdown ||
                clickedProductArrow
            ) {
                return;
            }

            this.rows.forEach(row => {
                row.productResultsOpen = false;
            });
        },


        async searchProducts(row, term) {
            term = String(term || '')
                .trim()
                .toLowerCase();

            /*
            * If the user changes the selected product text,
            * clear the previous product selection.
            */
            if (
                row.productId &&
                row.productLabel !== row.selectedProductLabel
            ) {
                row.productId = '';
                row.uomId = '';
                row.uoms = [];
                row.unitPrice = '';
                row.lineTotal = 0;
            }

            if (term === '') {
                row.productResults = [
                    ...this.products
                ];
            } else {
                row.productResults = this.products.filter(product => {
                    const name = String(product.name || '')
                        .toLowerCase();

                    const sku = String(product.sku || '')
                        .toLowerCase();

                    return (
                        name.includes(term) ||
                        sku.includes(term)
                    );
                });
            }

            row.productResultsOpen = true;

            // Debounce remote search for broader product catalog
            clearTimeout(row.searchTimer);
            row.searchTimer = setTimeout(async () => {
                try {
                    const searchUrl = <?php echo json_encode(route('orders.search.products'), 15, 512) ?> + '?q=' + encodeURIComponent(term);
                    const resp = await fetch(searchUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (resp.ok) {
                        const remoteProducts = await resp.json();
                        if (Array.isArray(remoteProducts)) {
                            const map = new Map(this.products.map(p => [String(p.id), p]));
                            remoteProducts.forEach(p => map.set(String(p.id), p));
                            this.products = Array.from(map.values());

                            if (term === '') {
                                row.productResults = [...this.products];
                            } else {
                                row.productResults = this.products.filter(product => {
                                    const name = String(product.name || '').toLowerCase();
                                    const sku = String(product.sku || '').toLowerCase();
                                    return name.includes(term) || sku.includes(term);
                                });
                            }
                        }
                    }
                } catch (e) {
                    console.error('Remote product search error', e);
                }
            }, 250);
        },

        async selectProduct(row, product) {
            row.productId = String(product.id);
            row.taxRate = Number(product.tax_rate || 0);
            row.productLabel = product.label ||
                `${product.name}${product.sku ? ' (' + product.sku + ')' : ''}`;
            row.selectedProductLabel = row.productLabel;
            row.productResultsOpen = false;
            row.productResults = [];
            row.uomId = '';
            row.unitPrice = '';
            row.isManualPrice = false;
            row.lineTotal = 0;
            row.uoms = [];
            await this.loadUoms(row);
        },

        async loadUoms(row, preservedUomId = null) {
            if (!row.productId) {
                row.uoms = [];
                row.uomId = '';
                return;
            }

            try {
                const url =
                    <?php echo json_encode(route('orders.search.uoms'), 15, 512) ?> +
                    '?product_id=' +
                    encodeURIComponent(row.productId);

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                if (!response.ok) {
                    throw new Error('UOM lookup failed.');
                }

                row.uoms = await response.json();

                if (preservedUomId && row.uoms.some(u => String(u.id) === String(preservedUomId))) {
                    row.uomId = String(preservedUomId);
                    if (!row.unitPrice && !row.isManualPrice) {
                        await this.resolvePrice(row);
                    }
                } else if (row.uoms.length === 1) {
                    row.uomId = String(row.uoms[0].id);
                    if (!row.unitPrice && !row.isManualPrice) {
                        await this.resolvePrice(row);
                    }
                } else if (!row.uomId && row.uoms.length > 0) {
                    const defUom = row.uoms.find(u => u.is_base) || row.uoms[0];
                    row.uomId = String(defUom.id);
                    if (!row.unitPrice && !row.isManualPrice) {
                        await this.resolvePrice(row);
                    }
                }

                this.updateTotal(row);
            } catch (error) {
                console.error(error);
                row.uoms = [];
                row.uomId = '';
            }
        },

        priceChanged(row) {
            row.isManualPrice = true;
            this.updateTotal(row);
        },

        quantityChanged(row) {
            this.updateTotal(row);
            if (!row.isManualPrice && row.productId && row.uomId && this.customerId) {
                clearTimeout(row.priceTimer);
                row.priceTimer = setTimeout(() => {
                    this.resolvePrice(row);
                }, 250);
            }
        },

        async resolvePrice(row, force = false) {
            this.updateTotal(row);

            if (
                !this.customerId ||
                !row.productId ||
                !row.uomId ||
                Number(row.quantity) <= 0
            ) {
                return;
            }

            if (row.isManualPrice && !force) {
                return;
            }

            row.priceLoading = true;

            try {
                const params = new URLSearchParams({
                    customer_id: this.customerId,
                    product_id: row.productId,
                    uom_id: row.uomId,
                    quantity: row.quantity,
                });

                const response = await fetch(
                    <?php echo json_encode(route('orders.resolve-price'), 15, 512) ?> +
                    '?' +
                    params.toString(),
                    {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    }
                );

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message ||
                        'Price lookup failed.'
                    );
                }

                if (data.found && (!row.isManualPrice || force)) {
                    row.unitPrice = Number(data.unit_price).toFixed(2);
                    if (force) {
                        row.isManualPrice = false;
                    }
                }

                this.updateTotal(row);
            } catch (error) {
                console.error(error);
            } finally {
                row.priceLoading = false;
            }
        },

        discountChanged() {
            let value = Number(this.discountValue || 0);
            if (!Number.isFinite(value) || value < 0) {
                value = 0;
            }
            const sub = this.subtotal;
            if (this.discountType === 'percent') {
                if (value > 100) {
                    value = 100;
                    this.discountValue = 100;
                }
                this.discountAmount = Math.round((sub * value / 100) * 100) / 100;
            } else {
                if (value > sub && sub > 0) {
                    value = sub;
                    this.discountValue = sub;
                }
                this.discountAmount = Math.round(value * 100) / 100;
            }
            this.computeBalance();
        },

        updateTotal(row) {
            const quantity = Number(row.quantity || 0);
            const unitPrice = Number(row.unitPrice || 0);
            row.lineTotal = Number.isFinite(quantity) && Number.isFinite(unitPrice) && quantity > 0 && unitPrice >= 0
                ? Math.round(quantity * unitPrice * 100) / 100
                : 0;
            this.discountChanged();
        },

        get subtotal() {
            const sum = this.rows.reduce(
                (total, row) => total + Number(row.lineTotal || 0),
                0
            );
            return Math.round(sum * 100) / 100;
        },

        get taxAmount() {
            const sub = this.subtotal;
            const disc = Math.min(Number(this.discountAmount || 0), sub);
            let tax = 0;
            this.rows.forEach(r => {
                const lt = Number(r.lineTotal || 0);
                if (lt > 0) {
                    const lineDisc = sub > 0 ? Math.round(disc * (lt / sub) * 100) / 100 : 0;
                    const taxable = Math.max(0, lt - lineDisc);
                    const rate = Number(r.taxRate || 0);
                    tax += Math.round(taxable * (rate / 100) * 100) / 100;
                }
            });
            return Math.round(tax * 100) / 100;
        },

        get grandTotal() {
            const sub = this.subtotal;
            const disc = Math.min(Number(this.discountAmount || 0), sub);
            const tax = this.taxAmount;
            return Math.max(0, Math.round((sub - disc + tax) * 100) / 100);
        },


        formatMoney(value) {

            return Number(value || 0).toLocaleString(
                'en-IN',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }
            );

        },


        handleShortcut(event) {

            const tag =
                document.activeElement?.tagName;


            const typing =
                ['INPUT', 'TEXTAREA', 'SELECT']
                    .includes(tag);


            if (
                event.ctrlKey &&
                event.key === 'Enter'
            ) {

                event.preventDefault();

                document
                    .querySelector('form')
                    ?.requestSubmit();

                return;

            }


            if (
                event.key.toLowerCase() === 'a' &&
                !typing
            ) {

                event.preventDefault();

                this.addRow();

            }

        },


        orderDateChanged() {
            if (!this.isManualDueDate && this.creditDays !== null) {
                this.recalcDueDate();
            } else if (this.dueDate) {
                this.dueDatePreview = this.formatDatePreview(this.dueDate);
            }
        },

        setCreditDays(days) {
            this.creditDays = Number(days);
            this.isManualDueDate = false;
            this.recalcDueDate();
        },

        recalcDueDate() {
            if (!this.orderDate) {
                return;
            }
            const days = this.creditDays !== null ? this.creditDays : 0;
            this.dueDate = this.addDays(this.orderDate, days);
            this.dueDatePreview = this.formatDatePreview(this.dueDate);
        },

        manualDueDateChanged() {
            if (!this.dueDate) {
                this.dueDatePreview = '';
                this.creditDays = null;
                this.isManualDueDate = false;
                return;
            }
            const diff = this.calcDaysDiff(this.orderDate, this.dueDate);
            const partyDays = Number(this.customerCreditDays[this.customerId] || 0);

            if (diff === 30 || diff === 45 || diff === 50) {
                this.creditDays = diff;
                this.isManualDueDate = false;
            } else if (partyDays > 0 && diff === partyDays) {
                this.creditDays = partyDays;
                this.isManualDueDate = false;
            } else {
                this.creditDays = null;
                this.isManualDueDate = true;
            }
            this.dueDatePreview = this.formatDatePreview(this.dueDate);
        },

        addDays(dateStr, days) {
            if (!dateStr || days === null || days === undefined || isNaN(days)) {
                return '';
            }
            const parts = String(dateStr).split('-');
            if (parts.length !== 3) {
                return '';
            }
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);

            const date = new Date(year, month, day);
            if (isNaN(date.getTime())) {
                return '';
            }
            date.setDate(date.getDate() + Number(days));

            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        },

        calcDaysDiff(startStr, endStr) {
            if (!startStr || !endStr) return null;
            const sParts = String(startStr).split('-');
            const eParts = String(endStr).split('-');
            if (sParts.length !== 3 || eParts.length !== 3) return null;

            const sDate = new Date(parseInt(sParts[0], 10), parseInt(sParts[1], 10) - 1, parseInt(sParts[2], 10));
            const eDate = new Date(parseInt(eParts[0], 10), parseInt(eParts[1], 10) - 1, parseInt(eParts[2], 10));
            if (isNaN(sDate.getTime()) || isNaN(eDate.getTime())) return null;

            const diffTime = eDate.getTime() - sDate.getTime();
            return Math.round(diffTime / (1000 * 60 * 60 * 24));
        },

        formatDatePreview(dateStr) {
            if (!dateStr) {
                return '';
            }
            const parts = String(dateStr).split('-');
            if (parts.length !== 3) {
                return dateStr;
            }
            const year = parts[0];
            const monthIdx = parseInt(parts[1], 10) - 1;
            const day = String(parseInt(parts[2], 10)).padStart(2, '0');
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const monthName = months[monthIdx] || parts[1];
            return `${day} ${monthName} ${year}`;
        },

        computeBalance() {
            const total = Number(this.grandTotal || 0);
            let adv = Number(this.advanceAmount || 0);
            if (adv < 0) {
                adv = 0;
                this.advanceAmount = 0;
            }
            if (total > 0 && adv > total) {
                adv = total;
                this.advanceAmount = total;
            }
            this.balanceAmount = Math.max(0, Math.round((total - adv) * 100) / 100);
        },

        advanceAmountChanged() {
            const total = Number(this.grandTotal || 0);
            let adv = Number(this.advanceAmount || 0);
            if (adv < 0) {
                adv = 0;
                this.advanceAmount = 0;
            }
            if (total > 0 && adv > total) {
                adv = total;
                this.advanceAmount = total;
            }
            if (total > 0 && adv > 0) {
                this.advancePercentage = Math.round((adv / total) * 10000) / 100;
            } else if (adv === 0) {
                this.advancePercentage = '';
            }
            this.computeBalance();
        },

        advancePercentageChanged() {
            const total = Number(this.grandTotal || 0);
            let pct = Number(this.advancePercentage || 0);
            if (pct < 0) {
                pct = 0;
                this.advancePercentage = 0;
            }
            if (pct > 100) {
                pct = 100;
                this.advancePercentage = 100;
            }
            if (total > 0 && pct > 0) {
                this.advanceAmount = Math.round((total * pct / 100) * 100) / 100;
            } else if (pct === 0) {
                this.advanceAmount = 0;
            }
            this.computeBalance();
        },

        saveDraftAndRedirect(event, url) {
            if (event) {
                event.preventDefault();
            }
            const draft = {
                customer_id: this.customerId || '',
                customer_label: this.customerLabel || '',
                order_date: this.orderDate || document.querySelector('input[name="order_date"]')?.value || '',
                due_date: this.dueDate || document.querySelector('input[name="due_date"]')?.value || '',
                credit_days: this.creditDays,
                is_manual_due_date: this.isManualDueDate,
                fulfilment_mode: document.querySelector('select[name="fulfilment_mode"]')?.value || '',
                created_by_name: document.querySelector('input[name="created_by_name"]')?.value || '',
                discount_type: this.discountType,
                discount_value: this.discountValue,
                discount_amount: this.discountAmount,
                payment_terms: this.paymentTerms || '',
                advance_amount: this.advanceAmount || 0,
                advance_percentage: this.advancePercentage || '',
                salesperson_id: this.salespersonId || '',
                reference_no: this.referenceNo || '',
                terms_of_delivery: this.termsOfDelivery || '',
                caretaker_phone: this.caretakerPhone || '',
                architect_name: this.architectName || '',
                architect_phone: this.architectPhone || '',
                billing_address_id: this.selectedBillingId || '',
                shipping_address_id: this.selectedShippingId || '',
                same_as_billing: this.sameAsBilling,
                billing_address: this.billingSnapshot || '',
                shipping_address: this.shippingSnapshot || '',
                notes: document.querySelector('textarea[name="notes"]')?.value || '',
                rows: this.rows.map(r => ({
                    productId: r.productId || '',
                    productLabel: r.productLabel || '',
                    selectedProductLabel: r.selectedProductLabel || '',
                    uomId: r.uomId || '',
                    quantity: r.quantity || 1,
                    unitPrice: r.unitPrice || '',
                    isManualPrice: Boolean(r.isManualPrice),
                    lineTotal: r.lineTotal || 0,
                    taxRate: r.taxRate || 0,
                }))
            };
            try {
                sessionStorage.setItem('dms_book_order_draft', JSON.stringify(draft));
            } catch (e) {
                console.error('Failed to save order draft', e);
            }
            window.location.href = url;
        },

        saveDraftAndRedirectToProduct(event, index) {
            if (event) {
                event.preventDefault();
            }
            const draft = {
                customer_id: this.customerId || '',
                customer_label: this.customerLabel || '',
                order_date: this.orderDate || document.querySelector('input[name="order_date"]')?.value || '',
                due_date: this.dueDate || document.querySelector('input[name="due_date"]')?.value || '',
                credit_days: this.creditDays,
                is_manual_due_date: this.isManualDueDate,
                fulfilment_mode: document.querySelector('select[name="fulfilment_mode"]')?.value || '',
                created_by_name: document.querySelector('input[name="created_by_name"]')?.value || '',
                discount_type: this.discountType,
                discount_value: this.discountValue,
                discount_amount: this.discountAmount,
                payment_terms: this.paymentTerms || '',
                advance_amount: this.advanceAmount || 0,
                advance_percentage: this.advancePercentage || '',
                salesperson_id: this.salespersonId || '',
                reference_no: this.referenceNo || '',
                terms_of_delivery: this.termsOfDelivery || '',
                caretaker_phone: this.caretakerPhone || '',
                architect_name: this.architectName || '',
                architect_phone: this.architectPhone || '',
                billing_address_id: this.selectedBillingId || '',
                shipping_address_id: this.selectedShippingId || '',
                same_as_billing: this.sameAsBilling,
                billing_address: this.billingSnapshot || '',
                shipping_address: this.shippingSnapshot || '',
                notes: document.querySelector('textarea[name="notes"]')?.value || '',
                rows: this.rows.map(r => ({
                    productId: r.productId || '',
                    productLabel: r.productLabel || '',
                    selectedProductLabel: r.selectedProductLabel || '',
                    uomId: r.uomId || '',
                    quantity: r.quantity || 1,
                    unitPrice: r.unitPrice || '',
                    isManualPrice: Boolean(r.isManualPrice),
                    lineTotal: r.lineTotal || 0,
                    taxRate: r.taxRate || 0,
                })),
                target_row_index: index !== undefined && index !== null ? index : 0,
                context: 'add_product'
            };
            try {
                sessionStorage.setItem('dms_book_order_draft', JSON.stringify(draft));
            } catch (e) {
                console.error('Failed to save order draft', e);
            }
            let url = <?php echo json_encode(route('masters.products.create', ['return_to' => 'orders.create']), 512) ?>;
            if (index !== undefined && index !== null) {
                url += (url.includes('?') ? '&' : '?') + 'target_row=' + encodeURIComponent(index);
            }
            window.location.href = url;
        },

        beforeSubmit(event) {
            this.discountChanged();

            if (this.submitting) {
                event.preventDefault();
                return;
            }

            if (!this.customerId) {
                event.preventDefault();
                alert('Please select a customer.');
                const custEl = document.getElementById('customer_search_input') || document.getElementById('customer_id');
                custEl?.focus();
                custEl?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            if (Number(this.advanceAmount || 0) > Number(this.grandTotal || 0) && Number(this.grandTotal || 0) > 0) {
                event.preventDefault();
                alert('Advance amount received cannot exceed the grand total.');
                const advEl = document.getElementById('advance_amount');
                advEl?.focus();
                advEl?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            if (this.rows.length === 0) {
                event.preventDefault();
                alert('Please add at least one product.');
                this.addRow();
                return;
            }

            const emptyIndex = this.rows.findIndex(row =>
                !row.productId || !row.uomId || Number(row.quantity) <= 0
            );

            if (emptyIndex !== -1) {
                event.preventDefault();
                alert('Please complete every product line (select a product, unit, and quantity).');
                const inputs = document.querySelectorAll('.product-search');
                if (inputs[emptyIndex]) {
                    inputs[emptyIndex].focus();
                    inputs[emptyIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            const invalidPriceIndex = this.rows.findIndex(row =>
                row.productId && (row.unitPrice === '' || isNaN(Number(row.unitPrice)) || Number(row.unitPrice) < 0)
            );

            if (invalidPriceIndex !== -1) {
                event.preventDefault();
                alert('Please enter a valid price (0 or greater) for all product lines.');
                return;
            }

            try {
                sessionStorage.removeItem('dms_book_order_draft');
            } catch (e) {}

            this.submitting = true;
        }
    };
}

window.orderBuilder = orderBuilder;

if (window.Alpine) {
    window.Alpine.data('orderBuilder', orderBuilder);
} else {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('orderBuilder', orderBuilder);
    });
}

</script>

<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/orders/create.blade.php ENDPATH**/ ?>