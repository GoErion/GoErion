<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }} - {{ $title }}</title>

    <!-- Fonts -->
    <link
        rel="shortcut icon"
        href="{{ asset('/img/erion.svg') }}"
        type="image/x-icon"
    />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dark:bg-slate-950 bg-slate-200 text-shadow-slate-950 dark:text-slate-300 bg-center bg-repeat font-bold font-sans antialiased" style="background-image: url({{ asset('/img/bg.svg') }})">
<div class="container mx-auto mt-2">
    <x-header class="py-3 border border-slate-900 rounded-lg px-3"/>
    <main class="">
        {{ $slot }}
    </main>
    <x-footer/>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
</div>
</body>
</html>
