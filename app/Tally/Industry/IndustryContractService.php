<?php

namespace Tally\Industry;

use Tally\Models\Company;
use Tally\Models\IndustryContract;

class IndustryContractService
{
    /**
     * @param  array{
     *   industry: string,
     *   contract_number: string,
     *   name: string,
     *   party_ledger_id?: ?int,
     *   start_date?: ?string,
     *   end_date?: ?string,
     *   contract_value?: mixed,
     *   retention_percent?: mixed,
     *   recognized_revenue?: mixed,
     *   billing_frequency?: ?string,
     *   status?: string,
     *   meta_json?: ?array
     * }  $data
     */
    public function save(Company $company, array $data, ?IndustryContract $contract = null): IndustryContract
    {
        $contract ??= new IndustryContract(['company_id' => $company->id]);
        $contract->fill([
            'company_id' => $company->id,
            'industry' => $data['industry'],
            'contract_number' => $data['contract_number'],
            'name' => $data['name'],
            'party_ledger_id' => $data['party_ledger_id'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'contract_value' => (string) ($data['contract_value'] ?? 0),
            'retention_percent' => $data['retention_percent'] ?? 0,
            'recognized_revenue' => (string) ($data['recognized_revenue'] ?? 0),
            'billing_frequency' => $data['billing_frequency'] ?? null,
            'status' => $data['status'] ?? 'active',
            'meta_json' => $data['meta_json'] ?? null,
        ])->save();

        return $contract;
    }
}
