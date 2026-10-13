<?php

namespace Tally\Controls;

use Illuminate\Support\Facades\DB;
use Tally\Models\CloseChecklist;
use Tally\Models\Company;

class CloseChecklistService
{
    /**
     * @var list<array{key: string, title: string}>
     */
    public const DEFAULT_TASKS = [
        ['key' => 'bank_recon', 'title' => 'Bank reconciliation'],
        ['key' => 'ar_confirm', 'title' => 'AR confirmation'],
        ['key' => 'ap_confirm', 'title' => 'AP confirmation'],
        ['key' => 'inventory', 'title' => 'Inventory count / valuation'],
        ['key' => 'gst', 'title' => 'GST reconciliation'],
        ['key' => 'tds', 'title' => 'TDS reconciliation'],
        ['key' => 'accruals', 'title' => 'Accruals & provisions'],
        ['key' => 'depreciation', 'title' => 'Depreciation run'],
        ['key' => 'fx', 'title' => 'FX revaluation'],
        ['key' => 'period_lock', 'title' => 'Period lock'],
    ];

    /**
     * @param  array{period_label: string, kind?: string, accounting_period_id?: ?int, owner_id?: ?int}  $data
     */
    public function createMonthly(Company $company, array $data): CloseChecklist
    {
        return DB::transaction(function () use ($company, $data) {
            $checklist = CloseChecklist::query()->create([
                'company_id' => $company->id,
                'accounting_period_id' => $data['accounting_period_id'] ?? null,
                'period_label' => $data['period_label'],
                'kind' => $data['kind'] ?? 'month',
                'status' => 'open',
                'owner_id' => $data['owner_id'] ?? null,
            ]);

            foreach (self::DEFAULT_TASKS as $index => $task) {
                $checklist->tasks()->create([
                    'task_key' => $task['key'],
                    'title' => $task['title'],
                    'sort_order' => $index + 1,
                    'status' => 'pending',
                ]);
            }

            return $checklist->load('tasks');
        });
    }
}
