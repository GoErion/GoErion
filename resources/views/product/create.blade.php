@props(['company'])
<x-home :company="$company">

    <x-slot name="title">
        Create Product
    </x-slot>

    <div class="mx-auto max-w-3xl px-4">

        <div class="rounded-3xl border border-slate-800 bg-transparent p-8">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-white">
                    Create Product
                </h1>

                <p class="mt-2 text-slate-400">
                    {{ $company->company_name }}
                </p>
            </div>

            <form method="POST" action="{{ route('product.store', $company) }}" class="space-y-6">

                @csrf

@include('product._form')
<div><label for="product_type">Product Type</label><select id="product_type" name="product_type" class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">@foreach(['physical', 'service', 'digital'] as $type)<option value="{{ $type }}" @selected(($form['product_type'] ?? 'physical') === $type)>{{ ucfirst($type) }}</option>@endforeach</select>@error('product_type')<p class="text-red-400">{{ $message }}</p>@enderror</div>
@can('managePrices', [\App\Models\Product\Product::class, $company])
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-sm text-slate-300">Product Prices</h2>
                            <p class="mt-1 text-xs text-slate-500">Add prices only where they apply.</p>
                        </div>

                        <button type="button" id="add-price"
                            class="rounded-xl border border-emerald-700 px-4 py-2 text-sm text-emerald-400">
                            Add Price
                        </button>
                    </div>

                    <div id="price-rows" class="space-y-4">
                        @foreach ($priceRows as $index => $price)
                            <div class="price-row rounded-xl border border-slate-800 p-4">
                                <div class="grid gap-4 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
                                    <div>
                                        <label for="price_type_{{ $index }}"
                                            class="block text-sm text-slate-300">Price Type</label>
                                        <select id="price_type_{{ $index }}"
                                            name="prices[{{ $index }}][price_type]" required
                                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                                            <option value="">Select type</option>
                                            @foreach (['retail' => 'Retail', 'wholesale' => 'Wholesale', 'dealer' => 'Dealer'] as $value => $label)
                                                <option value="{{ $value }}" @selected(($price['price_type'] ?? '') === $value)>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error("prices.{$price['error_index']}.price_type")
                                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="price_{{ $index }}"
                                            class="block text-sm text-slate-300">Price</label>
                                        <input id="price_{{ $index }}" type="number"
                                            name="prices[{{ $index }}][price]"
                                            value="{{ $price['price'] ?? '' }}" min="0" step="0.0001" required
                                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                                        @error("prices.{$price['error_index']}.price")
                                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="currency_{{ $index }}"
                                            class="block text-sm text-slate-300">Currency</label>
                                        <input id="currency_{{ $index }}" type="text"
                                            name="prices[{{ $index }}][currency]"
                                            value="{{ $price['currency'] ?? strtoupper($company->currency) }}" maxlength="3" required
                                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 uppercase text-white">
                                        @error("prices.{$price['error_index']}.currency")
                                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <button type="button"
                                        class="remove-price rounded-xl border border-red-900 px-4 py-3 text-sm text-red-400">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

@endcan
@can('manageBarcodes', [\App\Models\Product\Product::class, $company])
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-sm text-slate-300">Product Barcodes</h2>
                            <p class="mt-1 text-xs text-slate-500">Add one or more unique product barcodes.</p>
                        </div>

                        <button type="button" id="add-barcode"
                            class="rounded-xl border border-emerald-700 px-4 py-2 text-sm text-emerald-400">
                            Add Barcode
                        </button>
                    </div>

                    <div id="barcode-rows" class="space-y-4">
                        @foreach ($barcodeRows as $index => $barcode)
                            <div class="barcode-row rounded-xl border border-slate-800 p-4">
                                <div class="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                                    <div>
                                        <label for="barcode_{{ $index }}"
                                            class="block text-sm text-slate-300">Barcode</label>
                                        <input id="barcode_{{ $index }}" type="text"
                                            name="barcodes[{{ $index }}][barcode]"
                                            value="{{ $barcode['barcode'] ?? '' }}" maxlength="100" required
                                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                                        @error("barcodes.{$barcode['error_index']}.barcode")
                                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="barcode_type_{{ $index }}"
                                            class="block text-sm text-slate-300">Barcode Type</label>
                                        <select id="barcode_type_{{ $index }}"
                                            name="barcodes[{{ $index }}][type]" required
                                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                                            <option value="">Select type</option>
                                            @foreach (['EAN' => 'EAN', 'UPC' => 'UPC', 'CODE128' => 'Code 128', 'GTIN' => 'GTIN'] as $value => $label)
                                                <option value="{{ $value }}" @selected(($barcode['type'] ?? '') === $value)>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error("barcodes.{$barcode['error_index']}.type")
                                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <button type="button"
                                        class="remove-barcode rounded-xl border border-red-900 px-4 py-3 text-sm text-red-400">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

@endcan
                <div class="space-y-3">

                    <label for="track_stock" class="flex items-center gap-3 text-slate-300">
                        <input id="track_stock" type="checkbox" name="track_stock" value="1"
                            @checked($form['track_stock'] ?? (session()->hasOldInput() ? false : true))>

                        Track Stock
                    </label>

                    <label for="track_serial" class="flex items-center gap-3 text-slate-300">
                        <input id="track_serial" type="checkbox" name="track_serial" value="1"
                            @checked($form['track_serial'] ?? false)>

                        Track Serial Numbers
                    </label>

                    <label for="track_batch" class="flex items-center gap-3 text-slate-300">
                        <input id="track_batch" type="checkbox" name="track_batch" value="1"
                            @checked($form['track_batch'] ?? false)>

                        Track Batches
                    </label>

                </div>

                <div class="flex justify-end gap-3">

                    <a href="{{ route('home',$company) }}"
                        class="rounded-xl border border-slate-700 px-5 py-3 text-slate-300">
                        Cancel
                    </a>

                    <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white">
                        Create Product
                    </button>

                </div>

            </form>

        </div>

    </div>

    <template id="price-row-template">
        <div class="price-row rounded-xl border border-slate-800 p-4">
            <div class="grid gap-4 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
                <div>
                    <label for="price_type___INDEX__" class="block text-sm text-slate-300">Price Type</label>
                    <select id="price_type___INDEX__" name="prices[__INDEX__][price_type]" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                        <option value="">Select type</option>
                        <option value="retail">Retail</option>
                        <option value="wholesale">Wholesale</option>
                        <option value="dealer">Dealer</option>
                    </select>
                </div>

                <div>
                    <label for="price___INDEX__" class="block text-sm text-slate-300">Price</label>
                    <input id="price___INDEX__" type="number" name="prices[__INDEX__][price]" min="0"
                        step="0.0001" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                </div>

                <div>
                    <label for="currency___INDEX__" class="block text-sm text-slate-300">Currency</label>
                    <input id="currency___INDEX__" type="text" name="prices[__INDEX__][currency]" value="{{ strtoupper($company->currency) }}"
                        maxlength="3" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 uppercase text-white">
                </div>

                <button type="button"
                    class="remove-price rounded-xl border border-red-900 px-4 py-3 text-sm text-red-400">Remove</button>
            </div>
        </div>
    </template>

    <template id="barcode-row-template">
        <div class="barcode-row rounded-xl border border-slate-800 p-4">
            <div class="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <div>
                    <label for="barcode___INDEX__" class="block text-sm text-slate-300">Barcode</label>
                    <input id="barcode___INDEX__" type="text" name="barcodes[__INDEX__][barcode]" maxlength="100"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                </div>

                <div>
                    <label for="barcode_type___INDEX__" class="block text-sm text-slate-300">Barcode Type</label>
                    <select id="barcode_type___INDEX__" name="barcodes[__INDEX__][type]" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                        <option value="">Select type</option>
                        <option value="EAN">EAN</option>
                        <option value="UPC">UPC</option>
                        <option value="CODE128">Code 128</option>
                        <option value="GTIN">GTIN</option>
                    </select>
                </div>

                <button type="button"
                    class="remove-barcode rounded-xl border border-red-900 px-4 py-3 text-sm text-red-400">Remove</button>
            </div>
        </div>
    </template>

    <script>
        if (document.getElementById('price-rows')) {
        const priceRows = document.getElementById('price-rows');
        const priceTemplate = document.getElementById('price-row-template');
        let priceIndex = {{ count($priceRows) }};

        document.getElementById('add-price').addEventListener('click', () => {
            const row = priceTemplate.content.cloneNode(true);

            row.querySelectorAll('[id], [for], [name]').forEach((element) => {
                ['id', 'for', 'name'].forEach((attribute) => {
                    if (element.hasAttribute(attribute)) {
                        element.setAttribute(attribute, element.getAttribute(attribute).replaceAll(
                            '__INDEX__', priceIndex));
                    }
                });
            });

            priceRows.appendChild(row);
            priceIndex++;
        });

        priceRows.addEventListener('click', (event) => {
            if (event.target.closest('.remove-price')) {
                event.target.closest('.price-row').remove();
            }
        });

        }
        if (document.getElementById('barcode-rows')) {
        const barcodeRows = document.getElementById('barcode-rows');
        const barcodeTemplate = document.getElementById('barcode-row-template');
        let barcodeIndex = {{ count($barcodeRows) }};

        document.getElementById('add-barcode').addEventListener('click', () => {
            const row = barcodeTemplate.content.cloneNode(true);

            row.querySelectorAll('[id], [for], [name]').forEach((element) => {
                ['id', 'for', 'name'].forEach((attribute) => {
                    if (element.hasAttribute(attribute)) {
                        element.setAttribute(attribute, element.getAttribute(attribute).replaceAll(
                            '__INDEX__', barcodeIndex));
                    }
                });
            });

            barcodeRows.appendChild(row);
            barcodeIndex++;
        });

        barcodeRows.addEventListener('click', (event) => {
            if (event.target.closest('.remove-barcode')) {
                event.target.closest('.barcode-row').remove();
            }
        });
        }
    </script>

</x-home>
