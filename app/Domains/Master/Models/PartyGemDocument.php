<?php

namespace App\Domains\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PartyGemDocument extends Model
{
    protected $fillable = [
        'party_gem_authorization_id',
        'disk',
        'path',
        'original_name',
        'mime',
    ];

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(PartyGemAuthorization::class, 'party_gem_authorization_id');
    }

    public function url(): string
    {
        return Storage::disk($this->disk ?: 'public')->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}
