<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Audit\AuditLogger;
use Tally\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthTokenController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! $user->is_active || ! Hash::check($data['password'], $user->password)) {
            app(AuditLogger::class)->security('login_failed', 'API token request rejected for '.$data['email'].'.');

            return response()->json(['message' => 'These credentials do not match our records.'], 401);
        }

        $issued = ApiToken::issue($user, $data['name'] ?? 'API token');

        return $this->data([
            'token' => $issued['plain_text'],
            'token_type' => 'Bearer',
            'name' => $issued['token']->name,
            'expires_at' => $issued['token']->expires_at?->toIso8601String(),
        ], 201);
    }
}
