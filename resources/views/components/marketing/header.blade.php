<header x-data="{ open: false }" @keydown.escape.window="if (open) { open = false; $refs.toggle.focus() }" @click.outside="open = false" class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur-sm">
    <nav aria-label="Main navigation" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-20 items-center justify-between gap-4">
            <a href="{{ route('marketing') }}" class="flex items-center gap-2 rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-800">
                <img src="{{ asset(config('marketing.logo')) }}" alt="GoErion" width="1824" height="1367" class="h-16 w-24 object-contain" />
                <span aria-hidden="true" class="text-xl font-semibold tracking-tight">GoErion</span>
            </a>
            <div class="hidden items-center gap-3 md:flex"><x-marketing.navigation /></div>
            <button x-cloak x-ref="toggle" type="button" @click="open = !open" aria-controls="marketing-mobile-menu" aria-expanded="false" :aria-expanded="open.toString()" :aria-label="open ? 'Close menu' : 'Open menu'" class="inline-flex size-11 items-center justify-center rounded-md text-slate-800 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-800 md:hidden">
                <svg x-show="!open" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-6"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                <svg x-show="open" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-6"><path stroke-linecap="round" d="m6 6 12 12M6 18 18 6" /></svg>
            </button>
        </div>
        <div id="marketing-mobile-menu" x-cloak x-show="open" @click="if ($event.target.closest('a')) { open = false }" class="grid gap-3 border-t border-slate-200 py-4 md:hidden">
            <x-marketing.navigation />
        </div>
        <noscript><div class="grid gap-3 pb-4 md:hidden"><x-marketing.navigation /></div></noscript>
    </nav>
</header>
