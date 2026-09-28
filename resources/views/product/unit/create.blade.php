<x-home :company="$company">

    <x-slot name="title">
        Create Unit
    </x-slot>

    <div class="mx-auto max-w-xl px-4">

        <div class="rounded-3xl border border-slate-800 bg-slate-950 p-8">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-white">
                    Create Unit
                </h1>

                <p class="mt-2 text-slate-400">
                    {{ $company->company_name }}
                </p>
            </div>

            <form method="POST" action="{{ route('unit.store', $company) }}" class="space-y-6">

                @csrf

                <div>
                    <label for="name" class="block text-sm text-slate-300">
                        Unit Name
                    </label>

                    <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Kilogram" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">

                    @error('name')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="symbol" class="block text-sm text-slate-300">
                        Symbol
                    </label>

                    <input id="symbol" type="text" name="symbol" value="{{ old('symbol') }}" placeholder="kg" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">

                    @error('symbol')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <button type="submit" class="w-full rounded-xl bg-emerald-700 px-4 py-3 font-semibold text-white">
                    Create Unit
                </button>

            </form>

        </div>

    </div>

</x-home>
