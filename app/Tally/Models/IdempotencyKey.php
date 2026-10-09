<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdempotencyKey extends AccountingModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'key',
        'request_fingerprint',
        'status_code',
        'response_body',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
