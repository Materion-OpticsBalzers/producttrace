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

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script src="https://kit.fontawesome.com/7307de8da2.js" crossorigin="anonymous"></script>
        <script src="https://cdn.tiny.cloud/1/h6uktvbmku9vx92hi54xzfljo7gcp11mvyoab0vfjwdi0u5m/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    </head>
    <body class="h-screen font-sans antialiased bg-gray-100 relative overflow-hidden">
        <livewire:layouts.navigation />
        <div class="pt-28 h-full w-full">
            {{ $slot }}
        </div>
    </body>
</html>
