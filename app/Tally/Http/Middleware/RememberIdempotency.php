<?php

namespace Tally\Http\Middleware;

use Tally\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RememberIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->header('Idempotency-Key', ''));

        if ($key === '' || ! in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $next($request);
        }

        if (strlen($key) > 80) {
            return response()->json(['message' => 'The idempotency key must be 80 characters or fewer.'], 422);
        }

        $fingerprint = hash('sha256', $request->method().'|'.$request->path().'|'.$request->getContent());
        $existing = IdempotencyKey::query()
            ->where('user_id', $request->user()->id)
            ->where('key', $key)
            ->first();

        if ($existing) {
            if ($existing->request_fingerprint !== $fingerprint) {
                return response()->json([
                    'message' => 'This idempotency key was already used for a different request.',
                ], 409);
            }

            return response($existing->response_body, $existing->status_code)
                ->header('Content-Type', 'application/json')
                ->header('Idempotent-Replayed', 'true');
        }

        $response = $next($request);

        if ($response->isSuccessful()) {
            IdempotencyKey::query()->create([
                'user_id' => $request->user()->id,
                'key' => $key,
                'request_fingerprint' => $fingerprint,
                'status_code' => $response->getStatusCode(),
                'response_body' => $response->getContent(),
            ]);
        }

        return $response;
    }
}
