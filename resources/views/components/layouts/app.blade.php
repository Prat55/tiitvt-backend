<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' . config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('cdn')
</head>

<body class="min-h-screen font-sans antialiased bg-base-200">

    {{-- NAVBAR mobile only --}}
    <x-nav sticky class="lg:hidden">
        <x-slot:brand>
            <x-app-brand />
        </x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer" class="lg:hidden me-3">
                <x-icon name="o-bars-3" class="cursor-pointer" />
            </label>
        </x-slot:actions>
    </x-nav>

    {{-- MAIN --}}
    <x-main>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100">
            @php
                $role = App\Enums\RolesEnum::class;
                $user = auth()->user();
            @endphp
            {{-- MENU --}}
            <x-menu activate-by-route>
                <a href="{{ route('admin.index') }}" wire:navigate class="mb-3">
                    <div class="pt-3 hidden-when-collapsed ">
                        <div class="flex justify-center items-center cursor-pointer">
                            <img src="{{ asset('default/tiitvt_logo.svg') }}" alt="Logo" class="light-logo"
                                style="height: 100px">
                        </div>
                    </div>

                    <div class="display-when-collapsed hidden my-3 h-[25px] mx-3">
                        <img src="{{ asset('default/tiitvt_logo.svg') }}" alt="Logo" class="light-logo"
                            style="height: 100%">
                    </div>
                </a>

                <x-menu-item title="Dashboard" icon="o-home" link="{{ route('admin.index') }}" />
                @role($role::Admin->value)
                    <x-menu-item title="Center" icon="o-building-office" link="{{ route('admin.center.index') }}" />
                @endrole
                <x-menu-item title="Student" icon="o-user" link="{{ route('admin.student.index') }}" />
                @role($role::Admin->value)
                    <x-menu-item title="Category" icon="o-tag" link="{{ route('admin.category.index') }}" />
                    <x-menu-item title="Course" icon="o-book-open" link="{{ route('admin.course.index') }}" />
                @endrole

                <x-menu-sub title="Exam" icon="o-square-2-stack">
                    <x-menu-item title="Schedule" icon="o-calendar" link="{{ route('admin.exam.schedule') }}" />
                    <x-menu-item title="Exam" icon="o-book-open" link="{{ route('admin.exam.index') }}" />
                    <x-menu-item title="Results" icon="o-chart-bar" link="{{ route('admin.exam.results') }}" />

                    @role($role::Admin->value)
                        <x-menu-item title="Question" icon="o-question-mark-circle"
                            link="{{ route('admin.question.index') }}" />
                    @endrole
                </x-menu-sub>

                @role($role::Admin->value)
                    <x-menu-item title="Blog" icon="o-document-text" link="{{ route('admin.blog.index') }}" />
                    <x-menu-item title="Website Settings" icon="o-cog"
                        link="{{ route('admin.website-setting.index') }}" />
                    <x-menu-item title="Testimonials" icon="o-star" link="{{ route('admin.testimonial.index') }}" />
                @endrole
            </x-menu>
        </x-slot:sidebar>

        {{-- The `$slot` goes here --}}
        <x-slot:content class="bg-base-300">
            <div class="flex justify-end items-center gap-3 mb-5">
                <x-theme-toggle class="w-12 h-12 btn-sm btn-ghost" lightTheme="light" darkTheme="dark" responsive />
                <div class="gap-1.5">
                    <div class="tooltip tooltip-bottom" data-tip="Toggle Theme">
                    </div>
                    @auth
                        <div class="dropdown dropdown-bottom dropdown-end">
                            <label tabindex="0" class="btn btn-ghost rounded-btn px-1.5 hover:bg-base-content/20">
                                <div class="flex items-center gap-2">
                                    <div aria-label="Avatar photo" class="avatar placeholder">
                                        @if ($user->image)
                                            <div class="w-8 h-8 rounded bg-base-content/10">
                                                <img src="{{ asset($user->image) }}" alt="{{ $user->name }}">
                                            </div>
                                        @else
                                            <div
                                                class="w-8 h-8 rounded-full bg-primary text-primary-content !flex justify-center items-center">
                                                <span>
                                                    {{ substr($user->name, 0, 1) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex flex-col items-start">
                                        <p class="text-sm/none">
                                            {{ $user->name }}
                                        </p>
                                    </div>
                                </div>
                            </label>
                            <ul tabindex="0"
                                class="z-50 p-2 mt-4 shadow dropdown-content menu bg-base-100 rounded-box w-52"
                                role="menu">
                                <li>
                                    <a href="{{ route('admin.profile') }}" wire:navigate>
                                        My Profile
                                    </a>
                                </li>
                                <hr class="my-1 -mx-2 border-base-content/10" />
                                <li>
                                    <form action="{{ route('admin.logout') }}" method="POST"
                                        onsubmit="return confirm('Are you sure you want to log out?')">
                                        @csrf
                                        <button class="text-error">Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endauth
                </div>
            </div>

            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{--  TOAST area --}}
    <x-toast />
</body>

</html>
