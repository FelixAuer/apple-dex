<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#1e2419">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Apple-Dex">

        <link rel="manifest" href="/manifest.json">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/images/icon-192.png">

        <title>{{ config('app.name', 'Apple-Dex') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=baloo-2:600,700|nunito-sans:400,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-dex-bg text-dex-text">
        <div class="min-h-screen relative">
            <div class="grain-overlay fixed"></div>

            <livewire:layout.header />

            <main class="max-w-3xl mx-auto relative">
                {{ $slot }}
            </main>
        </div>

        <div
            x-data="{ show: false, message: '' }"
            x-on:toast.window="message = $event.detail.message; show = true; setTimeout(() => show = false, 3000)"
            x-show="show"
            x-transition
            x-cloak
            class="fixed bottom-24 left-1/2 -translate-x-1/2 z-50 bg-dex-card text-dex-text text-sm font-semibold px-4 py-2 rounded-full shadow-[0_4px_10px_rgba(0,0,0,0.35)] whitespace-nowrap"
        >
            <span x-text="message"></span>
        </div>

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js');
                });
            }
        </script>
    </body>
</html>
