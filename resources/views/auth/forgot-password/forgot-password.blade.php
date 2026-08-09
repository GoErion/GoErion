 <x-auth>
    <div class="flex justify-center">
        <x-slot name="title">
            Forgot Password
        </x-slot>
        <div class="w-full max-w-sm border border-slate-900 p-4 rounded-2xl sm:p-6 md:p-8">
            <form class="space-y-6" action="{{ route('password.email') }}" method="POST">
                @csrf
                <h5 class="text-xl font-bold font-sans">Register to {{ config('app.name') }}</h5>
                @session('status')
                    <div class="p-2 text-sm border border-green-300 rounded-lg bg-transparent text-green-600">
                        {{ $value }}
                    </div>
                @endsession
                <div class="mb-4 text-sm text-slate-600">
                    {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
                </div>
                <div>
                 <div>
                    <label for="email" class="block mb-2 font-bold">{{ __('Email') }}</label>
                    <input type="email" name="email" id="email" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('email') border-red-500 @enderror block w-full p-2.5 mb-4"/>
                    @error('email')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="w-full font-extrabold font-sans border border-pink-700 hover:bg-pink-800 focus:ring-4 focus:outline-none focus:ring-pink-300 rounded-lg text-pink-500 px-5 py-2.5 text-center">{{ __('Email Password Reset Link') }}</button>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-300 mt-4">
                    Already registered? <a href="{{ route('login.create') }}" class="text-blue-700 hover:underline">{{__('Log In')}}</a>
                </div>
            </form>
        </div>
</x-auth>