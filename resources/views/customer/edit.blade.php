<x-home :company="$company" :companies="$companies">
    <section class="mx-auto max-w-4xl space-y-6 py-6">
        <div><a class="text-sm text-pink-300 hover:underline" href="{{ route('company.customers.show', [$company, $customer]) }}">← {{ $customer->name }}</a><h1 class="mt-2 text-3xl font-bold text-white">Edit customer</h1><p class="text-slate-400">Customer number {{ $customer->code }} is permanent.</p></div>
        <form method="POST" action="{{ route('company.customers.update', [$company, $customer]) }}" class="space-y-6 rounded-2xl border border-slate-800 bg-slate-950 p-5 sm:p-8">
            @csrf @method('PATCH')
            @include('customer._form', ['customer' => $customer, 'groups' => $groups])
            <div class="flex justify-end"><button class="rounded-lg bg-pink-600 px-5 py-2 font-semibold text-white hover:bg-pink-500">Save customer</button></div>
        </form>
    </section>
</x-home>
