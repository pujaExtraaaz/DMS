@props(['title' => null, 'scene' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <main @class(['gate', 'is-login' => $scene === 'login'])>
        <section class="gate-card">
            <div class="brand gate-brand">
                <span class="brand-mark" aria-hidden="true">TC</span>
                <span>
                    <strong>{{ config('app.name') }}</strong>
                    <small>Books</small>
                </span>
            </div>
            {{ $slot }}
        </section>
    </main>
</body>
</html>
