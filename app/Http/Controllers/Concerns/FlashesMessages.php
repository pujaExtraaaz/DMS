<?php

namespace App\Http\Controllers\Concerns;

use App\Support\TitleCase;
use Illuminate\Http\RedirectResponse;

trait FlashesMessages
{
    protected function flashSuccess(string $message, ?string $route = null, array $params = []): RedirectResponse
    {
        $redirect = $route ? redirect()->route($route, $params) : back();

        return $redirect->with('status', TitleCase::format($message));
    }

    protected function flashError(string $message, ?string $route = null, array $params = []): RedirectResponse
    {
        $redirect = $route ? redirect()->route($route, $params) : back();

        return $redirect->with('error', TitleCase::format($message));
    }
}
