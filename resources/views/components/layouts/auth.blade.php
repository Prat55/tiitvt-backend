<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/x-icon" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' . config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="">
    <div>
        <div class="grid min-h-screen grid-cols-1 overflow-auto">
            <div class="col-span-12 lg:col-span-6">
                <div class="flex items-end justify-end ">
                    <div class="tooltip tooltip-left " data-tip="Toggle Theme">
                        <x-theme-toggle class="w-12 h-12 btn-sm btn-ghost" lightTheme="light" darkTheme="dark" />
                    </div>
                </div>
                <div class="flex items-center justify-center h-full">

                    <div class="" style="width: 450px">
                        <a href="/">
                            <div class="w-full flex justify-center">
                                <img src="{{ asset('default/logo.svg') }}" alt="Logo" class="light-logo"
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
</body>

</html>
