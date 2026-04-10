@props([
    'title',
    'description' => null,
])

<div class="mb-2 text-center">
    <h2 class="text-lg font-semibold text-white">{{ $title }}</h2>
    @if ($description)
        <p class="mt-1 text-sm text-slate-400">{{ $description }}</p>
    @endif
</div>
