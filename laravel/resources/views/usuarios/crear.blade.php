@extends('layouts.app')
@section('titulo', 'Nuevo usuario')
@section('contenido')
    <h1>Nuevo usuario</h1>
    <form class="alta" method="POST" action="/usuarios">
        @csrf
        <label>Nombre <input name="nombre" value="{{ old('nombre') }}" required></label>
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required></label>
        <label>Rol
            <select name="rol" required>
                @foreach ($roles as $rol)
                    <option value="{{ $rol->value }}" @selected(old('rol') === $rol->value)>{{ $rol->etiqueta() }}</option>
                @endforeach
            </select>
        </label>
        <label>Jurisdicción (carabineros y rescatistas)
            <select name="jurisdiccion_id">
                <option value="">— ninguna —</option>
                @foreach ($jurisdicciones as $j)
                    <option value="{{ $j['id'] }}" @selected(old('jurisdiccion_id') === $j['id'])>{{ $j['nombre'] }}</option>
                @endforeach
            </select>
        </label>
        <label>Contraseña <input type="password" name="password" required minlength="8"></label>
        <div><button class="btn" type="submit">Guardar</button> <a href="/usuarios">Cancelar</a></div>
    </form>
@endsection
