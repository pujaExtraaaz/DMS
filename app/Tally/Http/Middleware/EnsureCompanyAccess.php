<?php

namespace Tally\Http\Middleware;

use Tally\Authorization\CompanyAccess;
use Tally\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->route('company');
        $id = $company instanceof Company ? $company->id : (is_numeric($company) ? (int) $company : null);

        if ($id !== null && ! CompanyAccess::allows($request->user(), $id)) {
            abort(404);
        }

        return $next($request);
    }
}
