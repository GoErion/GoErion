<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>

    <meta charset="UTF-8">
    <metaname="viewport" content="width=device-width, initial-scale=1.0">
        @include('layouts.components.head')
    @vite(['resources/css/app.css','resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-950 text-slate-400 bg-center bg-repeat font-bold font-sans antialiased m-4" style="background-image: url({{ asset('img/dots.svg') }})">
   <div class="container mx-auto">
       <div class="flex items-center justify-center">
           <div class="">
            <a href="{{ route('marketing') }}">
                <img width="200" height="200" src="{{ asset('img/erion.png') }}" alt="erion logo">
            </a>
           </div>
       </div>
       <main>
           {{ $slot }}
       </main>
       @livewireScripts
   </div>
</body>
</html>