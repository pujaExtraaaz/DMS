<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Integration\Concerns\HasExternalReference;
use Carbon\CarbonInterface;
use Database\Factories\FinancialYearFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialYear extends AccountingModel
{
    /** @use HasFactory<FinancialYearFactory> */
    use HasExternalReference, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'start_date',
        'end_date',
        'is_active',
        'opening_profit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'opening_profit' => 'decimal:2',
        ];
    }

    public function canBeDeleted(): bool
    {
        return ! Voucher::query()->where('financial_year_id', $this->id)->exists()
            && ! Invoice::query()->where('financial_year_id', $this->id)->exists()
            && ! StockTransaction::query()->where('financial_year_id', $this->id)->exists()
            && ! ManufacturingOrder::query()->where('financial_year_id', $this->id)->exists();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function rangeLabel(): string
    {
        return $this->start_date->format('d M Y').' – '.$this->end_date->format('d M Y');
    }

    public static function labelForPeriod(CarbonInterface $start, CarbonInterface $end): string
    {
        if ($start->year === $end->year) {
            return (string) $start->year;
        }

        return $start->format('Y').'-'.$end->format('y');
    }

    /**
     * @param  Builder<FinancialYear>  $query
     * @return Builder<FinancialYear>
     */
    public function scopeOverlapping(Builder $query, int $companyId, string $start, string $end, ?int $ignoreId = null): Builder
    {
        return $query
            ->where('company_id', $companyId)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start);
    }
}
