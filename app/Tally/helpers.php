<?php

if (! function_exists('tally_route')) {
    function tally_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        if (! str_starts_with($name, 'books.tally.')) {
            $name = 'books.tally.'.$name;
        }

        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('tally_super_admin')) {
    function tally_super_admin(mixed $user = null): bool
    {
        $user ??= auth()->user();

        return $user
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super-admin', 'client-admin']);
    }
}

if (! function_exists('tally_route_has')) {
    function tally_route_has(string $name): bool
    {
        if (! str_starts_with($name, 'books.tally.')) {
            $name = 'books.tally.'.$name;
        }

        return \Illuminate\Support\Facades\Route::has($name);
    }
}
