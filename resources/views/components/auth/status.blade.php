@if (session('status'))
    <div role="status" {{ $attributes->merge(['class' => 'rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-300']) }}>
        {{ session('status') }}
    </div>
@endif
