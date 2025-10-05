<x-auth>
    <div class="flex justify-center mb-6">
        <x-slot name="title">
            Mnemonic
        </x-slot>

        <div class="w-full max-w-md border border-slate-900 p-6 rounded-lg shadow-lg">
            <!-- Label -->
            <label for="mnemonic" class="block font-bold mb-2">
                {{ __('Mnemonic') }}
            </label>

            <!-- Info text -->
            <p class="mb-4 leading-relaxed">
                This is your mnemonic key.  It consists of {{ config('site.mnemonic_length') }} words.
                Please write them down. This is the only time they will be shown to you, and without them
                you cannot recover your account in case you lose your password.
            </p>

            <!-- Textarea -->
            <div class="mb-6">
                <label for="mnemonic" class="block font-bold mb-1">{{__('Mnemonic')}}</label>
                <textarea
                    id="mnemonic"
                    name="mnemonic"
                    readonly
                    class="w-full h-40 p-3 bg-transparent rounded-lg border border-slate-900 text-base"
                >{{ $mnemonic }}</textarea>
            </div>

            <!-- Button -->
            <div class="flex justify-end">
                <x-nav-link
                    href="{{ route('login') }}"
                    class="inline-block bg-yellow-500 hover:bg-yellow-600 text-slate-900 font-semibold px-5 py-2 rounded-lg transition-all duration-150"
                >
                    Proceed to LogIn
                </x-nav-link>
            </div>
        </div>
    </div>

</x-auth>
