<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip <?php echo e(sprintf('%02d', $run->month)); ?>/<?php echo e($run->year); ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #666; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #ddd; text-align: left; }
        th { font-size: 10px; text-transform: uppercase; color: #555; }
        .right { text-align: right; }
        .box { margin-top: 16px; padding: 10px 12px; background: #111; color: #fff; }
        .grid { width: 100%; }
        .grid td { border: none; vertical-align: top; width: 50%; }
    </style>
</head>
<body>
    <h1><?php echo e($run->company?->name ?? config('app.name')); ?></h1>
    <div class="muted">Salary slip · <?php echo e(sprintf('%02d', $run->month)); ?>/<?php echo e($run->year); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($run->branch): ?> · <?php echo e($run->branch->name); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></div>

    <table class="grid">
        <tr>
            <td>
                <div class="muted">Employee</div>
                <strong><?php echo e($line->employee?->name); ?></strong><br>
                <?php echo e($line->employee?->employee_code); ?>

            </td>
            <td>
                <div class="muted">Department</div>
                <strong><?php echo e($line->employee?->department?->name ?? '—'); ?></strong><br>
                Payable <?php echo e($line->payable_days); ?> · LWP <?php echo e($line->lwp_days); ?> · OT <?php echo e($line->ot_minutes); ?> min
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr><th>Earnings</th><th class="right">Amount</th><th>Deductions</th><th class="right">Amount</th></tr>
        </thead>
        <tbody>
            <?php
                $earnings = collect($line->earnings_json ?? []);
                $deductions = collect($line->deductions_json ?? []);
                $max = max($earnings->count(), $deductions->count(), 1);
                $eKeys = $earnings->keys()->values();
                $dKeys = $deductions->keys()->values();
            ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php for($i = 0; $i < $max; $i++): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <tr>
                    <td><?php echo e($eKeys[$i] ?? ''); ?></td>
                    <td class="right"><?php echo e(isset($eKeys[$i]) ? number_format((float) $earnings[$eKeys[$i]], 2) : ''); ?></td>
                    <td><?php echo e($dKeys[$i] ?? ''); ?></td>
                    <td class="right"><?php echo e(isset($dKeys[$i]) ? number_format((float) $deductions[$dKeys[$i]], 2) : ''); ?></td>
                </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            <tr>
                <td><strong>Gross</strong></td>
                <td class="right"><strong><?php echo e(number_format((float) $line->gross_earnings, 2)); ?></strong></td>
                <td><strong>Total</strong></td>
                <td class="right"><strong><?php echo e(number_format((float) $line->total_deductions, 2)); ?></strong></td>
            </tr>
        </tbody>
    </table>

    <div class="box">
        <strong>Net payable</strong>
        <span style="float:right;font-size:16px;">Rs. <?php echo e(number_format((float) $line->net_payable, 2)); ?></span>
    </div>
</body>
</html>
<?php /**PATH D:\xampp\htdocs\dms\resources\views/hrms/payroll-runs/payslip-pdf.blade.php ENDPATH**/ ?>