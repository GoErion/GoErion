<x-app>
    <x-slot name="title">Create company</x-slot>
    <section class="mx-auto w-full max-w-4xl px-4 py-10 sm:px-6" aria-labelledby="company-create-heading">
        <x-access-feedback />
        <div class="rounded-3xl border border-slate-800 bg-slate-950/90 p-6 shadow-2xl shadow-black/30 sm:p-8">
            <div class="grid gap-2">
                <p class="text-sm font-semibold uppercase tracking-widest text-pink-400">Company onboarding</p>
                <h1 id="company-create-heading" class="text-3xl font-bold text-white">Create your company</h1>
                <p class="max-w-2xl text-sm text-slate-400">Start with a company name. Registration and contact details are optional and can be updated later.</p>
            </div>
            <form class="mt-8 grid gap-6" action="{{ route('company.store') }}" method="POST">
                @csrf
                @include('company.partials.form')
                <div class="flex flex-col-reverse gap-3 border-t border-slate-800 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('companies.select') }}" class="inline-flex justify-center rounded-xl border border-slate-700 px-5 py-3 font-semibold text-slate-200 transition hover:border-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-500">Cancel</a>
                    <button type="submit" class="inline-flex justify-center rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white transition hover:bg-pink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">Create company</button>
                </div>
            </form>
        </div>
    </section>
</x-app>
