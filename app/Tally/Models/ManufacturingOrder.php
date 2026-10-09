<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Accounting\VoucherStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ManufacturingOrder extends AccountingModel
{
    protected $fillable = [
        'company_id', 'branch_id', 'financial_year_id', 'bill_of_material_id', 'number',
        'manufactured_on', 'quantity', 'source_godown_id', 'destination_godown_id',
        'material_cost', 'voucher_id', 'narration', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'manufactured_on' => 'date',
            'quantity' => 'decimal:4',
            'material_cost' => 'decimal:2',
            'status' => VoucherStatus::class,
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bill_of_material_id');
    }

    public function sourceGodown(): BelongsTo
    {
        return $this->belongsTo(Godown::class, 'source_godown_id');
    }

    public function destinationGodown(): BelongsTo
    {
        return $this->belongsTo(Godown::class, 'destination_godown_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }
}
