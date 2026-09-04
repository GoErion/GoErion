<x-auth>
    <x-slot name="title">Choose a new password</x-slot>

    <section class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-950/90 p-6 shadow-2xl shadow-black/30 sm:p-8" aria-labelledby="reset-password-heading">
        <div class="grid gap-2 text-center">
            <h1 id="reset-password-heading" class="text-2xl font-bold text-white">Choose a new password</h1>
            <p class="text-sm text-slate-400">Use a strong password you do not use anywhere else.</p>
        </div>

        <form class="mt-8 grid gap-5" action="{{ route('password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-auth.input name="email" type="email" label="Email address" autocomplete="email" :value="old('email', $email)" required autofocus />
            <x-auth.input name="password" type="password" label="New password" autocomplete="new-password" required />
            <x-auth.input name="password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" required />

            <button type="submit" class="rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white transition hover:bg-pink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">Reset password</button>
        </form>
    </section>
</x-auth>
