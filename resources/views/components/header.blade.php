<nav>
<div {{ $attributes }}>
    <div class="flex items-center justify-between">
       <div>
           <x-nav-link href="{{ route('home') }}" class="font-extrabold text-2xl">
               <div class="flex items-center space-x-1 rtl:space-x-reverse">
                   <img class="w-8 h-8" src="{{ asset('/img/erion.png') }}" alt="{{ config('app.name') }}">
                   <span class="self-center whitespace-nowrap text-2xl">
            {{ config('app.name') }}
            </span>
               </div>
           </x-nav-link>
       </div>
        <div>
            <x-search/>
        </div>
        <div class="flex">
            @auth
                <x-form action="{{ route('logout') }}" method="post">
                <div class="flex justify-end">
                    <x-button type="submit" class="border border-slate-800 rounded-lg">Logout</x-button>
                </div>
                </x-form>
            @else
                <div class="flex items-center md:order-2 space-x-1 md:space-x-2 rtl:space-x-reverse">
                    <x-nav-link href="{{ route('login') }}" class="font-extrabold text-bold px-4 border border-slate-600 hover:bg-slate-600 p-2 rounded-lg">{{ __('Login') }}</x-nav-link>
                    <x-nav-link href="{{ route('register') }}" class="font-extrabold text-bold px-4 border border-slate-600 hover:bg-slate-600 p-2 rounded-lg">{{ __('Register') }}</x-nav-link>
                </div>
            @endauth
        </div>
    </div>
</div>
    <x-navbar/>
</nav>
