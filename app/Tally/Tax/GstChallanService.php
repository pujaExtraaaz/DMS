<?php

namespace Tally\Tax;

use Tally\Models\Company;
use Tally\Models\GstChallan;

class GstChallanService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Company $company, array $data, ?GstChallan $challan = null): GstChallan
    {
        $cgst = (float) ($data['cgst'] ?? 0);
        $sgst = (float) ($data['sgst'] ?? 0);
        $igst = (float) ($data['igst'] ?? 0);
        $cess = (float) ($data['cess'] ?? 0);
        $interest = (float) ($data['interest'] ?? 0);
        $penalty = (float) ($data['penalty'] ?? 0);
        $total = array_key_exists('total_amount', $data)
            ? (float) $data['total_amount']
            : $cgst + $sgst + $igst + $cess + $interest + $penalty;

        $payload = [
            'company_id' => $company->id,
            'return_period' => $data['return_period'],
            'challan_number' => $data['challan_number'] ?? null,
            'payment_date' => $data['payment_date'],
            'cgst' => number_format($cgst, 2, '.', ''),
            'sgst' => number_format($sgst, 2, '.', ''),
            'igst' => number_format($igst, 2, '.', ''),
            'cess' => number_format($cess, 2, '.', ''),
            'interest' => number_format($interest, 2, '.', ''),
            'penalty' => number_format($penalty, 2, '.', ''),
            'total_amount' => number_format($total, 2, '.', ''),
            'payment_voucher_id' => $data['payment_voucher_id'] ?? null,
            'status' => $data['status'] ?? 'paid',
        ];

        if ($challan) {
            $challan->update($payload);

            return $challan->fresh();
        }

        return GstChallan::query()->create($payload);
    }
}
