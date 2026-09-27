<x-home :company="$company">

```
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">
                Inventory
            </p>

            <h1 class="mt-2 text-3xl font-bold text-slate-100">
                {{ $product->name }}
            </h1>

            <p class="mt-2 text-sm text-slate-400">
                SKU: {{ $product->sku }}
            </p>
        </div>

        <div class="flex items-center gap-3">

            <a
                href="{{ route('product.index', ['company' => $company]) }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-900 bg-transparent px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-700 hover:text-white"
            >
                Back to Products
            </a>

        </div>

    </div>


    {{-- Product --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Main information --}}
        <div class="lg:col-span-2">

            <section class="rounded-2xl border border-slate-900 bg-transparent p-6">

                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">
                            Product
                        </p>

                        <h2 class="mt-2 text-2xl font-semibold text-slate-100">
                            {{ $product->name }}
                        </h2>
                    </div>

                    <div class="flex flex-wrap gap-2">

                        <span
                            class="inline-flex rounded-full border border-slate-900 px-3 py-1 text-xs font-medium uppercase tracking-wide text-slate-300"
                        >
                            {{ $product->product_type?->value ?? $product->product_type }}
                        </span>

                        <span
                            class="inline-flex rounded-full border border-slate-900 px-3 py-1 text-xs font-medium uppercase tracking-wide {{ $product->is_active ? 'text-emerald-400' : 'text-red-400' }}"
                        >
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>

                    </div>

                </div>


                {{-- Product information --}}
                <div class="mt-8">

                    <h3 class="text-sm font-semibold uppercase tracking-[0.15em] text-slate-400">
                        Product Information
                    </h3>

                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">

                        <div class="rounded-lg border border-slate-900 p-4">
                            <dt class="text-xs uppercase tracking-[0.15em] text-slate-500">
                                SKU
                            </dt>

                            <dd class="mt-2 font-medium text-slate-100">
                                {{ $product->sku }}
                            </dd>
                        </div>

                        <div class="rounded-lg border border-slate-900 p-4">
                            <dt class="text-xs uppercase tracking-[0.15em] text-slate-500">
                                Category
                            </dt>

                            <dd class="mt-2 font-medium text-slate-100">
                                {{ $product->category?->name ?? 'Uncategorized' }}
                            </dd>
                        </div>

                        <div class="rounded-lg border border-slate-900 p-4">
                            <dt class="text-xs uppercase tracking-[0.15em] text-slate-500">
                                Brand
                            </dt>

                            <dd class="mt-2 font-medium text-slate-100">
                                {{ $product->brand?->name ?? 'No Brand' }}
                            </dd>
                        </div>

                        <div class="rounded-lg border border-slate-900 p-4">
                            <dt class="text-xs uppercase tracking-[0.15em] text-slate-500">
                                Unit
                            </dt>

                            <dd class="mt-2 font-medium text-slate-100">
                                {{ $product->unit?->name ?? '—' }}
                            </dd>
                        </div>

                        <div class="rounded-lg border border-slate-900 p-4">
                            <dt class="text-xs uppercase tracking-[0.15em] text-slate-500">
                                Product Type
                            </dt>

                            <dd class="mt-2 font-medium text-slate-100">
                                {{ $product->product_type?->value ?? $product->product_type }}
                            </dd>
                        </div>

                        <div class="rounded-lg border border-slate-900 p-4">
                            <dt class="text-xs uppercase tracking-[0.15em] text-slate-500">
                                Status
                            </dt>

                            <dd
                                class="mt-2 font-medium {{ $product->is_active ? 'text-emerald-400' : 'text-red-400' }}"
                            >
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </dd>
                        </div>

                    </dl>

                </div>


                {{-- Description --}}
                <div class="mt-8 border-t border-slate-900 pt-6">

                    <h3 class="text-sm font-semibold uppercase tracking-[0.15em] text-slate-400">
                        Description
                    </h3>

                    <p class="mt-4 text-sm leading-7 text-slate-400">
                        {{ $product->description ?: 'No description provided for this product.' }}
                    </p>

                </div>


                {{-- Inventory tracking --}}
                <div class="mt-8 border-t border-slate-900 pt-6">

                    <h3 class="text-sm font-semibold uppercase tracking-[0.15em] text-slate-400">
                        Inventory Tracking
                    </h3>

                    <div class="mt-4 grid gap-4 sm:grid-cols-3">

                        <div class="rounded-lg border border-slate-900 p-4">

                            <p class="text-xs uppercase tracking-[0.15em] text-slate-500">
                                Stock
                            </p>

                            <p class="mt-2 text-sm font-medium text-slate-100">
                                {{ $product->track_stock ? 'Tracked' : 'Not Tracked' }}
                            </p>

                        </div>

                        <div class="rounded-lg border border-slate-900 p-4">

                            <p class="text-xs uppercase tracking-[0.15em] text-slate-500">
                                Serial
                            </p>

                            <p class="mt-2 text-sm font-medium text-slate-100">
                                {{ $product->track_serial ? 'Tracked' : 'Not Tracked' }}
                            </p>

                        </div>

                        <div class="rounded-lg border border-slate-900 p-4">

                            <p class="text-xs uppercase tracking-[0.15em] text-slate-500">
                                Batch
                            </p>

                            <p class="mt-2 text-sm font-medium text-slate-100">
                                {{ $product->track_batch ? 'Tracked' : 'Not Tracked' }}
                            </p>

                        </div>

                    </div>

                </div>

            </section>

        </div>


        {{-- Sidebar --}}
        <aside class="space-y-6">


            {{-- Prices --}}
            <section class="rounded-2xl border border-slate-900 bg-transparent p-6">

                <div class="flex items-center justify-between">

                    <h2 class="text-lg font-semibold text-slate-100">
                        Prices
                    </h2>

                    <span class="text-xs text-slate-500">
                        {{ $product->prices->count() }}
                    </span>

                </div>

                @forelse ($product->prices as $price)

                    <div class="mt-4 rounded-lg border border-slate-900 p-4">

                        <div class="flex items-center justify-between gap-4">

                            <div>

                                <p class="text-sm font-medium text-slate-100">
                                    {{ ucfirst($price->price_type->value) }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $price->currency }}
                                </p>

                            </div>

                            <p class="text-lg font-semibold text-slate-100">
                                {{ $price->currency }}
                                {{ number_format((float) $price->price, 2) }}
                            </p>

                        </div>

                    </div>

                @empty

                    <p class="mt-4 text-sm text-slate-400">
                        No prices added.
                    </p>

                @endforelse

            </section>


            {{-- Barcodes --}}
            <section class="rounded-2xl border border-slate-900 bg-transparent p-6">

                <h2 class="text-lg font-semibold text-slate-100">
                    Barcodes
                </h2>

                @forelse ($product->barcodes as $barcode)

                    <div
                        class="mt-4 flex items-center justify-between rounded-lg border border-slate-900 p-3"
                    >

                        <span class="font-mono text-sm text-slate-200">
                            {{ $barcode->barcode }}
                        </span>

                        <span class="text-xs uppercase text-slate-500">
                            {{ $barcode->type?->value ?? $barcode->type }}
                        </span>

                    </div>

                @empty

                    <p class="mt-4 text-sm text-slate-400">
                        No barcodes assigned.
                    </p>

                @endforelse

            </section>


            {{-- Suppliers --}}
            @if ($canViewSuppliers)
            <section class="rounded-2xl border border-slate-900 bg-transparent p-6">

                <div class="flex items-center justify-between">

                    <h2 class="text-lg font-semibold text-slate-100">
                        Suppliers
                    </h2>

                    <span class="text-xs text-slate-500">
                        {{ $product->suppliers->count() }}
                    </span>

                </div>

                @forelse ($product->suppliers as $supplier)

                    <div class="mt-4 rounded-lg border border-slate-900 p-4">

                        <p class="break-words font-medium text-slate-100">
                            {{ $supplier->name }}{{ $supplier->is_active ? '' : ' (Archived)' }}
                        </p>

                        @if ($supplier->pivot?->supplier_sku)

                            <p class="mt-1 text-xs text-slate-500">
                                Supplier SKU:
                                {{ $supplier->pivot->supplier_sku }}
                            </p>

                        @endif

                        @if ($supplier->pivot?->cost !== null)

                            <p class="mt-2 text-sm text-slate-300">
                                Cost:
                                {{ $supplier->pivot->cost }} <span class="text-xs">(currency not assigned)</span>
                            </p>

                        @endif

                    </div>

                @empty

                    <p class="mt-4 text-sm text-slate-400">
                        No suppliers linked.
                    </p>

                @endforelse

            </section>
            @endif


            {{-- Record --}}
            <section class="rounded-2xl border border-slate-900 bg-transparent p-6">

                <h2 class="text-lg font-semibold text-slate-100">
                    Record
                </h2>

                <dl class="mt-4 space-y-4">

                    <div>
                        <dt class="text-xs uppercase tracking-[0.15em] text-slate-500">
                            Created
                        </dt>

                        <dd class="mt-1 text-sm text-slate-300">
                            {{ $product->created_at?->format('d M Y, H:i') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-[0.15em] text-slate-500">
                            Updated
                        </dt>

                        <dd class="mt-1 text-sm text-slate-300">
                            {{ $product->updated_at?->format('d M Y, H:i') }}
                        </dd>
                    </div>

                </dl>

            </section>

        </aside>

    </div>

</div>
```

</x-home>
