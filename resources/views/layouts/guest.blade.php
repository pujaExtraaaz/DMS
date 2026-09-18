<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0f4c81">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="DMS">

        <title>{{ config('app.name', 'DMS') }} — Sign In</title>

        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('css/pwa-mobile.css') }}">
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-[100dvh] flex">
            <div class="hidden lg:flex lg:w-1/2 bg-slate-900 text-white flex-col justify-between p-12">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-lg">D</div>
                        <span class="text-xl font-semibold tracking-tight">{{ config('app.name', 'DMS') }}</span>
                    </div>
                    <h1 class="mt-16 text-4xl font-bold leading-tight">Distribution Management<br>made simple.</h1>
                    <p class="mt-4 text-slate-400 text-lg max-w-md">Orders, inventory, billing, logistics, and settlement — unified in one professional platform. Install as an app on your phone.</p>
                </div>
                <p class="text-sm text-slate-500">&copy; {{ date('Y') }} Distribution Management System</p>
            </div>

            <div class="flex-1 flex flex-col justify-center items-center px-5 py-10 sm:px-6 sm:py-12 bg-gray-50">
                <div class="lg:hidden mb-8 flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-white text-lg">D</div>
                    <span class="text-xl font-semibold text-slate-900">{{ config('app.name', 'DMS') }}</span>
                </div>

                <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-8 sm:px-8 sm:py-10">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-slate-500 lg:hidden max-w-xs">
                    On iPhone: Share → Add to Home Screen. On Android: browser menu → Install app.
                </p>
            </div>
        </div>
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {});
                });
            }
        </script>
    </body>
</html>
