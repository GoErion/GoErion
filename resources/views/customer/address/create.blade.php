<x-app>

    <x-slot name="title">
        Add Customer Address
    </x-slot>

    <div class="mx-auto max-w-3xl px-4">

        <div class="rounded-3xl border border-slate-800 bg-transparent p-8">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-white">
                    Add Address
                </h1>

                <p class="mt-2 text-slate-400">
                    {{ $customer->name }}
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('address.store', $customer) }}"
                class="space-y-6"
            >

                @csrf

                <div>
                    <label class="block text-sm text-slate-300">
                        Address Type
                    </label>

                    <select
                        name="type"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-transparent px-4 py-3 text-white"
                    >
                        <option value="billing">
                            Billing
                        </option>

                        <option value="shipping">
                            Shipping
                        </option>

                        <option value="other">
                            Other
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-slate-300">
                        Address Line 1
                    </label>

                    <input
                        type="text"
                        name="line_1"
                        value="{{ old('line_1') }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-transparent px-4 py-3 text-slate-400"
                    >
                </div>

                <div>
                    <label class="block text-sm text-slate-300">
                        Address Line 2
                    </label>

                    <input
                        type="text"
                        name="line_2"
                        value="{{ old('line_2') }}"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-transparent px-4 py-3"
                    >
                </div>

                <div class="grid gap-6 sm:grid-cols-2">

                    <div>
                        <label class="block text-sm text-slate-300">
                            City
                        </label>

                        <input
                            type="text"
                            name="city"
                            value="{{ old('city') }}"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-sm text-slate-300">
                            Postal Code
                        </label>

                        <input
                            type="text"
                            name="postal_code"
                            value="{{ old('postal_code') }}"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white"
                        >
                    </div>

                </div>

                <div>
                    <label class="block text-sm text-slate-300">
                        Country
                    </label>

                    <input
                        type="text"
                        name="country"
                        value="{{ old('country') }}"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white"
                    >
                </div>

                <label class="flex items-center gap-3 text-slate-300">

                    <input
                        type="checkbox"
                        name="is_default"
                        value="1"
                    >

                    Make this the default address

                </label>

                <div class="flex justify-end">

                    <button
                        type="submit"
                        class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white"
                    >
                        Save Address
                    </button>

                </div>

            </form>

        </div>

    </div>

</x-app>