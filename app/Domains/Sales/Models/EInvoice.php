<?php

namespace App\Domains\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EInvoice extends Model
{
    protected $fillable = [
        'invoice_id',
        'status',
        'provider',
        'irn',
        'ack_no',
        'ack_date',
        'signed_invoice',
        'signed_qr_base64',
        'qr_image_path',
        'last_error',
        'requested_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'ack_date' => 'datetime',
            'requested_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isReal(): bool
    {
        return in_array($this->status, ['generated', 'active'], true) && ! str_starts_with((string) $this->irn, 'STUB');
    }
}
