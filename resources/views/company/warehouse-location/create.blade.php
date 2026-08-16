<x-app>
    <div class="flex justify-center">
        <x-slot name="title">
            Create Warehouse Location
        </x-slot>
        <div class="w-full max-w-2xl border border-slate-900 p-4 rounded-2xl sm:p-6 md:p-8">
            <form class="space-y-6" action="{{ route('location', $warehouse) }}" method="POST">
                @csrf
                <h5 class="text-xl font-bold font-sans">Create location for {{ $warehouse->warehouse_name }}</h5>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="name" class="block mb-2 font-bold">{{ __('Location Name') }}</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                            class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('name') border-red-500 @enderror block w-full p-2.5"
                            placeholder="{{ __('Shelf A1') }}" required />
                        @error('name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="code" class="block mb-2 font-bold">{{ __('Code') }}</label>
                        <input type="text" name="code" id="code" value="{{ old('code') }}"
                            class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('code') border-red-500 @enderror block w-full p-2.5 uppercase"
                            placeholder="A1" required />
                        @error('code')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                            @checked(old('is_active', true)) class="rounded border-slate-900" />
                        <label for="is_active" class="ml-2 font-bold">{{ __('Active') }}</label>
                    </div>

                    <div class="md:col-span-2 flex gap-4">
                        <button type="submit"
                            class="px-4 py-2 bg-pink-500 text-white rounded-lg hover:bg-pink-600 transition">{{ __('Create Location') }}</button>
                        <a href="{{ url()->previous() }}"
                            class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">{{ __('Cancel') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app>
