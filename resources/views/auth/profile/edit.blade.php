<x-app>
    <x-slot name="title">Profile</x-slot>

    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-3xl rounded-3xl border border-slate-800 bg-slate-950/90 p-8 shadow-xl">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white">Edit Profile</h1>
                <p class="mt-2 text-slate-400">Update your account details, change your password, or delete your account.
                </p>
            </div>

            @auth
                <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="name" class="block mb-2 font-bold text-slate-200">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}"
                            class="w-full rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 text-slate-100 outline-none transition focus:border-pink-500 focus:ring-2 focus:ring-pink-500/20 @error('name') border-red-500 @enderror" />
                        @error('name')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block mb-2 font-bold text-slate-200">{{ __('Email') }}</label>
                        <input id="email" name="email" type="email"
                            value="{{ old('email', auth()->user()->email) }}"
                            class="w-full rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 text-slate-100 outline-none transition focus:border-pink-500 focus:ring-2 focus:ring-pink-500/20 @error('email') border-red-500 @enderror" />
                        @error('email')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <div>
                            <label for="password"
                                class="block mb-2 font-bold text-slate-200">{{ __('New Password') }}</label>
                            <input id="password" name="password" type="password"
                                class="w-full rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 text-slate-100 outline-none transition focus:border-pink-500 focus:ring-2 focus:ring-pink-500/20 @error('password') border-red-500 @enderror" />
                            @error('password')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation"
                                class="block mb-2 font-bold text-slate-200">{{ __('Confirm Password') }}</label>
                            <input id="password_confirmation" name="password_confirmation" type="password"
                                class="w-full rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 text-slate-100 outline-none transition focus:border-pink-500 focus:ring-2 focus:ring-pink-500/20" />
                        </div>
                    </div>

                    <div>
                        <label for="current_password"
                            class="block mb-2 font-bold text-slate-200">{{ __('Current Password') }}</label>
                        <input id="current_password" name="current_password" type="password"
                            class="w-full rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 text-slate-100 outline-none transition focus:border-pink-500 focus:ring-2 focus:ring-pink-500/20 @error('current_password') border-red-500 @enderror" />
                        @error('current_password')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-sm text-slate-500">Only required when changing password.</p>
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <button type="submit"
                            class="w-full rounded-2xl bg-pink-600 px-5 py-3 font-semibold text-white hover:bg-pink-500 sm:w-auto">
                            Save changes
                        </button>
                        <a href="{{ route('home') }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-800 bg-slate-900 px-5 py-3 text-slate-200 hover:bg-slate-900/90">
                            Back to dashboard
                        </a>
                    </div>
                </form>

                <div class="mt-10 rounded-3xl border border-red-700/30 bg-slate-900/80 p-6">
                    <h2 class="text-xl font-semibold text-red-400">Delete Account</h2>
                    <p class="mt-2 text-slate-400">This action is permanent. Enter your password to remove your account.</p>

                    <form action="{{ route('profile.destroy') }}" method="POST" class="mt-6 space-y-4">
                        @csrf
                        @method('DELETE')

                        <div>
                            <label for="delete_password"
                                class="block mb-2 font-bold text-slate-200">{{ __('Password') }}</label>
                            <input id="delete_password" name="password" type="password"
                                class="w-full rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 text-slate-100 outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-500/20 @error('password') border-red-500 @enderror" />
                            @error('password')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                            class="w-full rounded-2xl bg-red-600 px-5 py-3 font-semibold text-white hover:bg-red-500">
                            Delete account
                        </button>
                    </form>
                </div>
            @else
                <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 text-slate-300">
                    <p>Please log in to update your profile.</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('login.create') }}"
                            class="rounded-2xl bg-emerald-700 px-4 py-2 text-white hover:bg-emerald-600">Login</a>
                        <a href="{{ route('register.create') }}"
                            class="rounded-2xl border border-slate-800 px-4 py-2 text-slate-200 hover:bg-slate-900">Register</a>
                    </div>
                </div>
            @endauth
        </div>
    </div>
</x-app>
