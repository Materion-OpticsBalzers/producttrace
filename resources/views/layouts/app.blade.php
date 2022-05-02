<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Styles -->
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
        @livewireScripts
    </body>
</html>
