@props(['estado', 'texto' => null])
@php
    $fondo = match ($estado?->value) {
        'planificada' => 'bg-secondary',
        'en_curso' => 'bg-primary',
        'en_alerta' => 'bg-warning',
        'en_operativo', 'cierre_pendiente' => 'bg-error',
        default => 'bg-neutral-500',
    };
@endphp
<span class="inline-block whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold leading-none text-white {{ $fondo }}">{{ $estado?->etiqueta() ?? $texto }}</span>
