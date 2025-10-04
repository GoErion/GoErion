<x-form>
    <div class="flex">
        <x-button type="button" id="dropdown-button" data-dropdown-toggle="dropdown" class="shrink-0 z-10 inline-flex items-center py-2.5 px-4 text-center border border-slate-600 rounded-s-lg hover:bg-slate-600">
            All <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
            </svg>
        </x-button>
        <div id="dropdown" class="z-10 hidden bg-slate-900 divide-y divide-slate-600 rounded-lg w-44">
            <ul class="py-2 text-bold" aria-labelledby="dropdown-button">
                <li>
                    <x-nav-link href="" class="inline-flex w-full px-4 py-2 dark:hover:bg-slate-600 rounded-lg">Mockups</x-nav-link>
                </li>
                <li>
                    <x-nav-link href="" class="inline-flex w-full px-4 py-2 dark:hover:bg-slate-600  rounded-lg">Templates</x-nav-link>
                </li>
                <li>
                    <x-nav-link href="" class="inline-flex w-full px-4 py-2 dark:hover:bg-slate-600  rounded-lg">Design</x-nav-link>
                </li>
                <li>
                    <x-nav-link href="" class="inline-flex w-full px-4 py-2 dark:hover:bg-slate-600  rounded-lg">Logos</x-nav-link>
                </li>
            </ul>
        </div>
        <div class="relative">
            <input type="text" id="search-dropdown" class="hidden lg:block p-2.5 z-20 w-96 bg-transparent rounded-e-lg border border-slate-600 border-s-2" placeholder="Search"/>
            <x-button class="absolute top-0 end-0 p-3 text-sm font-medium h-full rounded-e-lg border border-slate-600 hover:bg-slate-600">
                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                </svg>
                <span class="sr-only">Search</span>
            </x-button>
        </div>
    </div>
</x-form>
