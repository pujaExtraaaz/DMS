<?php

namespace App\Support;

class ScreenHelp
{
    /**
     * @return array{title: string, summary: string, steps: array<int, string>}|null
     */
    public static function forRoute(?string $routeName): ?array
    {
        if (! $routeName) {
            return config('screen_help.default');
        }

        $map = config('screen_help', []);
        unset($map['default']);

        $keys = array_keys($map);
        usort($keys, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($keys as $key) {
            if ($routeName === $key || str_starts_with($routeName, $key.'.') || str_starts_with($routeName, $key)) {
                return $map[$key] + ['key' => $key];
            }
        }

        // Module fallback: first segment
        $segment = explode('.', $routeName)[0] ?? null;
        if ($segment && isset($map[$segment])) {
            return $map[$segment] + ['key' => $segment];
        }

        return config('screen_help.default');
    }
}
