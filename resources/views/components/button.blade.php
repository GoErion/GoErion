@props([
    'type'=>'submit'
])
<button type="{{ $type }}" {{ $attributes->class(['px-4 py-2 rounded font-bold']) }}>
    {{ $slot }}
</button>
