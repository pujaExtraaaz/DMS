<?php

namespace Tally\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');

        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, private, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
