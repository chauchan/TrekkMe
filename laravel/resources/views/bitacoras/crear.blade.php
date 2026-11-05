@extends('layouts.app')
@section('titulo', 'Nuevo registro de bitácora')
@section('contenido')
    <h1 class="text-2xl font-bold mb-4">Nuevo registro de bitácora</h1>
    <form class="card bg-base-100 shadow card-body max-w-2xl grid gap-3" method="POST" action="/bitacoras">
        @csrf
        <label class="flex flex-col gap-1"><span class="text-sm font-semibold">Excursión</span><select class="select w-full" name="excursion_id" required>
                @foreach ($excursiones as $e)
                    <option value="{{ $e['id'] }}" @selected(old('excursion_id') === $e['id'])>{{ $e['id'] }} · {{ $e['excursionista'] }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1"><span class="text-sm font-semibold">Rescatista</span><select class="select w-full" name="rescatista_id" required>
                @foreach ($rescatistas as $r)
                    <option value="{{ $r['id'] }}" @selected(old('rescatista_id') === $r['id'])>{{ $r['nombre'] }} ({{ $r['id'] }})</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1"><span class="text-sm font-semibold">Sendero</span><select class="select w-full" name="sendero_id">
                <option value="">Fuera de sendero</option>
                @foreach ($senderos as $s)
                    <option value="{{ $s['id'] }}" @selected(old('sendero_id') === $s['id'])>{{ $s['nombre'] }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1"><span class="text-sm font-semibold">Hora de inicio</span><input class="input w-full" type="datetime-local" name="hora_inicio" value="{{ old('hora_inicio') }}" required></label>
        <label class="flex flex-col gap-1"><span class="text-sm font-semibold">Hora de fin (opcional)</span><input class="input w-full" type="datetime-local" name="hora_fin" value="{{ old('hora_fin') }}"></label>
        <label class="flex flex-col gap-1"><span class="text-sm font-semibold">Texto</span><textarea class="textarea w-full" name="texto" rows="5" required>{{ old('texto') }}</textarea></label>
        <div><button class="btn btn-primary" type="submit">Guardar</button> <a class="btn btn-ghost" href="/bitacoras">Cancelar</a></div>
    </form>
@endsection
