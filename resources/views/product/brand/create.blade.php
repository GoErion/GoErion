<x-app>

    <x-slot name="title">
        Create Brand
    </x-slot>

    <div class="mx-auto max-w-xl px-4">

        <div class="rounded-3xl border border-slate-800 bg-slate-950 p-8">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-white">
                    Create Brand
                </h1>

                <p class="mt-2 text-slate-400">
                    {{ $company->company_name }}
                </p>
            </div>

            <form method="POST" action="{{ route('brand.store', $company) }}" class="space-y-6">

                @csrf

                <div>
                    <label class="block text-sm text-slate-300">
                        Brand Name
                    </label>

                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">

                    @error('name')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm text-slate-300">
                        Brand Code
                    </label>

                    <input type="text" name="code" value="{{ old('code') }}" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">

                    @error('code')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full rounded-xl bg-emerald-700 px-4 py-3 font-semibold text-white hover:bg-emerald-600">
                    Create Brand
                </button>

            </form>

        </div>

    </div>

</x-app>
