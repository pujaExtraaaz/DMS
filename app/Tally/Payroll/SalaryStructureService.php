<?php

namespace Tally\Payroll;

use Illuminate\Support\Facades\DB;
use Tally\Models\Company;
use Tally\Models\SalaryStructure;

class SalaryStructureService
{
    /**
     * @param  array{name: string, is_active?: bool, lines?: list<array{pay_head_id: int, amount?: mixed, percent_of_basic?: mixed, is_earning?: bool}>}  $data
     */
    public function save(Company $company, array $data, ?SalaryStructure $structure = null): SalaryStructure
    {
        return DB::transaction(function () use ($company, $data, $structure) {
            $structure ??= new SalaryStructure(['company_id' => $company->id]);
            $structure->fill([
                'company_id' => $company->id,
                'name' => $data['name'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ])->save();

            if (array_key_exists('lines', $data)) {
                $structure->lines()->delete();
                foreach ($data['lines'] ?? [] as $line) {
                    $structure->lines()->create([
                        'pay_head_id' => (int) $line['pay_head_id'],
                        'amount' => (string) ($line['amount'] ?? 0),
                        'percent_of_basic' => $line['percent_of_basic'] ?? null,
                        'is_earning' => (bool) ($line['is_earning'] ?? true),
                    ]);
                }
            }

            return $structure->load('lines.payHead');
        });
    }
}
