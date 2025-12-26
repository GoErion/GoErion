<x-geust>
    <x-slot name="title">
        Mnemonic
    </x-slot>
    <div>
        <div class="flex justify-center">
                <h2>Mnemonic</h2>
                <div class="mt-4">
                    <p class="leading-relaxed">
                        This is your mnemonik key. It consists out of {{config('marketplace.mnemonic_length')}} words.
                        Please write
                        them down. This is the only time they will be shown to you, and without them you cannot recover
                        your account
                        in case you lose password.
                    </p>
                </div>
            <div class="border border-slate-400 bg-transparent fl rounded-lg">
                <div class="mb-4">
                    <textarea class="items-center" name="" id="" cols="30" rows="6" readonly>
                        {{ $mnemonic }}
                    </textarea>
                </div>
            </div>
            <div class="text-center">
                <a href="{{ route('login') }}" class="bg-teal-700 rounded-lg py-2 px-4">
                    Proceed to Login
                </a>
            </div>
        </div>
    </div>
</x-geust>
