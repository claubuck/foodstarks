<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Reverb: URL para el navegador (localhost, no "reverb") -->
        <script>
            window.REVERB_APP_KEY = @json(config('broadcasting.connections.reverb.key'));
            window.REVERB_WS_HOST = @json(config('broadcasting.connections.reverb.public_host', 'localhost'));
            window.REVERB_WS_PORT = @json(config('broadcasting.connections.reverb.public_port', 8080));
            window.REVERB_WS_SCHEME = @json(config('broadcasting.connections.reverb.public_scheme', 'http'));
        </script>
        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
