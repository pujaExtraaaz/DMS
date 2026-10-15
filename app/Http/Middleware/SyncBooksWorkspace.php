<?php

namespace App\Http\Middleware;

use App\Support\BooksWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncBooksWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        BooksWorkspace::sync();

        return $next($request);
    }
}
