<x-app>
    <x-slot name="title">Choose a company</x-slot>
    <section class="mx-auto w-full max-w-5xl px-4 py-10 sm:px-6" aria-labelledby="company-selection-heading">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="grid gap-2">
                <p class="text-sm font-semibold uppercase tracking-widest text-pink-400">Your workspaces</p>
                <h1 id="company-selection-heading" class="text-3xl font-bold text-white">Choose a company</h1>
                <p class="text-sm text-slate-400">Open one of the companies you can access.</p>
            </div>
            <a href="{{ route('company.create') }}" class="inline-flex justify-center rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white transition hover:bg-pink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-500">Create company</a>
        </div>
        @if ($companies->isEmpty())
            <div class="mt-8 rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
                <h2 class="text-xl font-semibold text-white">No companies yet</h2>
                <p class="mt-2 text-sm text-slate-400">Create your first company to begin using GoErion.</p>
                <a href="{{ route('company.create') }}" class="mt-6 inline-flex rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white hover:bg-pink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-500">Create your first company</a>
            </div>
        @else
            <ul class="mt-8 grid gap-4 md:grid-cols-2">
                @foreach ($companies as $company)
                    <li><a href="{{ route('home', $company) }}" class="flex h-full items-center justify-between rounded-2xl border border-slate-800 bg-slate-900/70 px-5 py-5 font-semibold text-slate-100 transition hover:border-pink-500 hover:bg-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-500"><span><span class="block text-lg">{{ $company->company_name }}</span><span class="mt-1 block text-sm font-normal text-slate-400">{{ strtoupper($company->currency) }}</span></span><span aria-hidden="true">&rarr;</span></a></li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $companies->links() }}</div>
        @endif
    </section>
</x-app>
