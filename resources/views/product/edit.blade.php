<x-home :company="$company">
<div class="mx-auto max-w-3xl min-w-0 space-y-6 p-6 break-words">
<h1 class="text-2xl text-white">Edit Product</h1>
<p>SKU: {{ $product->sku }}</p>@unless($product->is_active)<p role="status">Archived Product — profile corrections do not restore it.</p>
@endunless
<form method="POST" action="{{ route('product.update', [$company, $product]) }}" class="space-y-4">
@csrf
 @method('PATCH')@include('product._form')<div class="flex flex-wrap gap-3">
<button class="rounded-lg border border-slate-700 px-4 py-2 text-white focus:ring-2">Save profile</button>
<a class="rounded-lg border border-slate-700 px-4 py-2 text-white focus:ring-2" href="{{ route('product.show', [$company, $product]) }}">Cancel</a>
</div>
</form>
</div>
</x-home>
