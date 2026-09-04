<x-app>
    <x-slot name="title">Choose a company</x-slot>

    <section class="mx-auto flex min-h-screen w-full max-w-3xl items-center px-4 py-10" aria-labelledby="company-selection-heading">
        <div class="w-full rounded-3xl border border-slate-800 bg-slate-950/90 p-6 shadow-2xl shadow-black/30 sm:p-8">
            <div class="grid gap-2">
                <h1 id="company-selection-heading" class="text-2xl font-bold text-white">Choose a company</h1>
                <p class="text-sm text-slate-400">Select the company workspace you want to open.</p>
            </div>

            <ul class="mt-8 grid gap-3">
                @foreach ($companies as $company)
                    <li>
                        <a href="{{ route('home', $company) }}" class="flex items-center justify-between rounded-2xl border border-slate-800 bg-slate-900/70 px-5 py-4 font-semibold text-slate-100 transition hover:border-pink-500 hover:bg-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-500">
                            <span>{{ $company->company_name }}</span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
</x-app>
