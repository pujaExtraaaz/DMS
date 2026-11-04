<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <title>Orders</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111827;
        }

        h1 {
            font-size: 16px;
            margin-bottom: 4px;
        }

        p {
            margin: 0 0 12px;
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 5px;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
            text-align: left;
        }

        .right {
            text-align: right;
        }
    </style>
</head>

<body>
    <h1>Orders</h1>

    <p>
        Generated:
        <?php echo e(now()->format('d M Y H:i')); ?>

    </p>

    <table>
        <thead>
            <tr>
                <th>Order</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Salesperson</th>
                <th>Status</th>
                <th class="right">Subtotal</th>
                <th class="right">Discount</th>
                <th class="right">Tax</th>
                <th class="right">Total</th>
                <th class="right">Advance</th>
                <th class="right">Balance</th>
            </tr>
        </thead>

        <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <tr>
                    <td><?php echo e($order->order_no); ?></td>
                    <td><?php echo e($order->order_date?->format('d M Y')); ?></td>
                    <td>
                        <?php echo e($order->customer?->name); ?>

                        (<?php echo e($order->customer?->code); ?>)
                    </td>
                    <td><?php echo e($order->salesperson?->name ?? ($order->salesperson_name ?? '—')); ?></td>
                    <td><?php echo e(ucfirst($order->status)); ?></td>
                    <td class="right">
                        ₹<?php echo e(number_format($order->subtotal, 2)); ?>

                    </td>
                    <td class="right">
                        ₹<?php echo e(number_format($order->discount_amount, 2)); ?>

                    </td>
                    <td class="right">
                        ₹<?php echo e(number_format($order->tax_amount, 2)); ?>

                    </td>
                    <td class="right">
                        ₹<?php echo e(number_format($order->grand_total, 2)); ?>

                    </td>
                    <td class="right">
                        ₹<?php echo e(number_format($order->advance_amount ?? 0, 2)); ?>

                    </td>
                    <td class="right">
                        ₹<?php echo e(number_format($order->balance_amount ?? max(0, $order->grand_total - ($order->advance_amount ?? 0)), 2)); ?>

                    </td>
                </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </tbody>
    </table>
</body>
</html><?php /**PATH D:\xampp\htdocs\DMS\resources\views/orders/pdf.blade.php ENDPATH**/ ?>