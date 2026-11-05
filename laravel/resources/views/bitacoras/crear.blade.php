@extends('layouts.app')
@section('titulo', 'Nuevo registro de bitácora')
@section('contenido')
    <h1>Nuevo registro de bitácora</h1>
    <form class="alta" method="POST" action="/bitacoras">
        @csrf
        <label>Excursión
            <select name="excursion_id" required>
                @foreach ($excursiones as $e)
                    <option value="{{ $e['id'] }}" @selected(old('excursion_id') === $e['id'])>{{ $e['id'] }} · {{ $e['excursionista'] }}</option>
                @endforeach
            </select>
        </label>
        <label>Rescatista
            <select name="rescatista_id" required>
                @foreach ($rescatistas as $r)
                    <option value="{{ $r['id'] }}" @selected(old('rescatista_id') === $r['id'])>{{ $r['nombre'] }} ({{ $r['id'] }})</option>
                @endforeach
            </select>
        </label>
        <label>Sendero
            <select name="sendero_id">
                <option value="">Fuera de sendero</option>
                @foreach ($senderos as $s)
                    <option value="{{ $s['id'] }}" @selected(old('sendero_id') === $s['id'])>{{ $s['nombre'] }}</option>
                @endforeach
            </select>
        </label>
        <label>Hora de inicio <input type="datetime-local" name="hora_inicio" value="{{ old('hora_inicio') }}" required></label>
        <label>Hora de fin (opcional) <input type="datetime-local" name="hora_fin" value="{{ old('hora_fin') }}"></label>
        <label>Texto <textarea name="texto" rows="5" required>{{ old('texto') }}</textarea></label>
        <div><button class="btn" type="submit">Guardar</button> <a href="/bitacoras">Cancelar</a></div>
    </form>
@endsection
