<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DMS') }} — Sign In</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex">
            <div class="hidden lg:flex lg:w-1/2 bg-slate-900 text-white flex-col justify-between p-12">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-lg">D</div>
                        <span class="text-xl font-semibold tracking-tight">{{ config('app.name', 'DMS') }}</span>
                    </div>
                    <h1 class="mt-16 text-4xl font-bold leading-tight">Distribution Management<br>made simple.</h1>
                    <p class="mt-4 text-slate-400 text-lg max-w-md">Orders, inventory, billing, logistics, and settlement — unified in one professional platform.</p>
                </div>
                <p class="text-sm text-slate-500">&copy; {{ date('Y') }} Distribution Management System</p>
            </div>

            <div class="flex-1 flex flex-col justify-center items-center px-6 py-12 bg-gray-50">
                <div class="lg:hidden mb-8 flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-white text-lg">D</div>
                    <span class="text-xl font-semibold text-slate-900">{{ config('app.name', 'DMS') }}</span>
                </div>

                <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-100 px-8 py-10">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
