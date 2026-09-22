<x-home :company="$company" :companies="$companies">
    <section class="mx-auto max-w-4xl space-y-6 py-6">
        <div><a class="text-sm text-pink-300 hover:underline" href="{{ route('company.customers.index', $company) }}">← Customers</a><h1 class="mt-2 text-3xl font-bold text-white">New customer</h1><p class="text-slate-400">A company customer number is assigned when you save.</p></div>
        <form method="POST" action="{{ route('company.customers.store', $company) }}" class="space-y-6 rounded-2xl border border-slate-800 bg-slate-950 p-5 sm:p-8">
            @csrf
            @include('customer._form', ['customer' => null, 'groups' => $groups])
            @can('manageCredit', [\App\Models\Customer\Customer::class, $company])
                <fieldset class="grid gap-4 border-t border-slate-800 pt-6 sm:grid-cols-2"><legend class="text-lg font-semibold text-white">Commercial terms</legend>
                    <div><label for="credit_limit" class="block text-sm font-medium text-slate-200">Credit limit ({{ $company->currency }})</label><input id="credit_limit" name="credit_limit" type="text" inputmode="decimal" value="{{ old('credit_limit', '0') }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-white">@error('credit_limit')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror</div>
                    <div><label for="payment_terms_days" class="block text-sm font-medium text-slate-200">Payment terms (days)</label><input id="payment_terms_days" name="payment_terms_days" type="number" min="0" max="3650" value="{{ old('payment_terms_days', 0) }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-white">@error('payment_terms_days')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror</div>
                </fieldset>
            @endcan
            <div class="flex justify-end"><button class="rounded-lg bg-pink-600 px-5 py-2 font-semibold text-white hover:bg-pink-500">Create customer</button></div>
        </form>
    </section>
</x-home>
