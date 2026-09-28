<x-home :company="$company">
    <x-slot name="title">
        Brands
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">
                    Inventory
                </p>

                <h1 class="mt-2 text-3xl font-bold text-slate-100 break-words">
                    Brands
                </h1>
            </div>

            <a href="{{ route('brand.create', $company) }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-900 px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-700 hover:text-white">
                Add Brand
            </a>
        </div>

        @if ($brands->isEmpty())
            <div class="rounded-2xl border border-slate-900 p-12 text-center">
                <p class="text-lg font-medium text-slate-200">
                    No brands found.
                </p>

                <p class="mt-2 text-sm text-slate-400">
                    Create your first brand to organize your products.
                </p>
            </div>
        @else
            <div class="overflow-x-auto rounded-2xl border border-slate-900">
                <table class="w-full table-fixed break-words divide-y divide-slate-900">
                    <thead class="bg-slate-950/30">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">Code</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-900">
                        @foreach ($brands as $brand)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-slate-100 break-words">{{ $brand->name }}{{ $brand->is_active ? '' : ' (Archived)' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-300">{{ $brand->code }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('brand.edit', [$company, $brand]) }}"
                                            class="text-sm font-medium text-emerald-400 hover:text-emerald-300">
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('brand.archive', [$company, $brand]) }}">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit" class="text-sm font-medium text-red-400 hover:text-red-300">
                                                Archive
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-8">
                {{ $brands->links() }}
            </div>
        @endif
    </div>
</x-home>
