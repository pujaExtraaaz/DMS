<?php

namespace App\Domains\Sales\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Support\AuditLogService;
use Illuminate\Validation\ValidationException;

class CreditCheckService
{
    public function __construct(
        protected OutstandingLedgerService $outstandingLedgerService,
        protected AuditLogService $auditLogService,
    ) {}

    /**
     * exposure = outstanding + pending approved SO − advances
     *
     * @return array{
     *     outstanding: float,
     *     pending_orders: float,
     *     advances: float,
     *     exposure: float,
     *     credit_limit: float,
     *     available_credit: float,
     *     frozen: bool,
     *     blocked: bool,
     *     reasons: array<int, string>
     * }
     */
    public function evaluate(Customer $customer, float $additionalAmount = 0, ?int $excludeOrderId = null): array
    {
        $balance = $this->outstandingLedgerService->getCurrentBalance($customer->id);
        $outstanding = max(0, $balance);
        $advances = max(0, -$balance);

        $pendingOrders = (float) Order::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'approved')
            ->when($excludeOrderId, fn ($q) => $q->where('id', '!=', $excludeOrderId))
            ->sum('grand_total');

        $exposure = round($outstanding + $pendingOrders + $additionalAmount - $advances, 2);
        $creditLimit = (float) ($customer->credit_limit ?? 0);
        $available = $creditLimit > 0 ? round($creditLimit - $exposure, 2) : 0.0;

        $reasons = [];
        $frozen = $customer->isFrozen();

        if ($frozen) {
            $reasons[] = 'Party credit status is frozen.';
        }

        if ($creditLimit > 0 && $exposure > $creditLimit) {
            $reasons[] = sprintf(
                'Credit exposure ₹%s exceeds limit ₹%s.',
                number_format($exposure, 2),
                number_format($creditLimit, 2)
            );
        }

        return [
            'outstanding' => $outstanding,
            'pending_orders' => $pendingOrders,
            'advances' => $advances,
            'exposure' => $exposure,
            'credit_limit' => $creditLimit,
            'available_credit' => $available,
            'frozen' => $frozen,
            'blocked' => $frozen || ($creditLimit > 0 && $exposure > $creditLimit),
            'reasons' => $reasons,
        ];
    }

    /**
     * @return array{status: string, result: array<string, mixed>}
     */
    public function assertForOrder(Order $order, ?string $overrideReason = null): array
    {
        $customer = $order->customer()->firstOrFail();
        $result = $this->evaluate(
            $customer,
            (float) $order->grand_total,
            $order->id
        );

        if (! $result['blocked']) {
            return ['status' => 'passed', 'result' => $result];
        }

        $reasonText = implode(' ', $result['reasons']);

        if (! $overrideReason || trim($overrideReason) === '') {
            throw ValidationException::withMessages([
                'credit_check' => $reasonText.' Provide an override reason to continue.',
            ]);
        }

        $this->auditLogService->approval(
            $order,
            'credit_override',
            'approved',
            $overrideReason,
            [
                'exposure' => $result['exposure'],
                'credit_limit' => $result['credit_limit'],
                'reasons' => $result['reasons'],
            ]
        );

        $this->auditLogService->record(
            $order,
            'credit_override',
            null,
            ['reason' => $overrideReason, 'result' => $result]
        );

        return ['status' => 'overridden', 'result' => $result];
    }
}
