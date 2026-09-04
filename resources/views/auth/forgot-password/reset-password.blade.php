 <x-auth>
    <div class="flex justify-center">
        <x-slot name="title">
            Reset Password
        </x-slot>
        <div class="w-full max-w-sm border border-slate-900 p-4 rounded-2xl sm:p-6 md:p-8">
            <form class="space-y-6" action="{{ route('password.store') }}" method="POST">
                @csrf
                <h5 class="text-xl font-bold font-sans">Register to {{ config('app.name') }}</h5>
                <!-- Password Reset Token -->
                <input type="hidden" name="token" value="{{ $request->token }}">
                 <div>
                    <label for="email" class="block mb-2 font-bold">{{ __('Email') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $request->email) }}" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('email') border-red-500 @enderror block w-full p-2.5"/>
                    @error('email')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="password" class="block mb-2 font-bold">{{ __('New Password') }}</label>
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
                <button type="submit" class="w-full font-extrabold font-sans border border-pink-700 hover:bg-pink-800 focus:ring-4 focus:outline-none focus:ring-pink-300 rounded-lg text-pink-500 px-5 py-2.5 text-center">{{ __('Reset Password') }}</button>
            </form>
        </div>
</x-auth>