<x-app>
    <div class="flex justify-center">
        <x-slot name="title">
            Create Branch
        </x-slot>
        <div class="w-full max-w-2xl border border-slate-900 p-4 rounded-2xl sm:p-6 md:p-8">
            <form class="space-y-6" action="{{ route('branch.store', $company) }}" method="POST">
                @csrf
                <h5 class="text-xl font-bold font-sans">Create branch for {{ $company->company_name }}</h5>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="branch_name" class="block mb-2 font-bold">{{ __('Branch Name') }}</label>
                        <input type="text" name="branch_name" id="branch_name" value="{{ old('branch_name') }}"
                            class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('branch_name') border-red-500 @enderror block w-full p-2.5"
                            required />
                        @error('branch_name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="code" class="block mb-2 font-bold">{{ __('Code') }}</label>
                        <input type="text" name="code" id="code" value="{{ old('code') }}"
                            class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('code') border-red-500 @enderror block w-full p-2.5"
                            required />
                        @error('code')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block mb-2 font-bold">{{ __('Email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                            class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('email') border-red-500 @enderror block w-full p-2.5" />
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block mb-2 font-bold">{{ __('Phone') }}</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                            class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('phone') border-red-500 @enderror block w-full p-2.5" />
                        @error('phone')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block mb-2 font-bold">{{ __('Address') }}</label>
                        <textarea name="address" id="address" rows="3"
                            class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('address') border-red-500 @enderror block w-full p-2.5">{{ old('address') }}</textarea>
                        @error('address')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="md:col-span-2 flex gap-4">
                        <button type="submit"
                            class="px-4 py-2 bg-pink-500 text-white rounded-lg hover:bg-pink-600 transition">{{ __('Create Branch') }}</button>
                        <a href="{{ route('home',$company) }}"
                            class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">{{ __('Cancel') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app>
