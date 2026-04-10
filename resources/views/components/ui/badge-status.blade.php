@props([
    'status',
    'label' => null,
    'map' => [], // ['activo' => 'success', 'vencido' => 'danger', ...]
])

@php
    $tone = $map[$status] ?? match (strtolower((string) $status)) {
        'activo', 'disponible', 'operativo', 'vigente', 'aprobado', 'completado' => 'success',
        'mantenimiento', 'pendiente', 'en_proceso', 'por_vencer' => 'warning',
        'inactivo', 'vencido', 'baja', 'rechazado', 'critico' => 'danger',
        'reservado', 'asignado', 'en_revision' => 'info',
        default => 'slate',
    };

    $classes = [
        'success' => 'bg-brand-50 text-brand-700 ring-brand-200 dark:bg-brand-950/50 dark:text-brand-300 dark:ring-brand-900',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:ring-amber-900',
        'danger'  => 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-950/50 dark:text-red-300 dark:ring-red-900',
        'info'    => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950/50 dark:text-sky-300 dark:ring-sky-900',
        'slate'   => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700',
    ][$tone];

    $dot = [
        'success' => 'bg-brand-500',
        'warning' => 'bg-amber-500',
        'danger'  => 'bg-red-500',
        'info'    => 'bg-sky-500',
        'slate'   => 'bg-slate-400',
    ][$tone];

    $text = $label ?? ucfirst(str_replace('_', ' ', (string) $status));
@endphp

{{-- Uso: <x-ui.badge-status :status="$vehiculo->estado" /> --}}

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset', $classes]) }}>
    <span class="size-1.5 rounded-full {{ $dot }}"></span>
    {{ $text }}
</span>
