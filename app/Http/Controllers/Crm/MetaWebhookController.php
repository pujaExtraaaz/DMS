<?php

namespace App\Http\Controllers\Crm;

use App\Domains\Crm\Services\MetaLeadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class MetaWebhookController extends Controller
{
    public function __construct(protected MetaLeadService $metaLeadService) {}

    public function verify(Request $request): SymfonyResponse
    {
        $challenge = $this->metaLeadService->verifyToken(
            $request->query('hub_mode') ?? $request->query('hub.mode'),
            $request->query('hub_verify_token') ?? $request->query('hub.verify_token'),
            $request->query('hub_challenge') ?? $request->query('hub.challenge')
        );

        if ($challenge === null) {
            return response('Forbidden', 403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request): Response
    {
        $secret = config('services.meta.app_secret');
        if ($secret) {
            $signature = $request->header('X-Hub-Signature-256');
            $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);
            if (! $signature || ! hash_equals($expected, $signature)) {
                return response('Invalid signature', 401);
            }
        }

        $this->metaLeadService->handleWebhook($request->all());

        return response('EVENT_RECEIVED', 200);
    }
}
