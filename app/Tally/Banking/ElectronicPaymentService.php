<?php

namespace Tally\Banking;

use Tally\Models\Company;
use Tally\Models\ElectronicPayment;

class ElectronicPaymentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Company $company, array $data, ?ElectronicPayment $payment = null): ElectronicPayment
    {
        $payload = [
            'company_id' => $company->id,
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'voucher_id' => $data['voucher_id'] ?? null,
            'payment_batch_id' => $data['payment_batch_id'] ?? null,
            'channel' => $data['channel'],
            'reference' => $data['reference'] ?? null,
            'utr' => $data['utr'] ?? null,
            'payment_date' => $data['payment_date'],
            'amount' => $data['amount'],
            'beneficiary' => $data['beneficiary'] ?? null,
            'beneficiary_account' => $data['beneficiary_account'] ?? null,
            'beneficiary_ifsc' => isset($data['beneficiary_ifsc'])
                ? strtoupper(trim((string) $data['beneficiary_ifsc']))
                : null,
            'status' => $data['status'] ?? 'initiated',
            'cleared_at' => ($data['status'] ?? '') === 'cleared'
                ? ($data['cleared_at'] ?? now())
                : ($data['cleared_at'] ?? null),
        ];

        if ($payment) {
            $payment->update($payload);

            return $payment->fresh();
        }

        return ElectronicPayment::query()->create($payload);
    }
}
