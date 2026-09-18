<?php

namespace App\Domains\Organization\Services;

use App\Domains\Organization\Models\FinancialYear;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class FinancialYearService
{
    public static function assertOpen($date, ?int $companyId = null): FinancialYear
    {
        $parsed = Carbon::parse($date)->startOfDay();
        $companyId ??= auth()->user()?->company_id;

        $fy = FinancialYear::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('starts_on', '<=', $parsed->toDateString())
            ->whereDate('ends_on', '>=', $parsed->toDateString())
            ->orderByDesc('is_current')
            ->first();

        if (! $fy) {
            throw ValidationException::withMessages([
                'date' => 'No financial year covers '.$parsed->toDateString().'.',
            ]);
        }

        if ($fy->is_closed) {
            throw ValidationException::withMessages([
                'date' => 'Financial year '.$fy->name.' is closed. Posting is not allowed.',
            ]);
        }

        return $fy;
    }

    public function current(?int $companyId = null): ?FinancialYear
    {
        $companyId ??= auth()->user()?->company_id;

        return FinancialYear::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_current', true)
            ->where('is_closed', false)
            ->first();
    }
}
