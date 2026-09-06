<section aria-labelledby="hero-heading" class="bg-linear-to-br from-white via-slate-50 to-amber-50/60">
    <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-2 lg:gap-10 lg:px-8 lg:py-24">
        <div class="min-w-0">
            <p class="mb-5 text-sm font-semibold tracking-wide text-amber-900">Wholesale and hardware operations, connected</p>
            <h1 id="hero-heading" class="max-w-xl text-4xl leading-tight font-semibold tracking-tight text-slate-950 sm:text-5xl lg:text-5xl xl:text-6xl">Run your wholesale and hardware business with clarity.</h1>
            <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">GoErion brings products, inventory, suppliers, customers, purchasing and sales into one company-secure workspace built for growing wholesale and hardware businesses.</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                @guest
                    <x-marketing.button :href="route('register')">Get started <span aria-hidden="true">→</span></x-marketing.button>
                @else
                    <x-marketing.button :href="route('companies.select')">Open GoErion <span aria-hidden="true">→</span></x-marketing.button>
                @endguest
                <x-marketing.button href="#product-preview" :secondary="true">Explore the product</x-marketing.button>
            </div>
            <ul class="mt-8 grid gap-3 border-t border-slate-200 pt-6 text-sm leading-6 text-slate-600">
                @foreach (['Multi-company and multi-branch organization', 'Secure role-based company access', 'Structured wholesale and hardware workflows'] as $capability)
                    <li class="flex gap-3"><svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="mt-1 size-4 shrink-0 text-amber-900"><path stroke-linecap="round" stroke-linejoin="round" d="m4 10 4 4 8-8" /></svg>{{ $capability }}</li>
                @endforeach
            </ul>
        </div>
        <x-marketing.product-preview />
    </div>
</section>
