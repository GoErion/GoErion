<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>

    <meta charset="UTF-8">
    <metaname="viewport" content="width=device-width, initial-scale=1.0">
        @include('layouts.components.head')
    @vite(['resources/css/app.css','resources/js/app.js'])
    @livewireStyles
</head>
    <body class="bg-slate-950 text-slate-400 bg-center bg-repeat font-bold font-sans antialiased" style="background-image: url({{ asset('img/dots.svg') }})">
        <div>
            <div class="flex min-h-screen flex-col">
                <div class="mx-auto grow">
                    <main class="mt-4 ">
                        {{ $slot }}
                    </main>
                </div>
                @session('success')
                    <div class="p-4 rounded-lg bg-green-300">
                        {{ $value }}
                    </div>
                @endsession
            </div>
            @livewireScripts
        </div>
    </body>
</html>