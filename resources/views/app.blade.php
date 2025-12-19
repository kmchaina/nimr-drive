<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title inertia>{{ config('app.name', 'Drive') }}</title>

        <!-- Theme (light default, dark optional) -->
        <script>
            (function () {
                try {
                    // Reset theme preference once (v2 = light mode default)
                    if (localStorage.getItem('theme_version') !== 'v2') {
                        localStorage.removeItem('theme');
                        localStorage.setItem('theme_version', 'v2');
                    }
                    var theme = localStorage.getItem('theme');
                    if (theme === 'dark') document.documentElement.classList.add('dark');
                } catch (e) {}
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased overflow-hidden">
        @inertia
    </body>
</html>