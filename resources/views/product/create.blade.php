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
                </div>

                <div>
                    <label for="description" class="block text-sm text-slate-300">
                        Description
                    </label>

                    <textarea id="description" name="description" rows="4"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">{{ old('description') }}</textarea>
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

                    <a href="{{ route('home') }}" class="rounded-xl border border-slate-700 px-5 py-3 text-slate-300">
                        Cancel
                    </a>

                    <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white">
                        Create Product
                    </button>

                </div>

            </form>

        </div>

    </div>

</x-app>
