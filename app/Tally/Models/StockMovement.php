<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Integration\Concerns\HasExternalReference;
use Tally\Inventory\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends AccountingModel
{
    use HasExternalReference;
    /**
     * Quantity and value are signed. Stock in, transfer in, and positive
     * adjustments increase the godown. Stock out and transfer out decrease it.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'branch_id',
        'financial_year_id',
        'product_id',
        'godown_id',
        'batch_id',
        'serial_id',
        'quantity',
        'rate',
        'value',
        'movement_type',
        'reference_type',
        'reference_id',
        'movement_date',
        'is_reversal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'rate' => 'decimal:2',
            'value' => 'decimal:2',
            'movement_type' => StockMovementType::class,
            'movement_date' => 'date',
            'is_reversal' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function godown(): BelongsTo
    {
        return $this->belongsTo(Godown::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(StockSerial::class, 'serial_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function referenceLabel(): string
    {
        $reference = $this->reference;

        if ($reference instanceof Invoice) {
            return $reference->invoice_number;
        }

        if ($reference instanceof StockTransaction) {
            return $reference->number;
        }

        if ($reference instanceof ManufacturingOrder) {
            return $reference->number;
        }

        return '—';
    }
}
