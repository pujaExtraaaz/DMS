<?php

if (! function_exists('dms_vite_ready')) {
    /**
     * True only when the Vite manifest exists AND every requested entry file is on disk.
     *
     * @param  array<int, string>|null  $entries
     */
    function dms_vite_ready(?array $entries = null): bool
    {
        $manifestPath = public_path('build/manifest.json');
        if (! is_file($manifestPath)) {
            return false;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (! is_array($manifest)) {
            return false;
        }

        $entries ??= ['resources/css/app.css', 'resources/js/app.js'];

        foreach ($entries as $entry) {
            $file = $manifest[$entry]['file'] ?? null;
            if (! is_string($file) || $file === '' || ! is_file(public_path('build/'.$file))) {
                return false;
            }
        }

        return true;
    }
}

if (! function_exists('dms_vite_cdn_fallback')) {
    /**
     * Inline Tailwind CDN tags used when a production Vite build is unavailable.
     */
    function dms_vite_cdn_fallback(bool $withAppJs = true): string
    {
        $html = <<<'HTML'
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                colors: {
                    dms: { sidebar: '#0F172A', active: '#4F46E5' },
                    violet: {
                        50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc',
                        400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca',
                        800: '#3730a3', 900: '#312e81',
                    },
                },
            },
        },
    };
</script>
<style type="text/tailwindcss">
    @tailwind base;
    @tailwind components;
    @tailwind utilities;
    [x-cloak] { display: none !important; }
    .dms-sidebar { background-color: #0f172a !important; color: #fff !important; }
</style>
HTML;

        if ($withAppJs) {
            $html .= "\n".'<script type="module" src="'.e(asset('js/app-fallback.js')).'"></script>';
        }

        return $html;
    }
}

if (! function_exists('dms_money_whole')) {
    /**
     * Full amount rounded to the nearest rupee, Indian grouping (hover tooltip).
     */
    function dms_money_whole(mixed $amount, string $symbol = '₹'): string
    {
        $num = (float) $amount;
        $rounded = (int) round($num);
        $negative = $rounded < 0;
        $whole = (string) abs($rounded);

        if (strlen($whole) > 3) {
            $last = substr($whole, -3);
            $rest = substr($whole, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) ?? $rest;
            $whole = $rest.','.$last;
        }

        return ($negative ? '-' : '').$symbol.$whole;
    }
}

if (! function_exists('dms_money_short')) {
    /**
     * Compact Indian amount for KPI cards: floored whole K+ / Lakh+ / Cr+.
     * e.g. 6.1 Cr and 6.9 Cr both display as "₹6 Cr+".
     */
    function dms_money_short(mixed $amount, string $symbol = '₹'): string
    {
        $num = (float) $amount;
        $abs = abs($num);
        $sign = $num < 0 ? '-' : '';

        if ($abs >= 10000000) {
            return $sign.$symbol.((int) floor($abs / 10000000)).' Cr+';
        }

        if ($abs >= 100000) {
            return $sign.$symbol.((int) floor($abs / 100000)).' Lakh+';
        }

        if ($abs >= 1000) {
            return $sign.$symbol.((int) floor($abs / 1000)).' K+';
        }

        return $sign.$symbol.(int) round($abs);
    }
}
