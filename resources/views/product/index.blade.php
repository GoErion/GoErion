<x-home :company="$company">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">
                    Inventory
                </p>
                <h1 class="mt-2 text-3xl font-bold text-slate-100">
                    Products
                </h1>
            </div>

            <a href="{{ route('product.create', $company) }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-900 bg-transparent px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-700 hover:text-white">
                Add Product
            </a>
        </div>

        @if ($products->isEmpty())
            <div class="rounded-2xl border border-slate-900 bg-transparent p-12 text-center">
                <p class="text-lg font-medium text-slate-200">No products found.</p>
                <p class="mt-2 text-sm text-slate-400">Create your first product to start managing inventory.</p>
            </div>
        @else
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($products as $product)
                    <article
                        class="rounded-lg border border-slate-900 bg-transparent p-5 shadow-sm transition duration-200 hover:border-slate-700">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-[0.2em] text-slate-400">
                                    {{ $product->category?->name ?? 'Uncategorized' }}
                                </p>
                                <h2 class="mt-2 text-xl font-semibold text-slate-100">
                                    {{ $product->name }}
                                </h2>
                            </div>

                            <span
                                class="inline-flex rounded-full border border-slate-900 px-2.5 py-1 text-[10px] font-medium uppercase tracking-wide text-slate-300">
                                {{ $product->product_type?->value ?? $product->product_type }}
                            </span>
                        </div>

                        <div class="mb-4 rounded-lg border border-slate-900 bg-slate-950/30 p-3">
                            <div class="flex items-center justify-between text-sm text-slate-300">
                                <span>SKU</span>
                                <span class="font-medium text-slate-100">
                                    {{ $product->sku ?? '—' }}
                                </span>
                            </div>

                            <div class="mt-2 flex items-center justify-between text-sm text-slate-300">
                                <span>Unit</span>
                                <span class="font-medium text-slate-100">
                                    {{ $product->unit?->name ?? '—' }}
                                </span>
                            </div>
                        </div>

                        <p class="line-clamp-3 text-sm leading-6 text-slate-400">
                            {{ $product->description ?: 'No description provided for this product.' }}
                        </p>

                        <div class="mt-5 flex items-center justify-between border-t border-slate-900 pt-4">
                            <div>
                                <p class="text-[10px] font-medium uppercase tracking-[0.2em] text-slate-500">
                                    Price
                                </p>
                                <p class="mt-1 text-lg font-semibold text-slate-100">
                                    @php
                                        $productPrice = $product->prices->sortByDesc('created_at')->first();
                                    @endphp

                                    @if ($productPrice)
                                        {{ $productPrice->currency ?? 'USD' }}
                                        {{ number_format((float) $productPrice->price, 2) }}
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>

                            <button type="button"
                                class="rounded-lg border border-slate-900 bg-transparent px-3 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-700 hover:text-white">
                                View
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</x-home>
