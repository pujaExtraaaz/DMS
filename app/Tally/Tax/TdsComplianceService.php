<?php

namespace Tally\Tax;

use Tally\Models\Company;
use Tally\Models\TdsCertificate;
use Tally\Models\TdsChallan;

class TdsComplianceService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function saveChallan(Company $company, array $data, ?TdsChallan $challan = null): TdsChallan
    {
        $payload = [
            'company_id' => $company->id,
            'return_period' => $data['return_period'],
            'section' => $data['section'] ?? null,
            'challan_number' => $data['challan_number'] ?? null,
            'bsr_code' => $data['bsr_code'] ?? null,
            'deposit_date' => $data['deposit_date'],
            'amount' => $data['amount'] ?? 0,
            'interest' => $data['interest'] ?? 0,
            'status' => $data['status'] ?? 'deposited',
        ];

        if ($challan) {
            $challan->update($payload);

            return $challan->fresh();
        }

        return TdsChallan::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveCertificate(Company $company, array $data, ?TdsCertificate $certificate = null): TdsCertificate
    {
        $payload = [
            'company_id' => $company->id,
            'party_ledger_id' => (int) $data['party_ledger_id'],
            'certificate_number' => $data['certificate_number'],
            'form_type' => $data['form_type'] ?? '16A',
            'financial_year' => $data['financial_year'],
            'quarter' => $data['quarter'] ?? null,
            'tds_amount' => $data['tds_amount'] ?? 0,
            'issue_date' => $data['issue_date'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ];

        if ($certificate) {
            $certificate->update($payload);

            return $certificate->fresh();
        }

        return TdsCertificate::query()->create($payload);
    }
}
