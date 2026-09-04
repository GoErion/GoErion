<x-auth>
    <x-slot name="title">Verify your email</x-slot>

    <section class="w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-950/90 p-6 text-center shadow-2xl shadow-black/30 sm:p-8" aria-labelledby="verify-email-heading">
        <div class="grid gap-3">
            <h1 id="verify-email-heading" class="text-2xl font-bold text-white">Verify your email address</h1>
            <p class="text-sm leading-6 text-slate-400">We sent a verification link to your email address. Verify it before entering company onboarding or the ERP.</p>
        </div>

        <div class="mt-6 grid gap-4">
            <x-auth.status />

            <form action="{{ route('verification.send') }}" method="POST">
                @csrf
                <button type="submit" class="w-full rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white transition hover:bg-pink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">Resend verification email</button>
            </form>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full rounded-xl border border-slate-700 px-5 py-3 font-semibold text-slate-200 transition hover:bg-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">Sign out</button>
            </form>
        </div>
    </section>
</x-auth>
