<x-app>
    <x-slot name="title">Home</x-slot>

    <div class="max-w-6xl mx-auto px-4">
        <div
            class="mb-8 flex flex-col gap-4 rounded-3xl border border-slate-800 bg-slate-950/80 p-5 shadow-xl sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white">Home</h1>
            </div>
            @foreach ($companies as $company)
               <div class="flex items-center justify-center space-x-4">
                 <div>
                <a href="{{ route('branch.create',$company) }}" class="border border->slate-800 bg-transparent rounded-lg py-2 px-4">Branch</a>
                </div>
                 <div>
                <a href="{{ route('warehouse.create',$company) }}" class="border border->slate-800 bg-transparent rounded-lg py-2 px-4">Warehouse</a>
                </div>
               </div>
            @endforeach

            <div class="flex items-center gap-3">
                @guest
                    <a href="{{ route('login') }}"
                        class="rounded-2xl bg-emerald-700 px-4 py-2 text-white shadow-sm hover:bg-emerald-600">
                        Login
                    </a>
                @endguest

                @auth
                    <details class="relative">
                        <summary
                            class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-700 bg-slate-900 px-4 py-2 text-slate-200 shadow-sm">
                            <span>{{ auth()->user()->name }}</span>
                            <span class="text-slate-500">▾</span>
                        </summary>

                        <div
                            class="absolute right-0 z-20 mt-2 w-64 overflow-hidden rounded-3xl border border-slate-800 bg-slate-950/95 p-4 shadow-xl">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Signed in as</p>
                            <p class="mb-3 text-sm font-semibold text-white">{{ auth()->user()->email }}</p>
                            <a href="{{ route('profile.edit') }}"
                                class="block rounded-2xl px-3 py-2 text-slate-200 hover:bg-slate-900">
                                Profile
                            </a>
                            <form action="{{ route('logout') }}" method="post" class="mt-2">
                                @csrf
                                <button type="submit"
                                    class="w-full rounded-2xl px-3 py-2 text-left text-red-400 hover:bg-slate-900">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </details>
                @endauth
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
            <aside class="space-y-5 rounded-3xl border border-slate-800 bg-slate-950/90 p-6 shadow-xl">
                <div class="space-y-2">
                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Quick nav</p>
                    <h2 class="text-xl font-semibold text-white">Dashboard</h2>
                </div>

                <nav class="space-y-3">
                    <a href="{{ route('home') }}"
                        class="block rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 text-slate-200 hover:bg-slate-900/90">
                        Home overview
                    </a>
                    <a href="{{ route('profile.edit') }}"
                        class="block rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 text-slate-200 hover:bg-slate-900/90">
                        Edit profile
                    </a>
                </nav>

                <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-4 text-slate-300">
                    <p class="text-sm uppercase tracking-[0.24em] text-slate-500">Account</p>
                    @auth
                        <p class="mt-3 text-sm text-white">{{ auth()->user()->name }}</p>
                        <p class="text-sm text-slate-400">{{ auth()->user()->email }}</p>
                    @else
                        <p class="mt-3 text-sm">Please log in to access profile controls.</p>
                    @endauth
                </div>
            </aside>

            <main class="rounded-3xl border border-slate-800 bg-slate-900/80 p-8 shadow-xl">
                <h2 class="text-2xl font-bold text-white">Welcome Home!</h2>
                <p class="mt-4 text-slate-300">This is your personal dashboard area. Use the profile menu to access your
                    account actions.</p>

                <div class="mt-8 grid gap-6 sm:grid-cols-2">
                    <div class="rounded-3xl border border-slate-800 bg-slate-950/80 p-6">
                        <h3 class="text-lg font-semibold text-white">Today</h3>
                        <p class="mt-3 text-slate-400">Check your profile settings or update your account details.</p>
                    </div>
                    <div class="rounded-3xl border border-slate-800 bg-slate-950/80 p-6">
                        <h3 class="text-lg font-semibold text-white">Actions</h3>
                        <p class="mt-3 text-slate-400">Use the sidebar links to navigate quickly across your dashboard.
                        </p>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app>
