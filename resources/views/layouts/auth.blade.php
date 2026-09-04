<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('layouts.components.head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 bg-center bg-repeat font-sans text-slate-300 antialiased" style="background-image: url({{ asset('img/dots.svg') }})">
    <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-8 sm:px-6 lg:px-8">
        <header class="flex justify-center">
            <a href="{{ route('marketing') }}" class="rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-500 focus-visible:ring-offset-4 focus-visible:ring-offset-slate-950">
                <img width="160" height="160" src="{{ asset('img/erion.png') }}" alt="GoErion">
            </a>
        </header>
        <main class="flex flex-1 items-center justify-center py-8">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
