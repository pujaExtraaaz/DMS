<?php

namespace App\Http\Middleware;

use App\Support\NameCase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UppercaseNameInputs
{
    /**
     * Handle an incoming request and ensure all applicable human-readable text fields are formatted in Title Case.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        if (!empty($input)) {
            $transformed = $this->transformInput($input);
            $request->replace($transformed);
        }

        return $next($request);
    }

    /**
     * Recursively normalize input values where keys represent human-readable text fields into Title Case.
     */
    protected function transformInput(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->transformInput($value);
            } elseif (is_string($value) && NameCase::isApplicableField((string) $key)) {
                $data[$key] = NameCase::format($value);
            }
        }

        return $data;
    }
}
