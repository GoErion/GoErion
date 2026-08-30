@props(['company','companies'])
<x-dashboard :company="$company">
    <div>
        {{  $slot }}
    </div>
</x-dashboard>