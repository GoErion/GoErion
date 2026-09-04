@php($editingCompany = $company ?? null)

<fieldset class="grid gap-5">
    <legend class="text-lg font-semibold text-white">Company identity</legend>
    <div>
        <label for="company_name" class="mb-2 block font-semibold text-slate-200">Company name <span class="text-pink-400" aria-hidden="true">*</span></label>
        <input id="company_name" name="company_name" type="text" required autocomplete="organization" value="{{ old('company_name', $editingCompany?->company_name) }}" class="block w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white outline-none transition focus:border-pink-500 focus:ring-2 focus:ring-pink-500/30 @error('company_name') border-red-500 @enderror">
        <p class="mt-2 text-sm text-slate-400">This is the name shown throughout GoErion.</p>
        @error('company_name')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div class="grid gap-5 md:grid-cols-2">
        @foreach (['legal_name' => 'Legal name', 'registration_number' => 'Registration number', 'tax_number' => 'Tax number'] as $field => $label)
            <div @class(['md:col-span-2' => $field === 'legal_name'])>
                <label for="{{ $field }}" class="mb-2 block font-semibold text-slate-200">{{ $label }} <span class="font-normal text-slate-500">(optional)</span></label>
                <input id="{{ $field }}" name="{{ $field }}" type="text" value="{{ old($field, $editingCompany?->{$field}) }}" class="block w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white outline-none transition focus:border-pink-500 focus:ring-2 focus:ring-pink-500/30 @error($field) border-red-500 @enderror">
                @error($field)<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>
</fieldset>
<fieldset class="grid gap-5 border-t border-slate-800 pt-6">
    <legend class="text-lg font-semibold text-white">Contact details <span class="font-normal text-slate-500">(optional)</span></legend>
    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label for="email" class="mb-2 block font-semibold text-slate-200">Company email</label>
            <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email', $editingCompany?->email) }}" class="block w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white outline-none transition focus:border-pink-500 focus:ring-2 focus:ring-pink-500/30 @error('email') border-red-500 @enderror">
            @error('email')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="phone" class="mb-2 block font-semibold text-slate-200">Phone</label>
            <input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone', $editingCompany?->phone) }}" class="block w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white outline-none transition focus:border-pink-500 focus:ring-2 focus:ring-pink-500/30 @error('phone') border-red-500 @enderror">
            @error('phone')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label for="address" class="mb-2 block font-semibold text-slate-200">Address</label>
        <textarea id="address" name="address" rows="4" autocomplete="street-address" class="block w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white outline-none transition focus:border-pink-500 focus:ring-2 focus:ring-pink-500/30 @error('address') border-red-500 @enderror">{{ old('address', $editingCompany?->address) }}</textarea>
        @error('address')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
</fieldset>
