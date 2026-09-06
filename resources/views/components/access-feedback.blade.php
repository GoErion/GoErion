@if (session('success'))
    <div role="status" class="my-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-200">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div role="alert" class="my-4 rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-200">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div role="alert" class="my-4 rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-200">
        <p class="font-semibold">Please review the following:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
