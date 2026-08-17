<x-app>

    <x-slot name="title">
        Create Customer
    </x-slot>

    <div class="mx-auto max-w-3xl px-4">

        <div class="rounded-3xl border border-slate-800 bg-transparent p-8">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-white">
                    Create Customer
                </h1>

                <p class="mt-2 text-slate-400">
                    {{ $company->company_name }}
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('customer.store', $company) }}"
                class="space-y-6"
            >

                @csrf

                <div>
                    <label class="block text-sm text-slate-300">
                        Customer Name
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
                        Customer Code
                    </label>

                    <input
                        type="text"
                        name="code"
                        value="{{ old('code') }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white"
                    >

                    @error('code')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
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

                <div class="grid gap-6 sm:grid-cols-2">

                    <div>
                        <label class="block text-sm text-slate-300">
                            Credit Limit
                        </label>

                        <input
                            type="number"
                            step="0.0001"
                            min="0"
                            name="credit_limit"
                            value="{{ old('credit_limit', 0) }}"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-sm text-slate-300">
                            Payment Terms (Days)
                        </label>

                        <input
                            type="number"
                            min="0"
                            name="payment_terms_days"
                            value="{{ old('payment_terms_days', 0) }}"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white"
                        >
                    </div>

                </div>

                <div>
                    <label class="flex items-center gap-3 text-slate-300">

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', true))
                        >

                        Active customer

                    </label>
                </div>

                <div class="flex justify-end gap-3">

                    <a
                        href="{{ route('home') }}"
                        class="rounded-xl border border-slate-700 px-5 py-3 text-slate-300"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white"
                    >
                        Create Customer
                    </button>

                </div>

            </form>

        </div>

    </div>

</x-app>