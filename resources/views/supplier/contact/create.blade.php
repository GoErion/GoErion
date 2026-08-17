<x-app>

    <x-slot name="title">
        Add Supplier Contact
    </x-slot>

    <div class="mx-auto max-w-3xl px-4">

        <div class="rounded-3xl border border-slate-800 bg-slate-950 p-8">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-white">
                    Add Supplier Contact
                </h1>

                <p class="mt-2 text-slate-400">
                    {{ $supplier->name }}
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('supplier.contact.store', $supplier) }}"
                class="space-y-6"
            >

                @csrf

                <div>
                    <label class="block text-sm text-slate-300">
                        Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white"
                    >

                    @error('name')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm text-slate-300">
                        Position
                    </label>

                    <input
                        type="text"
                        name="position"
                        value="{{ old('position') }}"
                        placeholder="e.g. Sales Manager"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white"
                    >
                </div>

                <div class="grid gap-6 sm:grid-cols-2">

                    <div>
                        <label class="block text-sm text-slate-300">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-sm text-slate-300">
                            Phone
                        </label>

                        <input
                            type="text"
                            name="phone"
                            value="{{ old('phone') }}"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white"
                        >
                    </div>

                </div>

                <div class="flex justify-end gap-3">

                    <a
                        href="{{ route('supplier.show', $supplier) }}"
                        class="rounded-xl border border-slate-700 px-5 py-3 text-slate-300"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white"
                    >
                        Save Contact
                    </button>

                </div>

            </form>

        </div>

    </div>

</x-app>