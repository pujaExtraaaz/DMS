<?php

namespace App\Domains\Hrms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    protected $fillable = [
        'holiday_calendar_id', 'holiday_date', 'name', 'category', 'kind', 'notes',
    ];

    protected function casts(): array
    {
        return ['holiday_date' => 'date'];
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(HolidayCalendar::class, 'holiday_calendar_id');
    }

    public function isCompulsory(): bool
    {
        return $this->kind === 'compulsory';
    }
}
