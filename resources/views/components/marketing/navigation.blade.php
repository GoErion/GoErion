<x-marketing.button href="#product-preview" :secondary="true">Product</x-marketing.button>
@guest
    <x-marketing.button :href="route('login')" :secondary="true">Sign in</x-marketing.button>
    <x-marketing.button :href="route('register')">Get started</x-marketing.button>
@else
    <x-marketing.button :href="route('companies.select')">Open GoErion</x-marketing.button>
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="min-h-11 w-full rounded-md px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-800">Log out</button>
    </form>
@endguest
