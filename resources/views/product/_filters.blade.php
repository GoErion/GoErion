<form method="GET" action="{{ route('product.index', $company) }}" class="grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-3">
<div>
<label for="q">Search SKU, name or barcode</label>
<input id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">
</div>
<div>
<label for="category">Category</label>
<select id="category" name="category" class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">
<option value="">All</option>@foreach($categories as $option)<option value="{{ $option->uuid }}" @selected(($filters['category'] ?? '') === $option->uuid)>{{ $option->name }}{{ $option->is_active ? '' : ' (Archived)' }}</option>
@endforeach
</select>
</div>
<div>
<label for="brand">Brand</label>
<select id="brand" name="brand" class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">
<option value="">All</option>@foreach($brands as $option)<option value="{{ $option->uuid }}" @selected(($filters['brand'] ?? '') === $option->uuid)>{{ $option->name }}{{ $option->is_active ? '' : ' (Archived)' }}</option>
@endforeach
</select>
</div>
<div>
<label for="unit">Unit</label>
<select id="unit" name="unit" class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">
<option value="">All</option>@foreach($units as $option)<option value="{{ $option->uuid }}" @selected(($filters['unit'] ?? '') === $option->uuid)>{{ $option->name }}{{ $option->is_active ? '' : ' (Archived)' }}</option>
@endforeach
</select>
</div>
@if($canViewSuppliers)
<div>
<label for="supplier">Supplier</label>
<select id="supplier" name="supplier" class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">
<option value="">All</option>@foreach($suppliers as $option)<option value="{{ $option->uuid }}" @selected(($filters['supplier'] ?? '') === $option->uuid)>{{ $option->name }}{{ $option->is_active ? '' : ' (Archived)' }}</option>
@endforeach
</select>
</div>

@endif

<div>
<label for="status">Status</label>
<select id="status" name="status" class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">@foreach(['active', 'archived', 'all'] as $value)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ ucfirst(str_replace('_', ' ', $value)) }}</option>
@endforeach
</select>
</div>
<div>
<label for="sort">Sort</label>
<select id="sort" name="sort" class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">@foreach(['created_at', 'name', 'sku'] as $value)<option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ ucfirst(str_replace('_', ' ', $value)) }}</option>
@endforeach
</select>
</div>
<div>
<label for="direction">Direction</label>
<select id="direction" name="direction" class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">@foreach(['desc', 'asc'] as $value)<option value="{{ $value }}" @selected($filters['direction'] === $value)>{{ ucfirst(str_replace('_', ' ', $value)) }}</option>
@endforeach
</select>
</div>
<div class="flex flex-wrap items-end gap-3">
<button class="rounded-lg border border-slate-700 px-4 py-2 text-white focus:ring-2">Apply filters</button>
<a class="rounded-lg border border-slate-700 px-4 py-2 text-white focus:ring-2" href="{{ route('product.index', $company) }}">Clear filters</a>
</div>
</form>
