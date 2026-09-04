<x-auth>
    <x-slot name="title">Create your account</x-slot>

    <section class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-950/90 p-6 shadow-2xl shadow-black/30 sm:p-8" aria-labelledby="registration-heading">
        <div class="grid gap-2 text-center">
            <h1 id="registration-heading" class="text-2xl font-bold text-white">Create your GoErion account</h1>
            <p class="text-sm text-slate-400">Start securely, then verify your email before creating a company.</p>
        </div>

        <form class="mt-8 grid gap-5" action="{{ route('register.store') }}" method="POST">
            @csrf
            <x-auth.input name="name" label="Full name" autocomplete="name" :value="old('name')" required autofocus />
            <x-auth.input name="email" type="email" label="Email address" autocomplete="email" :value="old('email')" required />
            <x-auth.input name="password" type="password" label="Password" autocomplete="new-password" required />
            <x-auth.input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" required />

            <button type="submit" class="rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white transition hover:bg-pink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">Create account</button>

            <p class="text-center text-sm text-slate-400">
                Already registered?
                <a href="{{ route('login') }}" class="font-semibold text-pink-300 hover:text-pink-200 focus-visible:outline-none focus-visible:underline">Sign in</a>
            </p>
        </form>
    </section>
</x-auth>
