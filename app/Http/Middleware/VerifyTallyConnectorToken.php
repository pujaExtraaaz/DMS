<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTallyConnectorToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.tally.connector_token');

        if ($expected === '') {
            return response()->json([
                'ok' => false,
                'message' => 'TALLY_CONNECTOR_TOKEN is not configured on the server.',
            ], 503);
        }

        $provided = (string) (
            $request->bearerToken()
            ?: $request->header('X-Tally-Connector-Token')
            ?: $request->query('token')
        );

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
