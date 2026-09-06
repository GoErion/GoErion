@props(['href', 'secondary' => false])
<a href="{{ $href }}" {{ $attributes->class([
    'inline-flex min-h-11 items-center justify-center gap-2 rounded-md px-5 py-3 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-800 motion-safe:transition-colors',
    'bg-amber-900 text-white shadow-sm hover:bg-amber-950' => ! $secondary,
    'border border-slate-300 bg-white text-slate-800 hover:bg-slate-100' => $secondary,
]) }}>{{ $slot }}</a>
