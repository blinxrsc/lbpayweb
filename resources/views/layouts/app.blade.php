<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <!-- app name -->
        <title>{{ config('app.name', 'LBPayLinker') }}</title>
        <!-- token csrf -->
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <!-- datepicker -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <style>
        /* Ensure editor content and displayed content look correct */
        .ck-content, .legal-content {
            min-height: 200px;
            line-height: 1.6;
        }
        .legal-content ul { list-style-type: disc !important; margin-left: 1.5rem !important; }
        .legal-content ol { list-style-type: decimal !important; margin-left: 1.5rem !important; }
        .legal-content h2 { font-size: 1.5rem; font-weight: bold; margin-top: 1rem; }
        .legal-content h3 { font-size: 1.2rem; font-weight: bold; margin-top: 0.8rem; }
        .legal-content p { margin-bottom: 1rem; }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen bg-gray-100">
            <!-- Sidebar -->
            <aside class="w-64 bg-white border-r flex flex-col">
                <!-- App Logo -->
                @php
                    $logo = \App\Models\Setting::where('key', 'site_logo')->first();
                    $favicon = \App\Models\Setting::where('key', 'site_favicon')->first();
                @endphp
                <div class="flex items-center space-x-2">
                    <img src="{{ $logo ? asset('storage/'.$logo->value) : asset('images/default-logo.png') }}"
                        alt="App Logo"
                        class="h-10 w-auto"
                    >
                    <link rel="icon" type="image/png" href="{{ $favicon ? asset('storage/'.$favicon->value) : asset('images/default-favicon.png') }}">
                    {{-- Branding Name --}}
                    <span class="text-l font-bold text-gray-800">
                        LBPayLinker ver1.2
                    </span>
                </div>
                <!-- Admin User on Top -->
                <div class="p-4 border-b">
                    <x-dropdown align="left" width="48">
                        <x-slot name="trigger">
                            <button class="w-full flex justify-between items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-700 bg-gray-50 hover:text-gray-900 focus:outline-none transition ease-in-out duration-150">
                                <x-heroicon-m-user class="w-4 h-4 mb-1" />{{ Auth::user()->name }}
                                <svg class="fill-current h-4 w-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                <!-- Sidebar Links -->
                <div class="flex-1 overflow-y-auto">
                    @include('layouts.sidebar')
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1">
                <!-- Disable header navigation because enable to sidebar -->
                {{--@include('layouts.navigation')--}}
                <!-- Page Heading -->
                @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
                @endisset
                <!-- Page Content -->
                <main>
                    {{-- ✅ Flash Message --}}  
                    @if (session('success'))
                        <div
                            x-data="{ show: true }"
                            x-init="setTimeout(() => show = false, 2500)"
                            x-show="show"
                            x-transition.opacity.duration.700ms
                            class="mb-4 rounded-md bg-emerald-200 p-4 text-emerald-800 border border-emerald-200"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-emerald-800">
                                    ✅ {{ session('success') }}
                                </span>
                                <button
                                    type="button"
                                    @click="show = false"
                                    class="text-emerald-500 hover:text-yellow-900"
                                    aria-label="Close"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div
                            x-data="{ show: true }"
                            x-init="setTimeout(() => show = false, 3500)"
                            x-show="show"
                            x-transition.opacity.duration.500ms
                            class="mb-4 rounded-md bg-red-50 p-4 text-red-500 border border-red-200"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-red-500">
                                    ❌ {{ session('error') }}
                                </span>
                                <button
                                    type="button"
                                    @click="show = false"
                                    class="text-red-500 hover:text-red-900"
                                    aria-label="Close"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    @endif
                    {{ $slot }}
                </main>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>
