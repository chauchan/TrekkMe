@extends('layouts.app')
@section('titulo', 'Bitácoras')
@section('contenido')
    <div class="barra">
        <h1>Bitácoras ({{ $bitacoras->count() }})</h1>
        <a class="btn" href="/bitacoras/crear">+ Nuevo registro</a>
    </div>

    <table>
        <thead>
            <tr><th>ID</th><th>Excursión</th><th>Rescatista</th><th>Ruta</th><th>Inicio</th><th>Fin</th><th>Texto</th><th>Versión</th></tr>
        </thead>
        <tbody>
            @forelse ($bitacoras as $b)
                <tr>
                    <td>{{ $b->id }}</td>
                    <td>{{ $b->excursion_id }} · {{ $b->excursion->excursionista }}</td>
                    <td>{{ $b->rescatista->nombre }}</td>
                    <td>{{ $b->ruta }}</td>
                    <td>{{ $b->hora_inicio->format('d/m/Y H:i') }}</td>
                    <td>{{ $b->hora_fin?->format('d/m/Y H:i') ?? 'En curso' }}</td>
                    <td>{{ $b->texto }}</td>
                    <td>{{ $b->version }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="vacio">No hay registros.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
