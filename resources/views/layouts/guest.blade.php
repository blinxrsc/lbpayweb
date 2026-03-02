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
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
