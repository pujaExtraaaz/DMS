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
        'cancel_reason',
        'cancel_remarks',
        'cancelled_at',
        'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'ack_date' => 'datetime',
            'requested_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function getSignedQrCodeAttribute(): ?string
    {
        return $this->signed_qr_base64;
    }

    /**
     * Get scannable data URI for the government-signed QR code or IRN verification payload.
     */
    public function getQrDataUri(int $size = 180): ?string
    {
        if (in_array($this->status, ['cancelled', 'failed'], true)) {
            return null;
        }

        $raw = trim((string) $this->signed_qr_base64);

        if (blank($raw) && filled($this->irn)) {
            $payload = 'IRN:' . $this->irn . ($this->ack_no ? '|ACK:' . $this->ack_no : '') . ($this->ack_date ? '|DATE:' . $this->ack_date->format('d/m/Y') : '');
            $rendered = \App\Support\QrCodeRenderer::dataUri($payload, $size);
            return filled($rendered) ? $rendered : null;
        }

        if (blank($raw)) {
            return null;
        }

        if (str_starts_with($raw, 'data:image/')) {
            return $raw;
        }

        // Check if raw is a base64 encoded PNG
        $decoded = @base64_decode($raw, true);
        if ($decoded !== false && str_starts_with($decoded, "\x89PNG\r\n\x1a\n")) {
            return 'data:image/png;base64,' . $raw;
        }

        // Standard government signed JWT or plain QR code string
        $rendered = \App\Support\QrCodeRenderer::dataUri($raw, $size);
        if (filled($rendered)) {
            return $rendered;
        }

        if ($decoded !== false && filled($decoded) && mb_check_encoding($decoded, 'UTF-8')) {
            $rendered = \App\Support\QrCodeRenderer::dataUri($decoded, $size);
            if (filled($rendered)) {
                return $rendered;
            }
        }

        return null;
    }
}
