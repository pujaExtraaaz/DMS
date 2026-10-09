<?php

namespace Tally\Http\Middleware;

use Tally\Authorization\RoutePermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $permission = RoutePermissions::for($request->route()?->getName(), $request->method());

        if ($permission !== null && ! $request->user()?->can($permission)) {
            abort(403, 'You do not have permission for this action.');
        }

        return $next($request);
    }
}
