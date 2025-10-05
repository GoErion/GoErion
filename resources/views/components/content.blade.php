<section class="flex-1">
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-500/10 border border-green-600 text-green-400 px-4 py-3">
            {{ session('success') }}
        </div>
    @endif
        @if (session('success_message'))
            <div class="mb-4 rounded-lg bg-green-500/10 border border-green-600 text-green-400 px-4 py-3">
                {{ session('success_message') }}
            </div>
        @endif
    <div class="flex flex-wrap justify-start w-full gap-6 mb-4">
        {{-- cards section--}}
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
        <x-card/>
    </div>
</section>
