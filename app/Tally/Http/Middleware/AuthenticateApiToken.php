<?php

namespace Tally\Http\Middleware;

use Tally\Audit\AuditLogger;
use Tally\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();
        $token = $plain ? ApiToken::findPlain($plain) : null;

        if (! $token || ! $token->user || $token->user->is_active === false) {
            app(AuditLogger::class)->security('api_unauthenticated', 'API request rejected at '.$request->path().'.');

            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        Auth::setUser($token->user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
