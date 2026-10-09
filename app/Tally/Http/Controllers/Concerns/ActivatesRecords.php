<?php

namespace Tally\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait ActivatesRecords
{
    protected function setActive(Request $request, Model $record, string $label): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $record->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', $record->is_active ? $label.' activated.' : $label.' deactivated.');
    }
}
