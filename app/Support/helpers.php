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
