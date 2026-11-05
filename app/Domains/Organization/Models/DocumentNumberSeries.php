<?php

namespace App\Domains\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DocumentNumberSeries extends Model
{
    public const METHOD_AUTOMATIC = 'automatic';

    public const METHOD_MANUAL = 'manual';

    protected $fillable = [
        'company_id',
        'document_type',
        'name',
        'prefix',
        'suffix',
        'include_financial_year',
        'financial_year_style',
        'separator',
        'padding',
        'numbering_method',
        'restart_each_fy',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'include_financial_year' => 'boolean',
            'restart_each_fy' => 'boolean',
            'is_active' => 'boolean',
            'padding' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isManual(): bool
    {
        return $this->numbering_method === self::METHOD_MANUAL;
    }

    public function isAutomatic(): bool
    {
        return ! $this->isManual();
    }

    public function financialYearCode(?FinancialYear $fy = null, ?Carbon $date = null): string
    {
        $fy ??= $this->resolveFinancialYear($date);
        if (! $fy) {
            $date ??= now();

            return $date->format('y').$date->copy()->addYear()->format('y');
        }

        $start = $fy->starts_on;
        $end = $fy->ends_on;

        return match ($this->financial_year_style) {
            'YYYY-YY' => $start->format('Y').'-'.$end->format('y'),
            'name' => (string) ($fy->name ?: ($start->format('y').$end->format('y'))),
            default => $start->format('y').$end->format('y'),
        };
    }

    public function resolveFinancialYear(?Carbon $date = null): ?FinancialYear
    {
        $date ??= now();
        $query = FinancialYear::query()
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->orderByDesc('is_current');

        if ($this->company_id) {
            $query->where('company_id', $this->company_id);
        }

        return $query->first()
            ?? FinancialYear::query()->where('is_current', true)->when($this->company_id, fn ($q) => $q->where('company_id', $this->company_id))->first();
    }

    public function formatNumber(int $number, ?FinancialYear $fy = null, ?Carbon $date = null): string
    {
        $sep = $this->separator !== null && $this->separator !== '' ? $this->separator : '-';
        $parts = [];
        $prefix = trim((string) $this->prefix);
        if ($prefix !== '') {
            $parts[] = strtoupper($prefix);
        }
        if ($this->include_financial_year) {
            $parts[] = $this->financialYearCode($fy, $date);
        }
        $padding = max(1, min(8, (int) $this->padding));
        $parts[] = sprintf('%0'.$padding.'d', max(0, $number));
        $body = implode($sep, $parts);
        $suffix = trim((string) ($this->suffix ?? ''));

        return $suffix === '' ? $body : $body.$suffix;
    }

    public function preview(?Carbon $date = null): string
    {
        return $this->formatNumber(1, null, $date);
    }

    public static function documentTypes(): array
    {
        return [
            'PO' => 'Purchase Order',
            'PI' => 'Purchase Invoice',
            'GRN' => 'Goods Receipt',
            'SO' => 'Sales Order',
            'INV' => 'Sales Invoice',
        ];
    }
}
