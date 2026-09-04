<x-auth>
    <x-slot name="title">Forgot your password</x-slot>

    <section class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-950/90 p-6 shadow-2xl shadow-black/30 sm:p-8" aria-labelledby="forgot-password-heading">
        <div class="grid gap-2 text-center">
            <h1 id="forgot-password-heading" class="text-2xl font-bold text-white">Reset your password</h1>
            <p class="text-sm text-slate-400">Enter your email address and we will send reset instructions if an account exists.</p>
        </div>

        <form class="mt-8 grid gap-5" action="{{ route('password.email') }}" method="POST">
            @csrf
            <x-auth.status />
            <x-auth.input name="email" type="email" label="Email address" autocomplete="email" :value="old('email')" required autofocus />

            <button type="submit" class="rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white transition hover:bg-pink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">Email reset instructions</button>

            <a href="{{ route('login') }}" class="text-center text-sm font-semibold text-pink-300 hover:text-pink-200 focus-visible:outline-none focus-visible:underline">Return to sign in</a>
        </form>
    </section>
</x-auth>
