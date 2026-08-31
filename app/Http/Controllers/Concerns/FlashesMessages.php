<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;

trait FlashesMessages
{
    protected function flashSuccess(string $message, ?string $route = null, array $params = []): RedirectResponse
    {
        $redirect = $route ? redirect()->route($route, $params) : back();

        return $redirect->with('status', $message);
    }

    protected function flashError(string $message, ?string $route = null, array $params = []): RedirectResponse
    {
        $redirect = $route ? redirect()->route($route, $params) : back();

        return $redirect->with('error', $message);
    }
}
