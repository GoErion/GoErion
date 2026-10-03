@if($errors->any())<div role="alert" class="break-words text-red-400">
<ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>
@endforeach
</ul>
</div>
@endif

<div>
<label for="name">Product name</label>
<input id="name" name="name" @error('name') aria-invalid="true" aria-describedby="product-name-error" @enderror value="{{ $form['name'] ?? '' }}" required maxlength="255" class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">@error('name')<p id="product-name-error" class="text-red-400">{{ $message }}</p>@enderror</div>
<div>
<label for="description">Description</label>
<textarea id="description" name="description" @error('description') aria-invalid="true" aria-describedby="product-description-error" @enderror rows="4" class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">{{ $form['description'] ?? '' }}</textarea>@error('description')<p id="product-description-error" class="text-red-400">{{ $message }}</p>@enderror</div>
<div>
<label for="category_id">Category</label>
<select id="category_id" name="category_id" @error('category_id') aria-invalid="true" aria-describedby="product-category_id-error" @enderror required class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">
<option value="">Select category</option>@foreach($categories as $option)<option value="{{ $option->id }}" @selected(($form['category_id'] ?? '') == $option->id)>{{ $option->name }}{{ $option->is_active ? '' : ' (Archived — current)' }}</option>
@endforeach
</select>@error('category_id')<p id="product-category_id-error" class="text-red-400">{{ $message }}</p>@enderror</div>
<div>
<label for="brand_id">Brand (optional)</label>
<select id="brand_id" name="brand_id" @error('brand_id') aria-invalid="true" aria-describedby="product-brand_id-error" @enderror  class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">
<option value="">No Brand</option>@foreach($brands as $option)<option value="{{ $option->id }}" @selected(($form['brand_id'] ?? '') == $option->id)>{{ $option->name }}{{ $option->is_active ? '' : ' (Archived — current)' }}</option>
@endforeach
</select>@error('brand_id')<p id="product-brand_id-error" class="text-red-400">{{ $message }}</p>@enderror</div>
<div>
<label for="unit_id">Unit</label>
<select id="unit_id" name="unit_id" @error('unit_id') aria-invalid="true" aria-describedby="product-unit_id-error" @enderror required class="mt-2 w-full min-w-0 rounded-lg border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:ring-2">
<option value="">Select unit</option>@foreach($units as $option)<option value="{{ $option->id }}" @selected(($form['unit_id'] ?? '') == $option->id)>{{ $option->name }}{{ $option->is_active ? '' : ' (Archived — current)' }}</option>
@endforeach
</select>@error('unit_id')<p id="product-unit_id-error" class="text-red-400">{{ $message }}</p>@enderror</div>
