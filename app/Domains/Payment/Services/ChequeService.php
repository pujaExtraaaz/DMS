<?php

namespace App\Domains\Payment\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Models\ChequeBounce;
use App\Support\AuditLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ChequeService
{
    public const BOUNCE_FREEZE_THRESHOLD = 3;

    public function __construct(protected AuditLogService $auditLogService) {}

    public function create(array $data): Cheque
    {
        return Cheque::create([
            'cheque_no' => $data['cheque_no'],
            'customer_id' => $data['customer_id'],
            'purpose' => $data['purpose'] ?? 'pdc',
            'direction' => $data['direction'] ?? 'received_from_client',
            'amount' => $data['amount'],
            'bank_name' => $data['bank_name'] ?? null,
            'branch_name' => $data['branch_name'] ?? null,
            'cheque_date' => $data['cheque_date'] ?? null,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'recorded_by' => auth()->id(),
            'payment_id' => $data['payment_id'] ?? null,
        ]);
    }

    public function deposit(Cheque $cheque, ?string $depositDate = null): Cheque
    {
        if ($cheque->status !== 'pending') {
            throw new InvalidArgumentException('Only pending cheques can be deposited.');
        }

        $cheque->update([
            'status' => 'deposited',
            'deposit_date' => $depositDate ?? now()->toDateString(),
        ]);

        $this->auditLogService->record($cheque, 'deposited');

        return $cheque->fresh();
    }

    public function clear(Cheque $cheque, ?string $clearanceDate = null): Cheque
    {
        if (! in_array($cheque->status, ['pending', 'deposited'], true)) {
            throw new InvalidArgumentException('Cheque cannot be cleared from its current status.');
        }

        $cheque->update([
            'status' => 'cleared',
            'clearance_date' => $clearanceDate ?? now()->toDateString(),
        ]);

        $this->auditLogService->record($cheque, 'cleared');

        return $cheque->fresh();
    }

    public function bounce(Cheque $cheque, ?string $reason = null, float $charges = 0): ChequeBounce
    {
        return DB::transaction(function () use ($cheque, $reason, $charges) {
            $locked = Cheque::query()->whereKey($cheque->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, ['cleared', 'cancelled'], true)) {
                throw new InvalidArgumentException('Cleared or cancelled cheques cannot bounce.');
            }

            $customer = Customer::query()->whereKey($locked->customer_id)->lockForUpdate()->firstOrFail();
            $bounceNumber = (int) $locked->bounce_count + 1;
            $partyBounceCount = (int) $customer->cheque_bounce_count + 1;
            $triggeredFreeze = $partyBounceCount > self::BOUNCE_FREEZE_THRESHOLD;

            $locked->update([
                'status' => 'bounced',
                'bounce_count' => $bounceNumber,
                'bounced_at' => now(),
                'bounce_reason' => $reason,
            ]);

            $customer->update([
                'cheque_bounce_count' => $partyBounceCount,
                'risk_status' => $triggeredFreeze ? 'high' : ($partyBounceCount > 1 ? 'watch' : $customer->risk_status),
                'credit_status' => $triggeredFreeze ? 'frozen' : $customer->credit_status,
            ]);

            $bounce = ChequeBounce::create([
                'cheque_id' => $locked->id,
                'customer_id' => $customer->id,
                'bounced_on' => now()->toDateString(),
                'reason' => $reason,
                'charges' => $charges,
                'bounce_number' => $bounceNumber,
                'triggered_freeze' => $triggeredFreeze,
                'recorded_by' => auth()->id(),
            ]);

            $this->auditLogService->record($locked, 'bounced', null, [
                'bounce_number' => $bounceNumber,
                'party_bounce_count' => $partyBounceCount,
                'triggered_freeze' => $triggeredFreeze,
            ]);

            if ($triggeredFreeze) {
                $this->auditLogService->approval(
                    $customer,
                    'cheque_bounce_freeze',
                    'frozen',
                    $reason ?? 'Cheque bounce threshold exceeded',
                    [
                        'cheque_id' => $locked->id,
                        'bounce_count' => $partyBounceCount,
                        'threshold' => self::BOUNCE_FREEZE_THRESHOLD,
                    ]
                );
            }

            return $bounce;
        });
    }

    public function cancel(Cheque $cheque, ?string $reason = null): Cheque
    {
        if (in_array($cheque->status, ['cleared', 'bounced'], true)) {
            throw new InvalidArgumentException('Cleared or bounced cheques cannot be cancelled.');
        }

        $cheque->update(['status' => 'cancelled', 'notes' => trim(($cheque->notes ? $cheque->notes."\n" : '').($reason ?? ''))]);
        $this->auditLogService->record($cheque, 'cancelled', null, ['reason' => $reason]);

        return $cheque->fresh();
    }
}
