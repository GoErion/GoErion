@props(['autocomplete', 'label', 'name', 'type' => 'text', 'value' => null])

@php($errorId = $name.'-error')

<div class="grid gap-2">
    <label for="{{ $name }}" class="text-sm font-semibold text-slate-200">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}"
        @if ($type !== 'password' && $value !== null) value="{{ $value }}" @endif
        @error($name) aria-describedby="{{ $errorId }}" aria-invalid="true" @enderror
        {{ $attributes->merge(['class' => 'w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-100 placeholder:text-slate-600 focus-visible:border-pink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-500/30']) }}>
    @error($name)
        <p id="{{ $errorId }}" class="text-sm font-medium text-red-400">{{ $message }}</p>
    @enderror
</div>
