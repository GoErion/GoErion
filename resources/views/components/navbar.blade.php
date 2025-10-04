<nav class="mt-4">
    <div class="mx-auto px-4 sm:px-6 lg:px-0">
        <div class="flex justify-between">
            <div class="flex">
                <div class="mr-2 -ml-2 flex items-center md:hidden">
                    <!-- Mobile menu button -->
                    <button type="button" class="relative inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden focus:ring-inset" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="absolute -inset-0.5"></span>
                        <span class="sr-only">Open main menu</span>
                        <!--
                          Icon when menu is closed.

                          Menu open: "hidden", Menu closed: "block"
                        -->
                        <svg class="block size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <!--
                          Icon when menu is open.

                          Menu open: "block", Menu closed: "hidden"
                        -->
                        <svg class="hidden size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="hidden md:flex md:space-x-8">
                    <!-- Current: "border-indigo-500 text-gray-900", Default: "border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700" -->


                    <button id="multiLevelDropdownButton" data-dropdown-toggle="multi-dropdown" class="bg-slate-900 hover:text-slate-600 font-bold px-6 py-2.5 text-center inline-flex items-center rounded-lg" type="button">Categories<svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>

                    <!-- Dropdown menu -->
                    <div id="multi-dropdown" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-slate-900">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="multiLevelDropdownButton">
                            <li>
                                <a href="#" class="block px-4 py-2 dark:hover:bg-slate-600 rounded-lg">Dashboard</a>
                            </li>
                            <li>
                                <button id="doubleDropdownButton" data-dropdown-toggle="doubleDropdown" data-dropdown-placement="right-start" type="button" class="flex items-center justify-between w-full px-4 py-2 hover:bg-slate-300 dark:hover:bg-slate-600 rounded-lg">Dropdown<svg class="w-2.5 h-2.5 ms-3 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                    </svg></button>
                                <div id="doubleDropdown" class="z-10 hidden bg-white divide-y divide-slate-300 rounded-lg shadow-sm w-44 dark:bg-slate-900">
                                    <ul class="py-2 text-gray-700 dark:text-gray-200" aria-labelledby="doubleDropdownButton">
                                        <li>
                                            <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg">Overview</a>
                                        </li>
                                        <li>
                                            <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg">My downloads</a>
                                        </li>
                                        <li>
                                            <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg">Billing</a>
                                        </li>
                                        <li>
                                            <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg">Rewards</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li>
                                <a href="#" class="block px-4 py-2 hover:bg-slate-300 dark:hover:bg-slate-600 rounded-lg">Earnings</a>
                            </li>
                            <li>
                                <a href="#" class="block px-4 py-2 hover:bg-slate-300 dark:hover:bg-slate-600 rounded-lg">Sign out</a>
                            </li>
                        </ul>
                    </div>


                    <x-nav-link href="#" class="bg-slate-900 hover:text-slate-600 font-bold px-6 py-2.5 text-center inline-flex items-center rounded-lg">Home</x-nav-link>
                    <x-nav-link href="#" class="bg-slate-900 hover:text-slate-600 font-bold px-6 py-2.5 text-center inline-flex items-center rounded-lg">Messages</x-nav-link>
                    <x-nav-link href="#" class="bg-slate-900 hover:text-slate-600 font-bold px-6 py-2.5 text-center inline-flex items-center rounded-lg">Wallet</x-nav-link>
                    <x-nav-link href="#" class="bg-slate-900 hover:text-slate-600 font-bold px-6 py-2.5 text-center inline-flex items-center rounded-lg">Account</x-nav-link>
                    <x-nav-link href="#" class="bg-slate-900 hover:text-slate-600 font-bold px-6 py-2.5 text-center inline-flex items-center rounded-lg">Forum</x-nav-link>
                </div>
            </div>
            <div class="flex items-center">
                <div class="py-2">
                    <label class="inline-flex items-center me-5 cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" checked>
                        <div class="relative w-11 h-6 bg-gray-200 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-slate-400 after:border-slate-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-800 peer-checked:bg-pink-600 dark:peer-checked:bg-pink-600"></div>
                    </label>
                </div>
                <div class="hidden md:ml-4 md:flex md:shrink-0 md:items-center">
                    <button type="button" class="relative rounded-full ml-4 p-1 hover:bg-slate-800 pr-2">
                        <span class="absolute -inset-1.5"></span>
                        <span class="sr-only">View notifications</span>
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </button>

                    <button type="button" class="relative rounded-full ml-4 p-1 hover:bg-slate-800 pr-2">
                        <span class="absolute -inset-1.5"></span>
                        <span class="sr-only">View cart</span>
                        <svg class="size-6 shrink-0 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile menu, show/hide based on menu state. -->
    <div class="md:hidden" id="mobile-menu">
        <div class="space-y-1 pt-2 pb-3">
            <!-- Current: "bg-indigo-50 border-indigo-500 text-indigo-700", Default: "border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700" -->
            <a href="#" class="block py-2 pr-4 pl-3 text-base font-medium sm:pr-6 sm:pl-5">Dashboard</a>
            <a href="#" class="block py-2 pr-4 pl-3 text-base font-medium hover:bg-slate-600 sm:pr-6 sm:pl-5">Categories</a>
            <a href="#" class="block py-2 pr-4 pl-3 text-base font-medium hover:bg-slate-600 sm:pr-6 sm:pl-5">Home</a>
            <a href="#" class="block py-2 pr-4 pl-3 text-base font-medium hover:bg-slate-600 sm:pr-6 sm:pl-5">Messages</a>
            <a href="#" class="block py-2 pr-4 pl-3 text-base font-medium hover:bg-slate-600 sm:pr-6 sm:pl-5">Wallet</a>
            <a href="#" class="block py-2 pr-4 pl-3 text-base font-medium hover:bg-slate-600 sm:pr-6 sm:pl-5">Account</a>
            <a href="#" class="block py-2 pr-4 pl-3 text-base font-medium hover:bg-slate-600 sm:pr-6 sm:pl-5">Forum</a>
        </div>
    </div>
</nav>

