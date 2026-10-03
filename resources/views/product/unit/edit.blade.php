<x-home :company="$company">
<div class="mx-auto max-w-3xl min-w-0 space-y-6 p-6 break-words">
<h1 class="text-2xl text-white">Edit Unit</h1>
<form method="POST" action="{{ route('unit.update', [$company, $unit]) }}" class="space-y-4">
@csrf
 @method('PATCH')@include('product.unit._form')<div class="flex flex-wrap gap-3">
<button class="rounded-lg border border-slate-700 px-4 py-2 text-white focus:ring-2">Save Unit</button>
<a class="rounded-lg border border-slate-700 px-4 py-2 text-white focus:ring-2" href="{{ route('unit.index', $company) }}">Cancel</a>
</div>
</form>
</div>
</x-home>
