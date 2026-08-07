<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{ asset('img/eicon.png') }}" type="image/x-icon">

    <title>{{ $title }}</title>

    <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
    <body class="bg-slate-950 text-slate-400 m-4 border border-slate-900 rounded-2xl bg-center bg-repeat font-bold font-sans antialiased" style="background-image: url({{ asset('img/dots.svg') }})">
        <div>
            <div class="flex min-h-screen flex-col">
                <div class="mx-auto ml-4 mr-4 grow">
                    <main class="mt-16 ">
                        {{ $slot }}
                    </main>
                </div>
                @session('success')
                    <div class="p-4 rounded-lg bg-green-300">
                        {{ $value }}
                    </div>
                @endsession
            </div>
        </div>
    </body>
</html>