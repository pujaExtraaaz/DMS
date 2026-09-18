<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserBranchScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $branchIds = $user ? $user->allowedBranchIds() : [];

        view()->share('allowedBranchIds', $branchIds);
        $request->attributes->set('allowed_branch_ids', $branchIds);
        app()->instance('allowed_branch_ids', $branchIds);

        return $next($request);
    }
}
