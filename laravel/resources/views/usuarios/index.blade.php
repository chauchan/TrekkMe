@extends('layouts.app')
@section('titulo', 'Usuarios')
@section('contenido')
    <div class="barra">
        <h1>Usuarios registrados ({{ $usuarios->count() }})</h1>
        <a class="btn" href="/usuarios/crear">+ Nuevo usuario</a>
    </div>

    <table>
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Casos asignados</th><th>Bitácoras</th><th>Registrado</th></tr>
        </thead>
        <tbody>
            @forelse ($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->id }}</td>
                    <td>{{ $usuario->nombre }}</td>
                    <td>{{ $usuario->email }}</td>
                    <td>{{ $usuario->rol->etiqueta() }}</td>
                    <td>{{ $usuario->excursiones_count }}</td>
                    <td>{{ $usuario->bitacoras_count }}</td>
                    <td>{{ $usuario->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="vacio">No hay usuarios registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
