<x-home :company="$company" :companies="$companies">
    <section class="mx-auto w-full max-w-4xl py-6" aria-labelledby="company-edit-heading">
        <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-400">
            <a href="{{ route('home', $company) }}" class="hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-500">{{ $company->company_name }}</a>
            <span aria-hidden="true" class="px-2">/</span><span>Company profile</span>
        </nav>
        <div class="rounded-3xl border border-slate-800 bg-slate-950/90 p-6 sm:p-8">
            <h1 id="company-edit-heading" class="text-3xl font-bold text-white">Edit company profile</h1>
            <p class="mt-2 text-sm text-slate-400">Currency and company status are read-only during this phase.</p>
            <form class="mt-8 grid gap-6" action="{{ route('company.update', $company) }}" method="POST">
                @csrf
                @method('PATCH')
                @include('company.partials.form', ['company' => $company])
                <div class="grid gap-4 rounded-2xl border border-slate-800 bg-slate-900/60 p-4 sm:grid-cols-2">
                    <div><span class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Currency</span><span class="mt-1 block text-white">{{ strtoupper($company->currency) }}</span></div>
                    <div><span class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Status</span><span class="mt-1 inline-flex rounded-full bg-emerald-500/15 px-3 py-1 text-sm font-semibold text-emerald-300">{{ ucfirst($company->status) }}</span></div>
                </div>
                <div class="flex flex-col-reverse gap-3 border-t border-slate-800 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('home', $company) }}" class="inline-flex justify-center rounded-xl border border-slate-700 px-5 py-3 font-semibold text-slate-200 transition hover:border-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-500">Cancel</a>
                    <button type="submit" class="inline-flex justify-center rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white transition hover:bg-pink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-500">Save profile</button>
                </div>
            </form>
        </div>
    </section>
</x-home>
