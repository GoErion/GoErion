<x-home :company="$company">
    <div class="break-words mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">
                Inventory
            </p>

            <h1 class="mt-2 text-3xl font-bold text-slate-100 break-words">
                Products
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-3">
@can('create', [\App\Models\Product\Product::class, $company])
            <a
                href="{{ route('product.create', $company) }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-900 bg-transparent px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-700 hover:text-white"
            >
                Add Product
            </a>

@endcan
@can('manageClassifications', [\App\Models\Product\Product::class, $company])
            <a
                href="{{ route('category.create', $company) }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-900 bg-transparent px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-700 hover:text-white"
            >
                Add Category
            </a>

            <a
                href="{{ route('brand.create', $company) }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-900 bg-transparent px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-700 hover:text-white"
            >
                Add Brand
            </a>

            <a
                href="{{ route('unit.create', $company) }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-900 bg-transparent px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-700 hover:text-white"
            >
                Add Unit
            </a>
@endcan
        </div>
    </div>

    @if ($products->isEmpty())

        <div class="rounded-2xl border border-slate-900 bg-transparent p-12 text-center">
            <p class="text-lg font-medium text-slate-200">
                No products found.
            </p>

            <p class="mt-2 text-sm text-slate-400">
                Create your first product to start managing inventory.
            </p>
        </div>

    @else

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

            @foreach ($products as $product)

                <article
                    class="relative rounded-lg border border-slate-900 bg-transparent p-5 shadow-sm transition duration-200 hover:border-slate-700"
                >

                    {{-- Product show link --}}
                    <a
                        href="{{ route('product.show', [
                            'company' => $company,
                            'product' => $product,
                        ]) }}"
                        class="absolute inset-0 z-10"
                        aria-label="View {{ $product->name }}"
                    ></a>

                    <div class="mb-4 flex items-start justify-between gap-3">

                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-[0.2em] text-slate-400">
                                {{ $product->category?->name ?? 'Uncategorized' }}
                            </p>

                            <h2 class="mt-2 text-xl font-semibold text-slate-100 break-words">
                                {{ $product->name }}
                            </h2>
                        </div>

                        <span
                            class="inline-flex rounded-full border border-slate-900 px-2.5 py-1 text-[10px] font-medium uppercase tracking-wide text-slate-300"
                        >
                            {{ $product->product_type?->value ?? $product->product_type }}
                        </span>

                    </div>


                    <div class="mb-4 rounded-lg border border-slate-900 bg-slate-950/30 p-3">

                        <div class="flex items-center justify-between text-sm text-slate-300">
                            <span>SKU</span>

                            <span class="font-medium text-slate-100 break-words">
                                {{ $product->sku ?? '—' }}
                            </span>
                        </div>

                        <div class="mt-2 flex items-center justify-between text-sm text-slate-300">
                            <span>Unit</span>

                            <span class="font-medium text-slate-100 break-words">
                                {{ $product->unit?->name ?? '—' }}
                            </span>
                        </div>

                    </div>


                    <p class="break-words text-sm leading-6 text-slate-400">
                        {{ $product->description ?: 'No description provided for this product.' }}
                    </p>


                    <div class="mt-5 flex items-center justify-between border-t border-slate-900 pt-4">

                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-[0.2em] text-slate-500">
                                Price
                            </p>

                            <p class="mt-1 text-lg font-semibold text-slate-100 break-words">

                                @php
                                    $productPrice = $product->prices->first();
                                @endphp

                                @if ($productPrice)

                                    {{ $productPrice->currency }}
                                    {{ $productPrice->price }}

                                @else

                                    No retail price.

                                @endif

                            </p>
                        </div>


                        {{-- View link --}}
                        <a
                            href="{{ route('product.show', [
                                'company' => $company,
                                'product' => $product,
                            ]) }}"
                            class="relative z-20 inline-flex items-center rounded-lg border border-slate-900 bg-transparent px-3 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-700 hover:text-white"
                        >
                            View
                        </a>

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
