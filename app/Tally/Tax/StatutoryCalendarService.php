<?php

namespace Tally\Tax;

use Carbon\Carbon;
use Tally\Models\Company;
use Tally\Models\StatutoryDueDate;

class StatutoryCalendarService
{
    /**
     * Seed common monthly GST/TDS due dates for a YYYY-MM period.
     *
     * @return list<StatutoryDueDate>
     */
    public function seedMonth(Company $company, string $period): array
    {
        $period = preg_match('/^\d{4}-\d{2}$/', $period) ? $period : now()->format('Y-m');
        $month = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $next = $month->copy()->addMonth();
        $label = $month->format('M Y');

        $templates = [
            ['obligation' => 'GSTR-1', 'category' => 'gst', 'day' => 11],
            ['obligation' => 'GSTR-3B', 'category' => 'gst', 'day' => 20],
            ['obligation' => 'GSTR-2B available', 'category' => 'gst', 'day' => 14],
            ['obligation' => 'TDS deposit (u/s 194C/194J etc.)', 'category' => 'tds', 'day' => 7],
            ['obligation' => 'TDS return (24Q/26Q) quarterly reminder', 'category' => 'tds', 'day' => 31],
        ];

        $created = [];

        foreach ($templates as $template) {
            $due = $next->copy()->day(min($template['day'], $next->daysInMonth));

            $exists = StatutoryDueDate::query()
                ->where('company_id', $company->id)
                ->where('obligation', $template['obligation'])
                ->where('period_label', $label)
                ->exists();

            if ($exists) {
                continue;
            }

            $created[] = StatutoryDueDate::query()->create([
                'company_id' => $company->id,
                'obligation' => $template['obligation'],
                'category' => $template['category'],
                'due_date' => $due->toDateString(),
                'period_label' => $label,
                'status' => 'pending',
                'alert_sent' => false,
            ]);
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Company $company, array $data): StatutoryDueDate
    {
        return StatutoryDueDate::query()->create([
            'company_id' => $company->id,
            'obligation' => $data['obligation'],
            'category' => $data['category'] ?? 'gst',
            'due_date' => $data['due_date'],
            'period_label' => $data['period_label'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'alert_sent' => (bool) ($data['alert_sent'] ?? false),
        ]);
    }
}
