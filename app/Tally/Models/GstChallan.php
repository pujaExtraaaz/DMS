<?php

namespace Tally\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GstChallan extends AccountingModel
{
    protected $fillable = [
        'company_id',
        'return_period',
        'challan_number',
        'payment_date',
        'cgst',
        'sgst',
        'igst',
        'cess',
        'interest',
        'penalty',
        'total_amount',
        'payment_voucher_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'cgst' => 'decimal:2',
            'sgst' => 'decimal:2',
            'igst' => 'decimal:2',
            'cess' => 'decimal:2',
            'interest' => 'decimal:2',
            'penalty' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'payment_voucher_id');
    }
}
