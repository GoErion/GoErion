<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body class="bg-black text-slate-400 font-bold flex p-6 lg:p-8 items-center lg: font-sans bg-center bg-repeat antialiased min-h-screen">
<div class="container mx-auto mt-10">
    <main class="mt-4">
        {{ $slot }}
    </main>
</div>
</body>
</html>
