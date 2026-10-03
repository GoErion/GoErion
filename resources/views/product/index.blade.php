<x-home :company="$company">
<div class="mx-auto max-w-7xl min-w-0 space-y-6 p-6 break-words">
<h1 class="text-3xl text-white">Products</h1>
<div class="flex flex-wrap gap-3">@can('create', [\App\Models\Product\Product::class, $company])<a class="rounded-lg border border-slate-700 px-4 py-2 text-white focus:ring-2" href="{{ route('product.create', $company) }}">Add Product</a>
@endcan

@can('manageClassifications', [\App\Models\Product\Product::class, $company])<a class="rounded-lg border border-slate-700 px-4 py-2 text-white focus:ring-2" href="{{ route('category.index', $company) }}">Manage category</a>
<a class="rounded-lg border border-slate-700 px-4 py-2 text-white focus:ring-2" href="{{ route('brand.index', $company) }}">Manage brand</a>
<a class="rounded-lg border border-slate-700 px-4 py-2 text-white focus:ring-2" href="{{ route('unit.index', $company) }}">Manage unit</a>
@endcan
</div>@if($errors->any())<div role="alert" class="break-words text-red-400">
<ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>
@endforeach
</ul>
</div>
@endif

@include('product._filters')
@if($products->isEmpty())<p role="status">{{ $catalogEmpty ? 'No products yet.' : 'No products match the current filters.' }}</p>@else
<div class="overflow-x-auto">
<table class="w-full table-fixed break-words text-left">
<thead>
<tr>@foreach(['Product / SKU', 'Classification', 'Retail price', 'Status / actions'] as $heading)<th scope="col" class="p-3">{{ $heading }}</th>
@endforeach
</tr>
</thead>
<tbody>@foreach($products as $product)<tr>
<td class="min-w-0 break-words p-3">
<a class="text-pink-300 focus:ring-2" href="{{ route('product.show', [$company, $product]) }}">{{ $product->name }}</a>
<p>{{ $product->sku }}</p>
</td>
<td class="min-w-0 break-words p-3">{{ $product->category->name }} / {{ $product->unit->name }}<p>{{ $product->brand?->name ?? 'No Brand' }}</p>
</td>
<td class="break-words p-3">@if($price = $product->prices->first()){{ $price->currency }} {{ $price->price }}@else No retail price.
@endif
</td>
<td class="p-3">
<p>{{ $product->is_active ? 'Active' : 'Archived' }}</p>
<div class="flex flex-wrap gap-3">@if($canUpdate)<a class="focus:ring-2" href="{{ route('product.edit', [$company, $product]) }}">Edit</a>
@endif
 @if($canArchive)<form method="POST" action="{{ route($product->is_active ? 'product.archive' : 'product.restore', [$company, $product]) }}">
@csrf
 @method('PATCH')<button class="focus:ring-2">{{ $product->is_active ? 'Archive' : 'Restore' }}</button>
</form>
@endif
</div>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>{{ $products->links() }}
@endif
</div>
</x-home>
