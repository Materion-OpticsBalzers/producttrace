<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Product Tracking') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <link href="{{ asset('fonts/font-awesome/css/fontawesome.min.css') }}" rel="stylesheet">
        <link href="{{ asset('fonts/font-awesome/css/all.min.css') }}" rel="stylesheet">
    </head>
    <body class="h-screen font-sans antialiased bg-gray-100 relative overflow-hidden">
        <livewire:layouts.navigation />
        <div class="pt-16 lg:pt-28 h-full w-full">
            {{ $slot }}
        </div>
    </body>
</html>
