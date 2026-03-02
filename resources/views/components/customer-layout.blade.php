<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-100">
    <!-- App Logo -->
    @php
        $logo = \App\Models\Setting::where('key', 'site_logo')->first();
        $favicon = \App\Models\Setting::where('key', 'site_favicon')->first();
    @endphp
    <div class="flex items-center justify-center space-x-2">
        {{-- Logo --}}
        <img src="{{ $logo ? asset('storage/'.$logo->value) : asset('images/default-logo.png') }}"
            alt="App Logo"
            class="h-10 w-auto">
        <link rel="icon" type="image/png" href="{{ $favicon ? asset('storage/'.$favicon->value) : asset('images/default-favicon.png') }}">
        {{-- Branding Name --}}
        <span class="text-xl font-bold text-gray-800">
            LBPayLinker
        </span>
    </div>
    <!-- Customer Header -->
    <header class="bg-white shadow">
        @if(request()->routeIs('customer.dashboard'))
            {{-- This hides the balance display on the dashboard --}}
        @else
            <div class="max-w-7xl mx-auto py-4 px-4 flex justify-between items-center">
                <p>My Balance: RM {{ number_format(auth('customer')->user()->wallet_balance, 2) }}</p>
            </div>
        @endif
        <div class="max-w-7xl mx-auto py-4 px-4 flex justify-between items-center">
            {{-- Breadcrumbs or header slot --}}
            {{ $header ?? '' }}

            {{-- Optional quick nav links --}}
            <nav class="space-x-4">
                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf
                    <x-primary-button type="submit">
                        Logout
                    </x-primary-button>
                </form>

            </nav>
        </div>
    </header>

    <!-- Customer Content -->
    <main class="pb-24"> {{-- extra bottom padding so content doesn't overlap nav --}}
        {{ $slot }}
    </main>

    <!-- Bottom Navigation Bar -->
    @if($showNav)
        <x-customer-bottom-nav />
    @endif
</body>
</html>