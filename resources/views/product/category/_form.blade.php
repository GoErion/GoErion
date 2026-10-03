@if($errors->any())<div role="alert" class="break-words text-red-400">
<ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>
@endforeach
</ul>
</div>
@endif

<div>
<label for="name">Name</label>
<input id="name" name="name" @error('name') aria-invalid="true" aria-describedby="category-name-error" @enderror value="{{ $form['name'] ?? '' }}" required class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">@error('name')<p id="category-name-error" class="text-red-400">{{ $message }}</p>@enderror</div>
<div>
<label for="code">Code</label>
<input id="code" name="code" @error('code') aria-invalid="true" aria-describedby="category-code-error" @enderror value="{{ $form['code'] ?? '' }}" required class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">@error('code')<p id="category-code-error" class="text-red-400">{{ $message }}</p>@enderror</div>
<div>
<label for="parent_id">Parent Category</label>
<select id="parent_id" name="parent_id" aria-describedby="category-parent-help{{ $errors->has('parent_id') ? ' category-parent_id-error' : '' }}" @error('parent_id') aria-invalid="true" @enderror class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">
<option value="">None (root)</option>@foreach($categories as $option)<option value="{{ $option->id }}" @selected(($form['parent_id'] ?? '') == $option->id)>{{ $paths[$option->id]['path'] }}{{ $option->is_active ? '' : ' (Archived — current parent)' }}</option>
@endforeach
</select>@error('parent_id')<p id="category-parent_id-error" class="text-red-400">{{ $message }}</p>@enderror<p id="category-parent-help">Maximum three levels. Invalid moves are rejected without changing descendants.</p>
</div>
