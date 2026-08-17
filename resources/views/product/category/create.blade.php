<x-app>

    <x-slot name="title">
        Create Category
    </x-slot>

    <div class="mx-auto max-w-3xl px-4">

        <div class="rounded-3xl border border-slate-800 bg-transparent p-8">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-white">
                    Create Category
                </h1>

                <p class="mt-2 text-slate-400">
                    {{ $company->company_name }}
                </p>
            </div>

            <form method="POST" action="{{ route('category.store', $company) }}" class="space-y-6">

                @csrf

                <div>
                    <label for="parent_id" class="block text-sm text-slate-300">
                        Parent Category
                    </label>

                    <select name="parent_id" id="parent_id"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">
                        <option value="">None</option>

                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('parent_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                            @foreach ($category->descendants as $child)
                                    <option value="{{ $child->id }}">
                                    {{ $child->name }}
                                    </option>
                            @endforeach
                        @endforeach
                    </select>

                    @error('parent_id')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm text-slate-300">
                        Category Name
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
                    <label for="code" class="block text-sm text-slate-300">
                        Category Code
                    </label>

                    <input id="code" type="text" name="code" value="{{ old('code') }}" required
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white">

                    @error('code')
                        <p class="mt-1 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3">

                    <a href="{{ route('home') }}" class="rounded-xl border border-slate-700 px-5 py-3 text-slate-300">
                        Cancel
                    </a>

                    <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white">
                        Create Category
                    </button>

                </div>

            </form>

        </div>

    </div>

</x-app>
