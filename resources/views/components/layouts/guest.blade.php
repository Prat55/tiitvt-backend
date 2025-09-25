<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/x-icon" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' . config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @yield('cdn')
</head>

<body class="bg-base-300">
    <div>
        <div class="grid min-h-screen grid-cols-1 overflow-auto">
            <div class="flex items-center justify-center h-[90vh] select-none w-full">
                <div class="w-full h-full">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
    <x-toast />
    @livewireScripts
</body>

</html>
