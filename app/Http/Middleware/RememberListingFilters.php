<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RememberListingFilters
{
    /**
     * Query keys that should never be persisted as listing filters.
     *
     * @var list<string>
     */
    protected array $ignoredKeys = [
        'page',
        'export',
        'reset_filters',
        '_token',
        '_method',
    ];

    /**
     * Routes that must not remember filters (sidebar shortcuts share the same index).
     *
     * @var list<string>
     */
    protected array $excludedRoutes = [
        'service.tickets.index',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if (! is_string($routeName) || $routeName === '' || ! str_ends_with($routeName, '.index')) {
            return $next($request);
        }

        if (in_array($routeName, $this->excludedRoutes, true)) {
            $request->session()->forget($this->sessionKey($routeName));

            return $next($request);
        }

        $sessionKey = $this->sessionKey($routeName);

        if ($request->boolean('reset_filters')) {
            $request->session()->forget($sessionKey);

            if ($this->isDynamicListingRequest($request)) {
                foreach (array_keys($request->query()) as $key) {
                    $request->query->remove($key);
                }

                return $next($request);
            }

            return redirect()->to($request->url());
        }

        $filters = $this->extractFilters($request);

        if ($filters !== []) {
            $request->session()->put($sessionKey, $filters);

            return $next($request);
        }

        // Dynamic listing calls with an empty query intentionally cleared filters.
        // Do not rehydrate from session in that case — wipe the saved state instead.
        if ($this->isDynamicListingRequest($request)) {
            $request->session()->forget($sessionKey);

            return $next($request);
        }

        $saved = $request->session()->get($sessionKey);
        if (! is_array($saved) || $saved === []) {
            return $next($request);
        }

        $saved = collect($saved)
            ->reject(fn ($value, $key) => in_array((string) $key, $this->ignoredKeys, true))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        if ($saved === []) {
            $request->session()->forget($sessionKey);

            return $next($request);
        }

        $query = $saved;
        if ($request->filled('page')) {
            $query['page'] = $request->query('page');
        }

        return redirect()->to($request->url().'?'.http_build_query($query));
    }

    protected function sessionKey(string $routeName): string
    {
        return 'listing_filters.'.$routeName;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractFilters(Request $request): array
    {
        return collect($request->query())
            ->reject(fn ($value, $key) => in_array((string) $key, $this->ignoredKeys, true))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(function ($value) {
                if (is_array($value)) {
                    return collect($value)
                        ->filter(fn ($item) => $item !== null && $item !== '')
                        ->all();
                }

                return $value;
            })
            ->filter(fn ($value) => ! is_array($value) || $value !== [])
            ->all();
    }

    protected function isDynamicListingRequest(Request $request): bool
    {
        return $request->headers->has('X-DMS-Dynamic')
            || $request->ajax()
            || $request->wantsJson();
    }
}
