<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tally\Context\WorkspaceContext;
use Tally\Support\Shell\ShellComposer;

class BindTallyWorkspace
{
    public function __construct(
        private readonly WorkspaceContext $context,
        private readonly ShellComposer $shell,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->resolve();

        view()->share('workspace', $this->context);
        view()->share($this->shell->data());

        return $next($request);
    }
}
