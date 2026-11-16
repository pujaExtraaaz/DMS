<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo e($title); ?></title>
    <style>
        @page {
            margin: 15mm 10mm;
            size: A4 landscape;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #1e293b;
            line-height: 1.3;
        }
        .header {
            border-bottom: 2px solid #6d28d9;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #4c1d95;
        }
        .report-title {
            font-size: 12pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 10px;
            font-size: 8pt;
            color: #64748b;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        table.data-table th {
            background-color: #6d28d9;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 5px 6px;
            border: 1px solid #5b21b6;
        }
        table.data-table td {
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-star {
            color: #b45309;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 7.5pt;
            color: #94a3b8;
            text-align: right;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="vertical-align: middle;">
                    <div class="company-name"><?php echo e($company?->name ?? 'DMS ERP'); ?></div>
                    <div class="report-title"><?php echo e($title); ?></div>
                </td>
                <td style="text-align: right; vertical-align: middle; font-size: 8pt; color: #64748b;">
                    <div>Generated: <?php echo e($generatedAt); ?></div>
                    <div>Total Records: <?php echo e(number_format($contacts->count())); ?></div>
                </td>
            </tr>
        </table>
    </div>

    
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">#</th>
                <th style="width: 130px;">Contact Name</th>
                <th style="width: 80px;">Type</th>
                <th style="width: 100px;">Designation</th>
                <th style="width: 80px;">Department</th>
                <th style="width: 110px;">Company</th>
                <th style="width: 85px;">Primary Mobile</th>
                <th style="width: 110px;">Email</th>
                <th>Location</th>
                <th style="width: 50px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <tr>
                    <td style="text-align: center;"><?php echo e($idx + 1); ?></td>
                    <td>
                        <strong><?php echo e($c->contact_name); ?></strong>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($c->is_important): ?>
                            <span class="badge-star">★</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td><?php echo e(\App\Domains\PhoneDirectory\Models\PhoneDirectoryContact::CONTACT_TYPES[$c->contact_type] ?? ucfirst($c->contact_type)); ?></td>
                    <td><?php echo e($c->designation ?: '—'); ?></td>
                    <td><?php echo e($c->department?->name ?? '—'); ?></td>
                    <td><?php echo e($c->company_name ?: '—'); ?></td>
                    <td><strong><?php echo e($c->mobile_number); ?></strong></td>
                    <td><?php echo e($c->email ?: '—'); ?></td>
                    <td><?php echo e($c->formatted_location); ?></td>
                    <td style="text-align: center;"><?php echo e(ucfirst($c->status ?? 'active')); ?></td>
                </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 15px; color: #94a3b8;">
                        No contacts found matching the selected criteria.
                    </td>
                </tr>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        DMS Phone Directory &bull; Page printed from ERP &bull; Strictly Confidential
    </div>

</body>
</html>
<?php /**PATH D:\xampp\htdocs\DMS\resources\views/phone_directory/pdf.blade.php ENDPATH**/ ?>