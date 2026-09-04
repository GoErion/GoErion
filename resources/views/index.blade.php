<x-home :company="$company" :companies="$companies">
    <section class="grid gap-6 py-6" aria-labelledby="company-overview-heading">
        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-emerald-200">{{ session('success') }}</div>
        @endif
        <div class="flex flex-col gap-4 rounded-3xl border border-slate-800 bg-slate-950/80 p-6 sm:flex-row sm:items-start sm:justify-between sm:p-8">
            <div><p class="text-sm font-semibold uppercase tracking-widest text-pink-400">Company overview</p><h1 id="company-overview-heading" class="mt-2 text-3xl font-bold text-white">{{ $company->company_name }}</h1><p class="mt-2 text-sm text-slate-400">Your company workspace and profile summary.</p></div>
            @can('update', $company)
                <a href="{{ route('company.edit', $company) }}" class="inline-flex justify-center rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white transition hover:bg-pink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-500">Edit company profile</a>
            @endcan
        </div>
        <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach (['Legal name' => $company->legal_name, 'Registration number' => $company->registration_number, 'Tax number' => $company->tax_number, 'Email' => $company->email, 'Phone' => $company->phone, 'Address' => $company->address] as $label => $value)
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5"><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</dt><dd class="mt-2 break-words text-slate-100">{{ $value ?: 'Not provided' }}</dd></div>
            @endforeach
        </dl>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5"><span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Currency</span><p class="mt-2 text-lg font-semibold text-white">{{ strtoupper($company->currency) }}</p></div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5"><span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status</span><p class="mt-2"><span class="inline-flex rounded-full bg-emerald-500/15 px-3 py-1 text-sm font-semibold text-emerald-300">{{ ucfirst($company->status) }}</span></p></div>
        </div>
    </section>
</x-home>
