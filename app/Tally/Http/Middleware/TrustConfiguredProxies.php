<?php

namespace Tally\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustConfiguredProxies
{
    public function handle(Request $request, Closure $next): Response
    {
        $proxies = config('operations.trusted_proxies');

        if (is_string($proxies) && $proxies !== '') {
            $request->setTrustedProxies(
                $proxies === '*' ? ['*'] : array_values(array_filter(array_map('trim', explode(',', $proxies)))),
                Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
                    | Request::HEADER_X_FORWARDED_AWS_ELB,
            );
        }

        return $next($request);
    }
}
