@if($errors->any())<div role="alert" class="break-words text-red-400">
<ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>
@endforeach
</ul>
</div>
@endif

<div>
<label for="name">Name</label>
<input id="name" name="name" @error('name') aria-invalid="true" aria-describedby="unit-name-error" @enderror value="{{ $form['name'] ?? '' }}" required class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">@error('name')<p id="unit-name-error" class="text-red-400">{{ $message }}</p>@enderror</div>
<div>
<label for="symbol">Symbol</label>
<input id="symbol" name="symbol" @error('symbol') aria-invalid="true" aria-describedby="unit-symbol-error" @enderror value="{{ $form['symbol'] ?? '' }}" required class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">@error('symbol')<p id="unit-symbol-error" class="text-red-400">{{ $message }}</p>@enderror</div>
