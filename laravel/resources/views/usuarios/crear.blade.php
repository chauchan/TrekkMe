@extends('layouts.app')
@section('titulo', 'Nuevo usuario')
@section('contenido')
    <h1 class="text-2xl font-bold mb-4">Nuevo usuario</h1>
    <form class="card bg-base-100 shadow card-body max-w-2xl grid gap-3" method="POST" action="/usuarios">
        @csrf
        <label class="flex flex-col gap-1"><span class="text-sm font-semibold">Nombre</span><input class="input w-full" name="nombre" value="{{ old('nombre') }}" required></label>
        <label class="flex flex-col gap-1"><span class="text-sm font-semibold">Email</span><input class="input w-full" type="email" name="email" value="{{ old('email') }}" required></label>
        <label class="flex flex-col gap-1"><span class="text-sm font-semibold">Rol</span><select class="select w-full" name="rol" required>
                @foreach ($roles as $rol)
                    <option value="{{ $rol->value }}" @selected(old('rol') === $rol->value)>{{ $rol->etiqueta() }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1"><span class="text-sm font-semibold">Jurisdicción (carabineros y rescatistas)</span><select class="select w-full" name="jurisdiccion_id">
                <option value="">— ninguna —</option>
                @foreach ($jurisdicciones as $j)
                    <option value="{{ $j['id'] }}" @selected(old('jurisdiccion_id') === $j['id'])>{{ $j['nombre'] }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1"><span class="text-sm font-semibold">Contraseña</span><input class="input w-full" type="password" name="password" required minlength="8"></label>
        <div><button class="btn btn-primary" type="submit">Guardar</button> <a class="btn btn-ghost" href="/usuarios">Cancelar</a></div>
    </form>
@endsection
