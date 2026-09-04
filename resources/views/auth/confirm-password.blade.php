<x-auth>
    <x-slot name="title">Confirm your password</x-slot>

    <section class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-950/90 p-6 shadow-2xl shadow-black/30 sm:p-8" aria-labelledby="confirm-password-heading">
        <div class="grid gap-2 text-center">
            <h1 id="confirm-password-heading" class="text-2xl font-bold text-white">Confirm your password</h1>
            <p class="text-sm text-slate-400">This is a sensitive area. Confirm your password before continuing.</p>
        </div>

        <form class="mt-8 grid gap-5" action="{{ route('password.confirm.store') }}" method="POST">
            @csrf
            <x-auth.input name="password" type="password" label="Current password" autocomplete="current-password" required autofocus />

            <button type="submit" class="rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white transition hover:bg-pink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">Confirm password</button>
        </form>
    </section>
</x-auth>
