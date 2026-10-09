<?php

namespace Tally\Http\Middleware;

use Tally\Audit\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ExpireIdleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $limit = (int) config('session.lifetime', 120) * 60;
        $last = (int) $request->session()->get('last_activity_at', 0);

        if ($last > 0 && (now()->timestamp - $last) > $limit) {
            app(AuditLogger::class)->security('session_expired', 'Session ended after inactivity.');
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Your session ended after a period of inactivity.');
        }

        $response = $next($request);
        $request->session()->put('last_activity_at', now()->timestamp);

        return $response;
    }
}
