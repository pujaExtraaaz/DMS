<?php

namespace Tally\Tax;

use Tally\Models\Company;
use Tally\Models\ItcEntry;

class ItcService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(Company $company, array $data): ItcEntry
    {
        return ItcEntry::query()->create([
            'company_id' => $company->id,
            'invoice_id' => $data['invoice_id'] ?? null,
            'return_period' => $data['return_period'] ?? null,
            'eligibility' => $data['eligibility'] ?? 'eligible',
            'igst' => $data['igst'] ?? 0,
            'cgst' => $data['cgst'] ?? 0,
            'sgst' => $data['sgst'] ?? 0,
            'cess' => $data['cess'] ?? 0,
            'reason' => $data['reason'] ?? null,
        ]);
    }
}
