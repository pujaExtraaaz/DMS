<?php

namespace Tally\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->can($permission)) {
            abort($request->is('api/*') ? 403 : 403, 'You do not have permission for this action.');
        }

        return $next($request);
    }
}
