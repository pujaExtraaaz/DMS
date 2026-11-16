<?php $__env->startSection('title', $isEdit ? 'Edit Contact: ' . $contact->contact_name : 'Add New Contact - Phone Directory'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-5xl mx-auto space-y-6" x-data="contactFormHandler()">

    
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <a href="<?php echo e(route('phone-directory.index')); ?>" class="hover:text-violet-600 transition">Phone Directory</a>
            <span>/</span>
            <span class="font-medium text-slate-900"><?php echo e($isEdit ? 'Edit Contact' : 'New Contact'); ?></span>
        </div>

        <a href="<?php echo e(route('phone-directory.index')); ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            <span>Back to Directory</span>
        </a>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800 shadow-xs">
            <div class="flex items-center gap-2 font-bold mb-2">
                <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span>Please correct the errors below before saving:</span>
            </div>
            <ul class="list-disc list-inside space-y-1">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <li><?php echo e($error); ?></li>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </ul>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <form
        method="POST"
        action="<?php echo e($isEdit ? route('phone-directory.contacts.update', $contact) : route('phone-directory.contacts.store')); ?>"
        enctype="multipart/form-data"
        class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden"
    >
        <?php echo csrf_field(); ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isEdit): ?>
            <?php echo method_field('PUT'); ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <div class="border-b border-slate-200 bg-slate-50/70 px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900"><?php echo e($isEdit ? 'Edit Contact Details' : 'Create New Directory Contact'); ?></h2>
                <p class="text-xs text-slate-500">Fill in the contact information across the categorized sections below.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[11px] text-slate-400"><span class="text-rose-500 font-bold">*</span> Required fields</span>
            </div>
        </div>

        <div class="p-6 space-y-8">

            
            <section class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-violet-100 text-violet-700 text-xs font-bold">A</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Basic Details</h3>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    
                    <div class="md:col-span-3 flex flex-col sm:flex-row items-center gap-5 p-4 rounded-xl border border-slate-200/70 bg-slate-50/40">
                        <div class="relative shrink-0">
                            <template x-if="photoPreview">
                                <img :src="photoPreview" class="h-20 w-20 rounded-full object-cover border-2 border-violet-400 shadow-md">
                            </template>
                            <template x-if="!photoPreview">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($contact->profile_photo_url): ?>
                                    <img src="<?php echo e($contact->profile_photo_url); ?>" class="h-20 w-20 rounded-full object-cover border-2 border-slate-200 shadow-md">
                                <?php else: ?>
                                    <div class="h-20 w-20 rounded-full flex items-center justify-center bg-violet-100 text-violet-700 border-2 border-dashed border-violet-300 text-sm font-bold">
                                        Photo
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </template>
                        </div>

                        <div class="flex-1 space-y-1.5 text-center sm:text-left">
                            <label class="block text-xs font-semibold text-slate-700">Profile Photo</label>
                            <p class="text-[11px] text-slate-500">Upload an image file (JPG, PNG, WebP up to 3MB). Square format recommended.</p>
                            <div class="flex items-center gap-2 pt-1 justify-center sm:justify-start">
                                <input
                                    type="file"
                                    name="profile_photo"
                                    id="profilePhotoInput"
                                    accept="image/*"
                                    @change="previewPhoto($event)"
                                    class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 cursor-pointer"
                                >
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isEdit && $contact->profile_photo): ?>
                                    <label class="inline-flex items-center gap-1 text-[11px] text-rose-600 hover:text-rose-800 cursor-pointer">
                                        <input type="checkbox" name="remove_photo" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                        <span>Remove current photo</span>
                                    </label>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Contact Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="contact_name"
                            value="<?php echo e(old('contact_name', $contact->contact_name)); ?>"
                            required
                            placeholder="e.g. Rajesh Kumar Sharma"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Contact Type <span class="text-rose-500">*</span>
                        </label>
                        <select
                            name="contact_type"
                            required
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-700 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                            <option value="">Select Contact Type</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $contactTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($key); ?>" <?php if(old('contact_type', $contact->contact_type ?? 'other') === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Designation / Job Title
                        </label>
                        <input
                            type="text"
                            name="designation"
                            value="<?php echo e(old('designation', $contact->designation)); ?>"
                            placeholder="e.g. Procurement Head / Sales Manager"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Department
                        </label>
                        <select
                            name="department_id"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-700 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                            <option value="">Select Department (Optional)</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($dept->id); ?>" <?php if((string) old('department_id', $contact->department_id) === (string) $dept->id): echo 'selected'; endif; ?>><?php echo e($dept->name); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Company / Organization
                        </label>
                        <input
                            type="text"
                            name="company_name"
                            value="<?php echo e(old('company_name', $contact->company_name)); ?>"
                            placeholder="e.g. Acme Corporation Pvt Ltd"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Contact Category
                        </label>
                        <select
                            name="category_id"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-700 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                            <option value="">Select Category (Optional)</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($cat->id); ?>" <?php if((string) old('category_id', $contact->category_id) === (string) $cat->id): echo 'selected'; endif; ?>><?php echo e($cat->name); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                </div>
            </section>

            
            <section class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 text-xs font-bold">B</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Contact Information</h3>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Primary Mobile Number <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                type="tel"
                                name="mobile_number"
                                value="<?php echo e(old('mobile_number', $contact->mobile_number)); ?>"
                                required
                                placeholder="e.g. 9876543210"
                                class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                            >
                        </div>
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Alternate Mobile Number
                        </label>
                        <input
                            type="tel"
                            name="alternate_mobile"
                            value="<?php echo e(old('alternate_mobile', $contact->alternate_mobile)); ?>"
                            placeholder="e.g. 9876543211"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            WhatsApp Number
                        </label>
                        <input
                            type="tel"
                            name="whatsapp_number"
                            value="<?php echo e(old('whatsapp_number', $contact->whatsapp_number)); ?>"
                            placeholder="Leave blank if same as mobile"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Office Phone / Landline
                        </label>
                        <input
                            type="text"
                            name="office_phone"
                            value="<?php echo e(old('office_phone', $contact->office_phone)); ?>"
                            placeholder="e.g. 022-28765432"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Extension Number
                        </label>
                        <input
                            type="text"
                            name="extension_number"
                            value="<?php echo e(old('extension_number', $contact->extension_number)); ?>"
                            placeholder="e.g. 104"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Email Address
                        </label>
                        <input
                            type="email"
                            name="email"
                            value="<?php echo e(old('email', $contact->email)); ?>"
                            placeholder="e.g. contact@example.com"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                    </div>

                    
                    <div class="md:col-span-3">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Alternate / Secondary Email
                        </label>
                        <input
                            type="email"
                            name="alternate_email"
                            value="<?php echo e(old('alternate_email', $contact->alternate_email)); ?>"
                            placeholder="e.g. accounts@example.com"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                    </div>
                </div>
            </section>

            
            <section class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 text-xs font-bold">C</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Address Details</h3>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    
                    <div class="md:col-span-3">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Street Address / Building / Plot
                        </label>
                        <textarea
                            name="address"
                            rows="2"
                            placeholder="e.g. Suite 402, Signature Tower, MG Road"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        ><?php echo e(old('address', $contact->address)); ?></textarea>
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Area / Locality
                        </label>
                        <input
                            type="text"
                            name="area"
                            value="<?php echo e(old('area', $contact->area)); ?>"
                            placeholder="e.g. Andheri East"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            City
                        </label>
                        <input
                            type="text"
                            name="city"
                            value="<?php echo e(old('city', $contact->city)); ?>"
                            placeholder="e.g. Mumbai"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            State
                        </label>
                        <select
                            name="state"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-700 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                            <option value="">Select State</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $states; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($st); ?>" <?php if(old('state', $contact->state) === $st): echo 'selected'; endif; ?>><?php echo e($st); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            PIN Code
                        </label>
                        <input
                            type="text"
                            name="pincode"
                            value="<?php echo e(old('pincode', $contact->pincode)); ?>"
                            placeholder="e.g. 400093"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Country
                        </label>
                        <input
                            type="text"
                            name="country"
                            value="<?php echo e(old('country', $contact->country ?? 'India')); ?>"
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        >
                    </div>
                </div>
            </section>

            
            <section class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-100 text-amber-700 text-xs font-bold">D</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Additional Details & Flags</h3>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    
                    <div class="space-y-3 p-4 rounded-xl border border-slate-200/70 bg-slate-50/40">
                        
                        <label class="flex items-center justify-between cursor-pointer">
                            <div>
                                <span class="text-xs font-semibold text-slate-800">Important Contact</span>
                                <p class="text-[11px] text-slate-500">Mark with a star for priority filtering and quick access.</p>
                            </div>
                            <input
                                type="checkbox"
                                name="is_important"
                                value="1"
                                <?php if(old('is_important', $contact->is_important)): echo 'checked'; endif; ?>
                                class="h-5 w-5 rounded border-slate-300 text-amber-500 focus:ring-amber-400 cursor-pointer"
                            >
                        </label>

                        <div class="border-t border-slate-200/60 pt-3">
                            
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Status</label>
                            <select
                                name="status"
                                class="w-full rounded-xl border-slate-300 text-xs text-slate-700 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                            >
                                <option value="active" <?php if(old('status', $contact->status ?? 'active') === 'active'): echo 'selected'; endif; ?>>Active</option>
                                <option value="inactive" <?php if(old('status', $contact->status) === 'inactive'): echo 'selected'; endif; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Notes / Remarks
                        </label>
                        <textarea
                            name="notes"
                            rows="4"
                            placeholder="Add any internal remarks, relationship context, or preferred contact times..."
                            class="w-full rounded-xl border-slate-300 text-xs text-slate-800 focus:border-violet-500 focus:ring-violet-500 shadow-xs"
                        ><?php echo e(old('notes', $contact->notes)); ?></textarea>
                    </div>
                </div>
            </section>
        </div>

        
        <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex items-center justify-end gap-3">
            <a
                href="<?php echo e(route('phone-directory.index')); ?>"
                class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-5 py-2 text-xs font-semibold text-white shadow-md shadow-violet-600/30 hover:bg-violet-700 transition"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                <span><?php echo e($isEdit ? 'Update Contact' : 'Save Contact'); ?></span>
            </button>
        </div>
    </form>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function contactFormHandler() {
    return {
        photoPreview: null,
        previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.photoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    };
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/phone_directory/form.blade.php ENDPATH**/ ?>