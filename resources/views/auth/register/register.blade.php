 <x-auth>
    <div class="flex justify-center">
        <x-slot name="title">
            Register
        </x-slot>
        <div class="w-full max-w-sm border border-slate-900 p-4 rounded-2xl sm:p-6 md:p-8">
            <form class="space-y-6" action="{{ route('register') }}" method="POST">
                @csrf
                <h5 class="text-xl font-bold font-sans">Register to {{ config('app.name') }}</h5>
                <div>
                    <label for="name" class="block mb-2 font-bold">{{ __('Name') }}</label>
                    <input type="text" name="name" id="name" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('name') border-red-500 @enderror block w-full p-2.5"/>
                    @error('name')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                 <div>
                    <label for="email" class="block mb-2 font-bold">{{ __('Email') }}</label>
                    <input type="email" name="email" id="email" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('email') border-red-500 @enderror block w-full p-2.5"/>
                    @error('email')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="password" class="block mb-2 font-bold">{{ __('Password') }}</label>
                    <input type="password" name="password" id="password" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('password') border-red-500 @enderror block w-full p-2.5"/>
                    @error('password')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="password" class="block mb-2 font-medium">{{__('Confirm Password')}}</label>
                    <input type="password" name="password_confirmation" id="password_confirm" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('password_confirm') border-red-500 @enderror block w-full p-2.5"/>
                    @error('password_confirm')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="w-full font-extrabold font-sans border border-pink-700 hover:bg-pink-800 focus:ring-4 focus:outline-none focus:ring-pink-300 rounded-lg text-pink-500 px-5 py-2.5 text-center">{{__('Register')}}</button>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-300">
                    Already registered? <a href="{{ route('login.create') }}" class="text-blue-700 hover:underline">{{__('Log In')}}</a>
                </div>
            </form>
        </div>
</x-auth>