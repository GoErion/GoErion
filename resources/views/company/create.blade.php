<x-auth>
    <div class="flex justify-center">
        <x-slot name="title">
            Create Company
        </x-slot>
        <div class="w-full max-w-2xl border border-slate-900 p-4 rounded-2xl sm:p-6 md:p-8">
            <form class="space-y-6" action="{{ route('company') }}" method="POST">
                @csrf
                <h5 class="text-xl font-bold font-sans">Create company for {{ config('app.name') }}</h5>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="company_name" class="block mb-2 font-bold">{{ __('Company Name') }}</label>
                        <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('company_name') border-red-500 @enderror block w-full p-2.5" required />
                        @error('company_name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="legal_name" class="block mb-2 font-bold">{{ __('Legal Name') }}</label>
                        <input type="text" name="legal_name" id="legal_name" value="{{ old('legal_name') }}" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('legal_name') border-red-500 @enderror block w-full p-2.5" />
                        @error('legal_name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="registration_number" class="block mb-2 font-bold">{{ __('Registration Number') }}</label>
                        <input type="text" name="registration_number" id="registration_number" value="{{ old('registration_number') }}" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('registration_number') border-red-500 @enderror block w-full p-2.5" />
                        @error('registration_number')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="tax_number" class="block mb-2 font-bold">{{ __('Tax Number') }}</label>
                        <input type="text" name="tax_number" id="tax_number" value="{{ old('tax_number') }}" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('tax_number') border-red-500 @enderror block w-full p-2.5" />
                        @error('tax_number')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block mb-2 font-bold">{{ __('Email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('email') border-red-500 @enderror block w-full p-2.5" />
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block mb-2 font-bold">{{ __('Phone') }}</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('phone') border-red-500 @enderror block w-full p-2.5" />
                        @error('phone')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block mb-2 font-bold">{{ __('Address') }}</label>
                        <textarea name="address" id="address" rows="3" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('address') border-red-500 @enderror block w-full p-2.5">{{ old('address') }}</textarea>
                        @error('address')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="w-full font-extrabold font-sans border border-pink-700 hover:bg-pink-800 focus:ring-4 focus:outline-none focus:ring-pink-300 rounded-lg text-pink-500 px-5 py-2.5 text-center">
                    {{ __('Create Company') }}
                </button>
            </form>
        </div>
    </div>
</x-auth>
