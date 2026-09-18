<?php

namespace App\Support;

use App\Domains\Organization\Models\FinancialYear;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FinancialYearService
{
    public static function assertOpen($date, ?int $companyId = null): FinancialYear
    {
        $date = Carbon::parse($date);
        $companyId ??= auth()->user()?->company_id;

        $fy = FinancialYear::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->orderByDesc('is_current')
            ->first();

        if (! $fy) {
            throw ValidationException::withMessages([
                'date' => 'No financial year covers '.$date->toDateString().'.',
            ]);
        }

        if ($fy->is_closed) {
            throw ValidationException::withMessages([
                'date' => 'Financial year '.$fy->name.' is closed. Posting is blocked.',
            ]);
        }

        return $fy;
    }
}
