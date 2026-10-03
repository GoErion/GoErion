@if($errors->any())<div role="alert" class="break-words text-red-400">
<ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>
@endforeach
</ul>
</div>
@endif

<div>
<label for="name">Name</label>
<input id="name" name="name" @error('name') aria-invalid="true" aria-describedby="brand-name-error" @enderror value="{{ $form['name'] ?? '' }}" required class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">@error('name')<p id="brand-name-error" class="text-red-400">{{ $message }}</p>@enderror</div>
<div>
<label for="code">Code</label>
<input id="code" name="code" @error('code') aria-invalid="true" aria-describedby="brand-code-error" @enderror value="{{ $form['code'] ?? '' }}" required class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">@error('code')<p id="brand-code-error" class="text-red-400">{{ $message }}</p>@enderror</div>
