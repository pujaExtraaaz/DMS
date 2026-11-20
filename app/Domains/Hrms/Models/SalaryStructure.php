<?php

namespace App\Domains\Hrms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryStructure extends Model
{
    protected $fillable = [
        'employee_id', 'name', 'basic', 'hra', 'allowances', 'deductions',
        'effective_from', 'effective_to', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'basic' => 'decimal:2',
            'hra' => 'decimal:2',
            'allowances' => 'decimal:2',
            'deductions' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalaryStructureLine::class)->orderBy('id');
    }

    public function syncLegacyTotalsFromLines(): void
    {
        $lines = $this->relationLoaded('lines')
            ? $this->lines
            : $this->lines()->with('payHead')->get();

        $basic = 0.0;
        $hra = 0.0;
        $allowances = 0.0;
        $deductions = 0.0;
        $otherEarnings = 0.0;

        foreach ($lines as $line) {
            $amount = (float) $line->amount;
            $code = strtoupper((string) ($line->payHead?->code ?? ''));
            $type = $line->payHead?->type ?? PayHead::TYPE_EARNING;

            if ($type === PayHead::TYPE_DEDUCTION) {
                $deductions += $amount;
                continue;
            }

            match ($code) {
                PayHead::CODE_BASIC => $basic += $amount,
                PayHead::CODE_HRA => $hra += $amount,
                PayHead::CODE_ALLOWANCES => $allowances += $amount,
                default => $otherEarnings += $amount,
            };
        }

        $this->forceFill([
            'basic' => $basic,
            'hra' => $hra,
            'allowances' => $allowances + $otherEarnings,
            'deductions' => $deductions,
        ])->save();
    }

    public function grossEarnings(): float
    {
        return (float) $this->basic + (float) $this->hra + (float) $this->allowances;
    }

    public function netAmount(): float
    {
        return $this->grossEarnings() - (float) $this->deductions;
    }
}
