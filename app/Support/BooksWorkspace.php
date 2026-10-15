<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class BooksWorkspace
{
    public const SESSION_KEY = 'books_workspace';

    public static function sync(): void
    {
        if (! Auth::check()) {
            session()->forget(self::SESSION_KEY);

            return;
        }

        if (request()->routeIs('books.tally*')) {
            session([self::SESSION_KEY => true]);

            return;
        }

        if (session(self::SESSION_KEY) && self::currentRouteIsBooksDestination()) {
            return;
        }

        session()->forget(self::SESSION_KEY);
    }

    public static function activate(): void
    {
        session([self::SESSION_KEY => true]);
    }

    public static function active(): bool
    {
        return (bool) session(self::SESSION_KEY);
    }

    public static function userCanAccessBooks(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'client-admin'])) {
            return true;
        }

        return method_exists($user, 'hasAnyPermission')
            && $user->hasAnyPermission(['tally.view', 'tally.manage', 'accounting.view']);
    }

    public static function showAside(): bool
    {
        return self::userCanAccessBooks() && self::active();
    }

    public static function currentRouteIsBooksDestination(): bool
    {
        $patterns = self::destinationActivePatterns();
        if ($patterns === []) {
            return false;
        }

        return request()->routeIs(...$patterns);
    }

    /**
     * @return list<string>
     */
    public static function destinationActivePatterns(): array
    {
        $patterns = ['books.tally*'];
        $config = config('books_sidebar', []);
        $items = $config['gateway'] ?? [];

        foreach ($config['categories'] ?? [] as $category) {
            foreach ($category['items'] ?? [] as $item) {
                $items[] = $item;
            }
        }

        foreach ($items as $item) {
            foreach ($item['active'] ?? [$item['route'] ?? null] as $pattern) {
                if (is_string($pattern) && $pattern !== '') {
                    $patterns[] = $pattern;
                }
            }
            if (! empty($item['route']) && is_string($item['route'])) {
                $patterns[] = $item['route'];
            }
        }

        return array_values(array_unique($patterns));
    }
}
