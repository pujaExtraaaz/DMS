<?php

namespace Tally\Http\Middleware;

use Tally\Audit\AuditLogger;
use Tally\Models\ApiRequestLog;
use Tally\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('api_started_at', microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! Schema::hasTable('acct_api_request_logs')) {
            return;
        }

        $started = $request->attributes->get('api_started_at');
        $token = $request->attributes->get('api_token');

        ApiRequestLog::query()->create([
            'user_id' => $request->user()?->id,
            'api_token_id' => $token instanceof ApiToken ? $token->id : null,
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'status' => $response->getStatusCode(),
            'ip' => $request->ip(),
            'duration_ms' => $started ? (int) round((microtime(true) - $started) * 1000) : 0,
        ]);

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) && $response->getStatusCode() < 500) {
            app(AuditLogger::class)->record(
                'api_'.$request->method(),
                'api',
                null,
                $request->method().' /'.$request->path().' returned '.$response->getStatusCode().'.',
            );
        }
    }
}
