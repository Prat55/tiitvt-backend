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
</head>

<body class="bg-base-300">
    <div>
        <div class="grid min-h-screen grid-cols-1 overflow-auto">
            <div class="col-span-12 lg:col-span-6">
                <div class="flex items-end justify-end ">
                    <div class="tooltip tooltip-left " data-tip="Toggle Theme">
                        <x-theme-toggle class="w-12 h-12 btn-sm btn-ghost" lightTheme="light" darkTheme="dark" />
                    </div>
                </div>
                <div class="flex items-center justify-center h-[90vh] select-none">
                    <div class="w-[450px] bg-base-200 py-10 px-4 rounded-lg">
                        <a href="{{ url('/') }}">
                            <div class="w-full flex justify-center rounded-full overflow-hidden">
                                <img src="{{ asset('default/tiitvt_logo.png') }}" alt="Logo" class="light-logo"
                                    style="height: 120px">
                            </div>
                        </a>

                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-toast />
    @livewireScripts
</body>

</html>
