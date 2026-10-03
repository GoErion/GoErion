<x-home :company="$company">
<div class="mx-auto max-w-7xl min-w-0 space-y-6 p-6 break-words">
<h1 class="text-2xl text-white">Units</h1>
<a class="rounded-lg border border-slate-700 px-4 py-2 text-white focus:ring-2" href="{{ route('unit.create', $company) }}">Add Unit</a>@if($errors->any())<div role="alert" class="break-words text-red-400">
<ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>
@endforeach
</ul>
</div>
@endif

<form method="GET" class="flex flex-wrap gap-3">
<label for="status">Status</label>
<select id="status" name="status" class="bg-slate-900 focus:ring-2">@foreach(['active', 'archived', 'all'] as $value)<option value="{{ $value }}" @selected($status === $value)>{{ ucfirst($value) }}</option>
@endforeach
</select>
<button class="rounded-lg border border-slate-700 px-4 py-2 text-white focus:ring-2">Filter</button>
</form>
<div class="overflow-x-auto">
<table class="w-full table-fixed break-words text-left">
<thead>
<tr>
<th scope="col" class="p-3">Name</th>
<th scope="col" class="p-3">Symbol</th>
<th scope="col" class="p-3">Status / actions</th>
</tr>
</thead>
<tbody>@forelse($units as $record)<tr>
<td class="min-w-0 break-words p-3">{{ $record->name }}</td>
<td class="break-words p-3">{{ $record->symbol }}</td>
<td class="p-3">
<p>{{ $record->is_active ? 'Active' : 'Archived' }}</p>
<div class="flex flex-wrap gap-3">
<a class="focus:ring-2" href="{{ route('unit.edit', [$company, $record]) }}">Edit</a>
<form method="POST" action="{{ route($record->is_active ? 'unit.archive' : 'unit.restore', [$company, $record]) }}">
@csrf
 @method('PATCH')<button class="focus:ring-2">{{ $record->is_active ? 'Archive' : 'Restore' }}</button>
</form>
</div>
</td>
</tr>
@empty
<tr>
<td colspan="3" class="p-5">No units found.</td>
</tr>
@endforelse
</tbody>
</table>
</div>{{ $units->links() }}</div>
</x-home>
