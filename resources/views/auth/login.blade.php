<x-geust>
    <div>
        <div class="flex justify-center">
            <x-slot name="title">
                Login
            </x-slot>
            <div class="w-full max-w-sm border border-slate-900 p-4 rounded-lg sm:p-6 md:p-8">
                <form class="space-y-6" action="{{ route('login.store') }} " method="POST">
                    @csrf
                    <h5 class="text-xl font-bold font-sans">Login to {{ config('app.name') }}</h5>
                    <div>
                        <label for="username" class="block mb-2 font-bold">Username</label>
                        <input type="text" name="username" id="username" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('username') border-red-500 @enderror block w-full p-2.5 dark:placeholder-slate-400" placeholder="username"/>
                        @error('username')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="password" class="block mb-2 font-bold">password</label>
                        <input type="password" name="password" id="password" placeholder="************" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('password') border-red-500 @enderror block w-full p-2.5 dark:placeholder-slate-400"/>
                        @error('password')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <button type="submit" class="w-full font-extrabold font-sans border border-pink-700 hover:bg-pink-800 focus:ring-4 focus:outline-none focus:ring-pink-300 rounded-lg text-pink-500 px-5 py-2.5 text-center">{{__('Login')}}</button>
                    <div class="flex items-center justify-between font-bold text-gray-500 dark:text-gray-300 gap-3">
                        <a href="{{ route('register') }}" class="border border-slate-700 hover:bg-slate-800 focus:ring-4 focus:outline-none focus:ring-slate-300 rounded-lg text-slate-500 px-5 py-2.5 text-center">{{__('Register')}}</a>
                        <a href="{{-- route('reset') --}}" class="border border-slate-700 hover:bg-slate-800 focus:ring-4 focus:outline-none focus:ring-slate-300 rounded-lg text-slate-500 px-5 py-2.5 text-center">{{__('Reset?')}}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-geust>
