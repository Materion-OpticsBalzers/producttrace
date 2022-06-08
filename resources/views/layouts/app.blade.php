<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

        <link rel="stylesheet" href="{{ asset('css/app.css') }}">

        <!-- Scripts -->
        <script src="{{ asset('js/app.js') }}" defer></script>

        <script src="https://kit.fontawesome.com/7307de8da2.js" crossorigin="anonymous"></script>
        <script>
            window.Userback = window.Userback || {};
            Userback.access_token = '8943|68154|VP5n11xg74kXbodka8MWM0vqVClnuSmGg5x96nhfEPbgbovKBV';
            (function(d) {
                var s = d.createElement('script');s.async = true;
                s.src = 'https://static.userback.io/widget/v1.js';
                (d.head || d.body).appendChild(s);
            })(document);
        </script>
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <div class="h-screen bg-gray-100">
            @include('layouts.navigation')

            {{ $slot }}
        </div>
        @include('sweetalert::alert')
        @livewireScripts
    </body>
</html>
