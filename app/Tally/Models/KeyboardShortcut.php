<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Illuminate\Database\Eloquent\Model;

class KeyboardShortcut extends AccountingModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'shortcut_id',
        'keys',
        'is_enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }
}
