<x-auth>
    <x-slot name="title">Sign in</x-slot>

    <section class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-950/90 p-6 shadow-2xl shadow-black/30 sm:p-8" aria-labelledby="login-heading">
        <div class="grid gap-2 text-center">
            <h1 id="login-heading" class="text-2xl font-bold text-white">Welcome back</h1>
            <p class="text-sm text-slate-400">Sign in to continue to your GoErion workspace.</p>
        </div>

        <form class="mt-8 grid gap-5" action="{{ route('login.store') }}" method="POST">
            @csrf
            <x-auth.status />
            <x-auth.input name="email" type="email" label="Email address" autocomplete="email" :value="old('email')" required autofocus />
            <x-auth.input name="password" type="password" label="Password" autocomplete="current-password" required />

            <label class="flex cursor-pointer items-center gap-3 text-sm font-medium text-slate-300">
                <input name="remember" type="checkbox" value="1" @checked(old('remember')) class="size-4 rounded border-slate-600 bg-slate-900 text-pink-600 focus-visible:ring-2 focus-visible:ring-pink-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">
                Remember me
            </label>

            <button type="submit" class="rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white transition hover:bg-pink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">Sign in</button>

            <div class="flex flex-col gap-3 text-center text-sm sm:flex-row sm:justify-between">
                <a href="{{ route('register') }}" class="text-pink-300 hover:text-pink-200 focus-visible:outline-none focus-visible:underline">Create an account</a>
                <a href="{{ route('password.request') }}" class="text-pink-300 hover:text-pink-200 focus-visible:outline-none focus-visible:underline">Forgot your password?</a>
            </div>
        </form>
    </section>
</x-auth>
