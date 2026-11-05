@extends('layouts.app')
@section('titulo', 'Excursiones')
@section('contenido')
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Excursiones ({{ count($excursiones) }})</h1>
        <a class="btn btn-primary" href="/excursiones/crear">+ Nueva excursión</a>
    </div>

    <div class="overflow-x-auto bg-base-100 rounded-box shadow"><table class="table table-zebra">
        <thead>
            <tr><th>ID</th><th>Excursionista</th><th>RUT</th><th>Ruta</th><th>Salida</th><th>Retorno</th><th>Estado</th><th>Rescatista</th><th>Acomp.</th><th>Bitácoras</th></tr>
        </thead>
        <tbody>
            @forelse ($excursiones as $e)
                <tr>
                    <td>{{ $e['id'] }}</td>
                    <td>{{ $e['excursionista'] }}</td>
                    <td>{{ $e['rut'] }}</td>
                    <td>{{ $e['ruta'] }}</td>
                    <td>{{ $e['salidaAt']?->format('d/m/Y H:i') }}</td>
                    <td>{{ $e['retornoAt']?->format('d/m/Y H:i') }}</td>
                    <td><x-estado-badge :estado="$e['estado']" :texto="$e['estadoTexto']" /></td>
                    <td>{{ ($e['rescatistaId'] ?? '') !== '' ? ($nombres[$e['rescatistaId']] ?? $e['rescatistaId']) : '—' }}</td>
                    <td>{{ count($e['acompanantes'] ?? []) }}</td>
                    <td>{{ $registros[$e['id']] ?? 0 }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center opacity-60">No hay excursiones.</td></tr>
            @endforelse
        </tbody>
    </table></div>
@endsection
