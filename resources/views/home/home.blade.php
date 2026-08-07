<x-app>
    <x-slot name="title">Home</x-slot>
    <div>
        <h1>
            Welcome Home!
        </h1>
    
        <div>
        @guest
            <div>
                <a href="{{ route('login') }}" class="py-2 px-4  bg-emerald-700 rounded-lg">
                    Login
                </a>
            </div>
        @endguest
        @auth
                <form action="{{ route('logout') }}" method="post">
                        <div>
                            <button type="submit" class="py-2 px-4 bg-pink-500 rounded-lg">
                                Logout
                            </button>
                        </div>
                </form>
        @endauth
        </div>
    </div>
</x-app>