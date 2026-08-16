<x-app>
    <x-slot name="title">
        Edit Settings
    </x-slot>

    <div class="flex justify-center">
        <div class="w-full max-w-2xl border border-slate-900 p-4 rounded-2xl sm:p-6 md:p-8">

            <form
                class="space-y-6"
                action="{{ route('settings', $company) }}"
                method="POST"
            >
                @csrf
                @method('PUT')

                <h5 class="text-xl font-bold font-sans">
                    Settings for {{ $company->name }}
                </h5>

                <div class="grid gap-4 md:grid-cols-2">

                    {{-- Currency --}}
                    <div>
                        <label for="currency" class="block mb-2 font-bold">
                            {{ __('Currency') }}
                        </label>

                        <input
                            type="text"
                            name="currency"
                            id="currency"
                            value="{{ old('currency', $settings->get('currency', '')) }}"
                            class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('currency') border-red-500 @enderror block w-full p-2.5"
                            placeholder="USD"
                            maxlength="10"
                        />

                        @error('currency')
                            <span class="text-red-500 text-sm">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    {{-- Timezone --}}
                    <div>
                        <label for="timezone" class="block mb-2 font-bold">
                            {{ __('Timezone') }}
                        </label>

                        <input
                            type="text"
                            name="timezone"
                            id="timezone"
                            value="{{ old('timezone', $settings->get('timezone', '')) }}"
                            class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('timezone') border-red-500 @enderror block w-full p-2.5"
                            placeholder="UTC"
                            maxlength="100"
                        />

                        @error('timezone')
                            <span class="text-red-500 text-sm">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    {{-- Invoice Prefix --}}
                    <div>
                        <label for="invoice_prefix" class="block mb-2 font-bold">
                            {{ __('Invoice Prefix') }}
                        </label>

                        <input
                            type="text"
                            name="invoice_prefix"
                            id="invoice_prefix"
                            value="{{ old('invoice_prefix', $settings->get('invoice_prefix', '')) }}"
                            class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('invoice_prefix') border-red-500 @enderror block w-full p-2.5"
                            placeholder="INV"
                            maxlength="20"
                        />

                        @error('invoice_prefix')
                            <span class="text-red-500 text-sm">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    {{-- Invoice Due Days --}}
                    <div>
                        <label for="invoice_due_days" class="block mb-2 font-bold">
                            {{ __('Invoice Due Days') }}
                        </label>

                        <input
                            type="number"
                            name="invoice_due_days"
                            id="invoice_due_days"
                            value="{{ old('invoice_due_days', $settings->get('invoice_due_days', '')) }}"
                            class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('invoice_due_days') border-red-500 @enderror block w-full p-2.5"
                            placeholder="30"
                            min="0"
                        />

                        @error('invoice_due_days')
                            <span class="text-red-500 text-sm">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="md:col-span-2 flex gap-4">
                        <button
                            type="submit"
                            class="px-4 py-2 bg-pink-500 text-white rounded-lg hover:bg-pink-600 transition"
                        >
                            {{ __('Update Settings') }}
                        </button>

                        <a
                            href="{{ url()->previous() }}"
                            class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition"
                        >
                            {{ __('Cancel') }}
                        </a>
                    </div>

                </div>
            </form>

        </div>
    </div>
</x-app>