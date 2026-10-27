<?php

namespace App\Domains\Organization\Models;

use App\Models\User;
use App\Support\CodeGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterCompanyTransaction extends Model
{
    protected $table = 'inter_company_transactions';

    protected $fillable = [
        'transaction_no',
        'transaction_date',
        'transaction_type',
        'from_company_id',
        'to_company_id',
        'amount',
        'currency',
        'exchange_rate',
        'is_gst_applicable',
        'igst_amount',
        'is_tds_applicable',
        'tds_section',
        'tds_rate',
        'tds_amount',
        'net_amount',
        'reference_invoice',
        'reference_po',
        'due_date',
        'status',
        'narration',
        'approved_by',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'is_gst_applicable' => 'boolean',
            'igst_amount' => 'decimal:2',
            'is_tds_applicable' => 'boolean',
            'tds_rate' => 'decimal:2',
            'tds_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (InterCompanyTransaction $model) {
            if (empty($model->transaction_no)) {
                $model->transaction_no = CodeGenerator::forInterCompanyTransaction();
            }
            if (auth()->check()) {
                $model->created_by = $model->created_by ?? auth()->id();
            }
            $model->calculateNetAmount();
        });

        static::updating(function (InterCompanyTransaction $model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
            $model->calculateNetAmount();
        });
    }

    /**
     * Compute Net Amount based on amount, exchange rate, IGST, and TDS.
     */
    public function calculateNetAmount(): float
    {
        $amount = (float) $this->amount;
        $exchangeRate = (float) ($this->exchange_rate ?: 1.0);
        $baseAmount = round($amount * $exchangeRate, 2);

        $gst = $this->is_gst_applicable ? (float) ($this->igst_amount ?: 0.0) : 0.0;
        
        $tds = 0.0;
        if ($this->is_tds_applicable) {
            if ((float) $this->tds_amount > 0) {
                $tds = (float) $this->tds_amount;
            } elseif ((float) $this->tds_rate > 0) {
                $tds = round(($baseAmount * (float) $this->tds_rate) / 100, 2);
                $this->tds_amount = $tds;
            }
        } else {
            $this->tds_amount = 0.0;
            $this->tds_rate = 0.0;
            $this->tds_section = null;
        }

        if (! $this->is_gst_applicable) {
            $this->igst_amount = 0.0;
        }

        $net = round($baseAmount + $gst - $tds, 2);
        $this->net_amount = $net;

        return $net;
    }

    public function fromCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'from_company_id');
    }

    public function toCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'to_company_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

