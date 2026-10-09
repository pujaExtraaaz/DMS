<?php

namespace Tally\Context;

use Tally\Models\Company;
use Tally\Models\FinancialYear;
use App\Models\User;
use Tally\Preferences\PreferenceStore;
use Illuminate\Support\Carbon;

class WorkingCalendar
{
    public function __construct(private PreferenceStore $preferences) {}

    public function date(?Company $company, ?User $user, ?FinancialYear $year): string
    {
        return $this->savedDate($company, $user, $year)
            ?? $this->fallbackDate($year);
    }

    public function savedDate(?Company $company, ?User $user, ?FinancialYear $year): ?string
    {
        if (! $company) {
            return null;
        }

        $saved = (string) $this->preferences->get($company, $user, 'dashboard.as_on');

        if ($saved === '' || strtotime($saved) === false) {
            return null;
        }

        $saved = date('Y-m-d', strtotime($saved));

        if ($year && ! $this->inside($saved, $year)) {
            return null;
        }

        return $saved;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function period(?Company $company, ?User $user, ?FinancialYear $year): array
    {
        $from = $company ? (string) $this->preferences->get($company, $user, 'dashboard.period_from') : '';
        $to = $company ? (string) $this->preferences->get($company, $user, 'dashboard.period_to') : '';

        if ($from !== '' && $to !== '' && $from <= $to && (! $year || ($this->inside($from, $year) && $this->inside($to, $year)))) {
            return [$from, $to];
        }

        if ($year) {
            return [$year->start_date->toDateString(), $year->end_date->toDateString()];
        }

        return [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
    }

    public function saveDate(Company $company, string $date): void
    {
        $this->preferences->put($company, null, 'dashboard.as_on', $date);
    }

    public function savePeriod(Company $company, string $from, string $to): void
    {
        $this->preferences->put($company, null, 'dashboard.period_from', $from);
        $this->preferences->put($company, null, 'dashboard.period_to', $to);
    }

    /**
     * @return array{date: string, from: string, to: string, saved: bool, dateLabel: string, periodLabel: string, min: ?string, max: ?string}
     */
    public function present(?Company $company, ?User $user, ?FinancialYear $year): array
    {
        $date = $this->date($company, $user, $year);
        [$from, $to] = $this->period($company, $user, $year);

        return [
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'saved' => $this->savedDate($company, $user, $year) !== null,
            'dateLabel' => Carbon::parse($date)->format('l, j-M-Y'),
            'periodLabel' => Carbon::parse($from)->format('j-M-y').' to '.Carbon::parse($to)->format('j-M-y'),
            'min' => $year?->start_date->toDateString(),
            'max' => $year?->end_date->toDateString(),
        ];
    }

    private function fallbackDate(?FinancialYear $year): string
    {
        $today = now()->toDateString();

        if ($year && $this->inside($today, $year)) {
            return $today;
        }

        return $year?->start_date->toDateString() ?? $today;
    }

    private function inside(string $date, FinancialYear $year): bool
    {
        return $date >= $year->start_date->toDateString() && $date <= $year->end_date->toDateString();
    }
}
