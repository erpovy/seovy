<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Seovy') }}</title>

    <!-- Dynamic Favicon -->
    @php
        $systemFavicon = \App\Models\SystemSetting::get('system_favicon', null) ?: \App\Models\SystemSetting::get('system_logo', null);
    @endphp
    @if ($systemFavicon)
        <link rel="icon" href="{{ $systemFavicon }}" id="app-favicon">
    @else
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" id="app-favicon">
    @endif

    <!-- Theme Initialization (prevents FOUC) -->
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('seovy_theme') || 'dark';
                var isDark = theme === 'dark' || (theme === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.classList.remove('light');
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.classList.add('light');
                    document.documentElement.classList.remove('dark');
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="h-full font-sans antialiased selection:bg-indigo-500 selection:text-white">
    @inertia
</body>
</html>
