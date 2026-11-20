<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyEsslConnectorToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('hrms.essl.enabled')) {
            return response()->json(['message' => 'eSSL connector disabled.'], 503);
        }

        $expected = (string) config('hrms.essl.token');
        if ($expected === '') {
            return response()->json(['message' => 'eSSL connector token not configured.'], 503);
        }

        $provided = (string) ($request->bearerToken() ?: $request->header('X-Essl-Token', ''));
        if (! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
