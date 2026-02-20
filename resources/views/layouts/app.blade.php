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
    <body class="font-sans antialiased">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-3 focus:py-2 focus:text-sm focus:font-semibold focus:text-slate-900"
        >
            Asosiy kontentga o'tish
        </a>
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main id="main-content" tabindex="-1">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-4">
                    @if (session('status'))
                        <div class="mb-4 rounded border border-green-300 bg-green-100 p-3 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="mb-4 rounded border border-amber-300 bg-amber-100 p-3 text-sm text-amber-800">
                            {{ session('warning') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded border border-red-300 bg-red-100 p-3 text-sm text-red-800">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
