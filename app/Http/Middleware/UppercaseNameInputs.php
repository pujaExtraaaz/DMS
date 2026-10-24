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
    protected function transformInput(array $data, ?string $parentKey = null): array
    {
        foreach ($data as $key => $value) {
            $effectiveField = is_numeric($key) ? ($parentKey ?? (string) $key) : (string) $key;
            if (is_array($value)) {
                $data[$key] = $this->transformInput($value, $effectiveField);
            } elseif (is_string($value) && !is_numeric($effectiveField) && NameCase::isApplicableField($effectiveField)) {
                $data[$key] = NameCase::format($value);
            }
        }

        return $data;
    }
}
