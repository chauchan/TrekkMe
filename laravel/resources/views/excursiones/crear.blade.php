@extends('layouts.app')
@section('titulo', 'Nueva excursión')
@section('contenido')
    <h1>Nueva excursión</h1>
    <form class="alta" method="POST" action="/excursiones">
        @csrf
        <fieldset>
            <legend>Excursionista</legend>
            <label>Nombre <input name="excursionista" value="{{ old('excursionista') }}" required></label>
            <label>RUT <input name="rut" value="{{ old('rut') }}" required></label>
            <label>Teléfono <input name="telefono" value="{{ old('telefono') }}" required></label>
            <label>Edad <input type="number" name="edad" min="1" max="120" value="{{ old('edad') }}" required></label>
            <label>Experiencia
                <select name="experiencia" required>
                    @foreach (['Principiante', 'Intermedio', 'Avanzado'] as $nivel)
                        <option @selected(old('experiencia') === $nivel)>{{ $nivel }}</option>
                    @endforeach
                </select>
            </label>
            <label>Condiciones médicas <input name="condiciones_medicas" value="{{ old('condiciones_medicas', 'Ninguna') }}" required></label>
        </fieldset>

        <fieldset>
            <legend>Acompañantes (opcional)</legend>
            <div id="acompanantes"></div>
            <button type="button" class="btn" onclick="agregarAcompanante()">+ Agregar acompañante</button>
        </fieldset>

        <fieldset>
            <legend>Excursión</legend>
            <label>Sendero
                <select name="sendero_id" required>
                    @foreach ($senderos as $s)
                        <option value="{{ $s['id'] }}" @selected(old('sendero_id') === $s['id'])>{{ $s['nombre'] }}</option>
                    @endforeach
                </select>
            </label>
            <label>Salida <input type="datetime-local" name="salida_at" value="{{ old('salida_at') }}" required></label>
            <label>Retorno <input type="datetime-local" name="retorno_at" value="{{ old('retorno_at') }}" required></label>
            <label>Estado
                <select name="estado" required>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->value }}" @selected(old('estado', 'planificada') === $estado->value)>{{ $estado->etiqueta() }}</option>
                    @endforeach
                </select>
            </label>
            <label>Rescatista asignado
                <select name="rescatista_id">
                    <option value="">— ninguno —</option>
                    @foreach ($rescatistas as $r)
                        <option value="{{ $r['id'] }}" @selected(old('rescatista_id') === $r['id'])>{{ $r['nombre'] }} ({{ $r['id'] }})</option>
                    @endforeach
                </select>
            </label>
            <label>Detalle <textarea name="detalle" rows="3">{{ old('detalle') }}</textarea></label>
            <label class="check"><input type="checkbox" name="lleva_carpa" value="1" @checked(old('lleva_carpa'))> Lleva carpa</label>
            <label>Color de carpa <input name="color_carpa" value="{{ old('color_carpa') }}"></label>
            <label class="check"><input type="checkbox" name="lleva_linterna" value="1" @checked(old('lleva_linterna'))> Lleva linterna</label>
        </fieldset>

        <fieldset>
            <legend>Contacto de emergencia</legend>
            <label>Nombre <input name="contacto_nombre" value="{{ old('contacto_nombre') }}" required></label>
            <label>Teléfono <input name="contacto_telefono" value="{{ old('contacto_telefono') }}" required></label>
        </fieldset>

        <fieldset>
            <legend>Última ubicación (opcional)</legend>
            <label>Latitud <input type="number" step="any" name="ultima_lat" value="{{ old('ultima_lat') }}"></label>
            <label>Longitud <input type="number" step="any" name="ultima_lng" value="{{ old('ultima_lng') }}"></label>
            <label>Batería (%) <input type="number" name="bateria" min="0" max="100" value="{{ old('bateria') }}"></label>
        </fieldset>

        <div><button class="btn" type="submit">Guardar</button> <a href="/excursiones">Cancelar</a></div>
    </form>

    <script>
        let n = 0;
        function agregarAcompanante() {
            const i = n++;
            const div = document.createElement('div');
            div.className = 'fila-acomp';
            div.innerHTML = `
                <input name="acompanantes[${i}][nombre]" placeholder="Nombre" required>
                <input name="acompanantes[${i}][rut]" placeholder="RUT" required>
                <input type="number" name="acompanantes[${i}][edad]" placeholder="Edad" required>
                <input name="acompanantes[${i}][condicionesMedicas]" placeholder="Condiciones médicas" value="Ninguna">
                <input name="acompanantes[${i}][telefono]" placeholder="Teléfono">
                <input name="acompanantes[${i}][experiencia]" placeholder="Experiencia">`;
            document.getElementById('acompanantes').appendChild(div);
        }
    </script>
@endsection
