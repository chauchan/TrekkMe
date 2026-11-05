@extends('layouts.app')
@section('titulo', 'Usuarios')
@section('contenido')
    <div class="barra">
        <h1>Usuarios registrados ({{ count($usuarios) }})</h1>
        <a class="btn" href="/usuarios/crear">+ Nuevo usuario</a>
    </div>

    <table>
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Jurisdicción</th><th>Casos asignados</th><th>Bitácoras</th><th>Actualizado</th></tr>
        </thead>
        <tbody>
            @forelse ($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario['id'] }}</td>
                    <td>{{ $usuario['nombre'] }}</td>
                    <td>{{ $usuario['email'] }}</td>
                    <td>{{ $usuario['rol']?->etiqueta() ?? '—' }}</td>
                    <td>{{ $jurisdicciones[$usuario['jurisdiccionId']] ?? '—' }}</td>
                    <td>{{ $casos[$usuario['id']] ?? 0 }}</td>
                    <td>{{ $registros[$usuario['id']] ?? 0 }}</td>
                    <td>{{ $usuario['_actualizado'] ? \Illuminate\Support\Carbon::parse($usuario['_actualizado'])->format('d/m/Y H:i') : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="vacio">No hay usuarios registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
