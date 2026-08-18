<x-app>
    <x-slot name="title">Home</x-slot>

    <div class="max-w-6xl mx-auto ">

        {{-- Header --}}
        <div
            class="mb-8 flex flex-col gap-4 rounded-2xl border border-slate-800 bg-transparent py-2 px-4 shadow-xl lg:flex-row lg:items-center lg:justify-between">

            <div>
                <h1 class="text-3xl font-bold text-slate-400">Home</h1>
            </div>

            {{-- Company Actions --}}
            <div class="flex flex-wrap items-center gap-3">
                @foreach ($companies as $company)

                    <a href="{{ route('branch.create', $company) }}"
                        class="rounded-lg border border-slate-800 bg-transparent px-4 py-2 text-sm text-slate-400 hover:bg-slate-800">
                        Branch
                    </a>

                    <a href="{{ route('warehouse.create', $company) }}"
                        class="rounded-lg border border-slate-800 bg-transparent px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">
                        Warehouse
                    </a>

                    <a href="{{ route('settings.edit', $company) }}"
                        class="rounded-lg border border-slate-800 bg-transparent px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">
                        Settings
                    </a>
                    <a href="{{ route('brand.create', $company) }}" class="rounded-lg border border-slate-800 bg-transparent px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">
                        Create Brand
                    </a>
                    
                    <a href="{{ route('invitations.create', $company) }}"
                        class="rounded-lg border border-slate-800 bg-transparent px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">
                        Invite User
                    </a>
                    <a href="{{ route('unit.create', $company) }}"
                        class="rounded-lg border border-slate-800 bg-transparent px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">
                        unit.create
                    </a>
                    <a href="{{ route('customer.create', $company) }}" class="rounded-lg border border-slate-800 bg-transparent px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">
                        Customers
                    </a>
                    <a href="{{ route('category.create', $company) }}" class="rounded-lg border border-slate-800 bg-transparent px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">
                        Category
                    </a>

                @endforeach
            </div>

            {{-- Authentication --}}
            <div class="flex items-center gap-3">

                @guest
                    <a href="{{ route('login') }}"
                        class="rounded-lg bg-emerald-700 px-4 py-2 text-slate-400 shadow-sm hover:bg-emerald-600">
                        Login
                    </a>
                @endguest

                @auth
                    <details class="relative">

                        <summary
                            class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-700 bg-slate-900 px-4 py-2 text-slate-400 shadow-sm">
                            <span>{{ auth()->user()->name }}</span>
                            <span class="text-slate-500">▾</span>
                        </summary>

                        <div
                            class="absolute right-0 z-20 mt-2 w-64 overflow-hidden rounded-3xl border border-slate-800 bg-slate-950/95 p-4 shadow-xl">

                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">
                                Signed in as
                            </p>

                            <p class="mb-3 text-sm font-semibold text-white">
                                {{ auth()->user()->email }}
                            </p>

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


        {{-- Dashboard --}}
        <div class="grid gap-6 lg:grid-cols-[280px_1fr]">

            {{-- Sidebar --}}
            <aside
                class="space-y-5 rounded-3xl border border-slate-800 bg-transparent p-6 shadow-xl">

                <div class="space-y-2">
                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">
                        Quick nav
                    </p>

                    <h2 class="text-xl font-semibold text-white">
                        Dashboard
                    </h2>
                </div>


                <nav class="space-y-3">

                    <a href="{{ route('home') }}"
                        class="block rounded-2xl border border-slate-800 bg-transparent px-4 py-2 text-slate-200 hover:bg-slate-900/90">
                        Home overview
                    </a>

                    <a href="{{ route('profile.edit') }}"
                        class="block rounded-2xl border border-slate-800 bg-transparent px-4 py-2 text-slate-200 hover:bg-slate-900/90">
                        Edit profile
                    </a>


                    {{-- Companies --}}
                    @foreach ($companies as $company)

                        <div class="pt-3">

                            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                {{ $company->name }}
                            </p>

                            {{-- Branches --}}
                            @foreach ($company->branches as $branch)

                                <div class="mb-3 ml-2">

                                    <p class="mb-2 text-sm font-medium text-slate-300">
                                        {{ $branch->name }}
                                    </p>

                                    {{-- Warehouses --}}
                                    @foreach ($branch->warehouses as $warehouse)

                                        <a href="{{ route('location.create', $warehouse) }}"
                                            class="mb-2 ml-2 block rounded-2xl border border-slate-800 bg-transparent px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">

                                            {{ $warehouse->warehouse_name }}

                                        </a>

                                    @endforeach

                                </div>

                            @endforeach

                        </div>

                    @endforeach

                </nav>


                {{-- Account --}}
                <div
                    class="rounded-3xl border border-slate-800 bg-transparent px-4 py-2 text-slate-300">

                    <p class="text-sm uppercase tracking-[0.24em] text-slate-500">
                        Account
                    </p>

                    @auth

                        <p class="mt-3 text-sm text-white">
                            {{ auth()->user()->name }}
                        </p>

                        <p class="text-sm text-slate-400">
                            {{ auth()->user()->email }}
                        </p>

                    @else

                        <p class="mt-3 text-sm">
                            Please log in to access profile controls.
                        </p>

                    @endauth

                </div>

            </aside>


            {{-- Main Content --}}
            <main
                class="rounded-3xl border border-slate-800 bg-transparent px-4 py-4 shadow-xl">

                <h2 class="text-2xl font-bold text-white">
                    Welcome Home!
                </h2>

                <p class="mt-4 text-slate-300">
                    This is your personal dashboard area. Use the navigation
                    to manage your companies, branches, warehouses and locations.
                </p>


                {{-- Dashboard Cards --}}
                <div class="mt-8 grid gap-6 sm:grid-cols-2">

                    <div
                        class="rounded-3xl border border-slate-800 bg-slate-950/80 p-6">

                        <h3 class="text-lg font-semibold text-slate-400">
                            Today
                        </h3>

                        <p class="mt-3 text-slate-400">
                            Check your profile settings or update your account details.
                        </p>

                    </div>


                    <div
                        class="rounded-3xl border border-slate-800 bg-slate-950/80 p-6">

                        <h3 class="text-lg font-semibold text-slate-400">
                            Actions
                        </h3>

                        <p class="mt-3 text-slate-400">
                            Use the sidebar links to navigate quickly across your dashboard.
                        </p>

                    </div>

                </div>

            </main>

        </div>

    </div>
</x-app>