<x-app>

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

                <div>
                    <label for="name" class="block text-sm text-slate-300">
                        Product Name
                    </label>

                    <input id="name" type="text" name="name" value="{{ old('name') }}" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">

                    @error('name')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="sku" class="block text-sm text-slate-300">
                        SKU
                    </label>

                    <input id="sku" type="text" name="sku" value="{{ old('sku') }}"
                        placeholder="Leave empty to generate automatically"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">

                    <p class="mt-1 text-xs text-slate-500">
                        Leave empty and GoErion will generate the SKU.
                    </p>

                    @error('sku')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="category_id" class="block text-sm text-slate-300">
                        Category
                    </label>

                    <select name="category_id" id="category_id" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                        <option value="">Select category</option>

                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('category_id')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="brand_id" class="block text-sm text-slate-300">
                        Brand
                    </label>

                    <select name="brand_id" id="brand_id"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                        <option value="">No Brand</option>

                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id') == $brand->id)>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('brand_id')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="unit_id" class="block text-sm text-slate-300">
                        Unit
                    </label>

                    <select name="unit_id" id="unit_id" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                        <option value="">Select unit</option>

                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>
                                {{ $unit->name }} ({{ $unit->symbol }})
                            </option>
                        @endforeach
                    </select>

                    @error('unit_id')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="product_type" class="block text-sm text-slate-300">
                        Product Type
                    </label>

                    <select id="product_type" name="product_type" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                        <option value="physical" @selected(old('product_type', 'physical') === 'physical')>Physical</option>
                        <option value="service" @selected(old('product_type') === 'service')>Service</option>
                        <option value="digital" @selected(old('product_type') === 'digital')>Digital</option>
                    </select>

                    @error('product_type')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm text-slate-300">
                        Description
                    </label>

                    <textarea id="description" name="description" rows="4"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">{{ old('description') }}</textarea>

                    @error('description')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

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
                        @foreach (old('prices', []) as $index => $price)
                            <div class="price-row rounded-xl border border-slate-800 p-4">
                                <div class="grid gap-4 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
                                    <div>
                                        <label for="price_type_{{ $index }}"
                                            class="block text-sm text-slate-300">Price Type</label>
                                        <select id="price_type_{{ $index }}"
                                            name="prices[{{ $index }}][price_type]" required
                                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                                            <option value="">Select type</option>
                                            @foreach (['cost' => 'Cost', 'retail' => 'Retail', 'wholesale' => 'Wholesale', 'dealer' => 'Dealer'] as $value => $label)
                                                <option value="{{ $value }}" @selected(($price['price_type'] ?? '') === $value)>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error("prices.{$index}.price_type")
                                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="price_{{ $index }}"
                                            class="block text-sm text-slate-300">Price</label>
                                        <input id="price_{{ $index }}" type="number"
                                            name="prices[{{ $index }}][price]"
                                            value="{{ $price['price'] ?? '' }}" min="0" step="0.01" required
                                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                                        @error("prices.{$index}.price")
                                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="currency_{{ $index }}"
                                            class="block text-sm text-slate-300">Currency</label>
                                        <input id="currency_{{ $index }}" type="text"
                                            name="prices[{{ $index }}][currency]"
                                            value="{{ $price['currency'] ?? 'USD' }}" maxlength="3" required
                                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 uppercase text-white">
                                        @error("prices.{$index}.currency")
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
                        @foreach (old('barcodes', []) as $index => $barcode)
                            <div class="barcode-row rounded-xl border border-slate-800 p-4">
                                <div class="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                                    <div>
                                        <label for="barcode_{{ $index }}"
                                            class="block text-sm text-slate-300">Barcode</label>
                                        <input id="barcode_{{ $index }}" type="text"
                                            name="barcodes[{{ $index }}][barcode]"
                                            value="{{ $barcode['barcode'] ?? '' }}" maxlength="100" required
                                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                                        @error("barcodes.{$index}.barcode")
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
                                        @error("barcodes.{$index}.type")
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

                <div class="space-y-3">

                    <label for="track_stock" class="flex items-center gap-3 text-slate-300">
                        <input id="track_stock" type="checkbox" name="track_stock" value="1"
                            @checked(old('track_stock', true))>

                        Track Stock
                    </label>

                    <label for="track_serial" class="flex items-center gap-3 text-slate-300">
                        <input id="track_serial" type="checkbox" name="track_serial" value="1"
                            @checked(old('track_serial'))>

                        Track Serial Numbers
                    </label>

                    <label for="track_batch" class="flex items-center gap-3 text-slate-300">
                        <input id="track_batch" type="checkbox" name="track_batch" value="1"
                            @checked(old('track_batch'))>

                        Track Batches
                    </label>

                </div>

                <div class="flex justify-end gap-3">

                    <a href="{{ route('home') }}"
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
                        <option value="cost">Cost</option>
                        <option value="retail">Retail</option>
                        <option value="wholesale">Wholesale</option>
                        <option value="dealer">Dealer</option>
                    </select>
                </div>

                <div>
                    <label for="price___INDEX__" class="block text-sm text-slate-300">Price</label>
                    <input id="price___INDEX__" type="number" name="prices[__INDEX__][price]" min="0"
                        step="0.01" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                </div>

                <div>
                    <label for="currency___INDEX__" class="block text-sm text-slate-300">Currency</label>
                    <input id="currency___INDEX__" type="text" name="prices[__INDEX__][currency]" value="USD"
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
        const priceRows = document.getElementById('price-rows');
        const priceTemplate = document.getElementById('price-row-template');
        let priceIndex = {{ count(old('prices', [])) }};

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

        const barcodeRows = document.getElementById('barcode-rows');
        const barcodeTemplate = document.getElementById('barcode-row-template');
        let barcodeIndex = {{ count(old('barcodes', [])) }};

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
    </script>

</x-app>
