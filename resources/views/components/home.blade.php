@props(['company','companies'])
<x-dashboard>
      {{-- Sidebar --}}
        <div class="hidden md:block md:w-1/5">
            <x-sidebar
                :company="$company"
                :companies="$companies"
            />
        </div>
    <div>
        {{  $slot }}
    </div>
</x-dashboard>