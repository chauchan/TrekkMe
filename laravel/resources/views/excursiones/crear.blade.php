@extends('layouts.app')
@section('titulo', 'Nueva excursión')
@section('contenido')
    @php
        $campo = 'input w-full';
        $etiqueta = 'flex flex-col gap-1';
        $texto = 'text-sm font-semibold';
    @endphp

    <h1 class="text-2xl font-bold mb-4">Nueva excursión</h1>

    <form id="wizard" class="card bg-base-100 shadow max-w-3xl" method="POST" action="/excursiones" novalidate>
        @csrf
        <div class="card-body gap-6">
            <ul class="steps w-full" id="indicador">
                @foreach (['Excursionista', 'Acompañantes', 'Excursión', 'Contacto'] as $i => $nombre)
                    <li class="step cursor-pointer text-sm" data-ir="{{ $i }}">{{ $nombre }}</li>
                @endforeach
            </ul>

            {{-- Paso 1: excursionista --}}
            <section data-paso class="grid gap-4 sm:grid-cols-2">
                <h2 class="sm:col-span-2 text-lg font-semibold">Datos del excursionista</h2>
                <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Nombre</span><input class="{{ $campo }}" name="excursionista" value="{{ old('excursionista') }}" required></label>
                <label class="{{ $etiqueta }}"><span class="{{ $texto }}">RUT</span><input class="{{ $campo }}" name="rut" value="{{ old('rut') }}" required></label>
                <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Teléfono</span><input class="{{ $campo }}" name="telefono" value="{{ old('telefono') }}" required></label>
                <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Edad</span><input class="{{ $campo }}" type="number" name="edad" min="1" max="120" value="{{ old('edad') }}" required></label>
                <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Experiencia</span>
                    <select class="select w-full" name="experiencia" required>
                        @foreach (['Principiante', 'Intermedio', 'Avanzado'] as $nivel)
                            <option @selected(old('experiencia') === $nivel)>{{ $nivel }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Condiciones médicas</span><input class="{{ $campo }}" name="condiciones_medicas" value="{{ old('condiciones_medicas', 'Ninguna') }}" required></label>
            </section>

            {{-- Paso 2: acompañantes --}}
            <section data-paso hidden class="grid gap-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Acompañantes <span class="text-sm font-normal opacity-60">(opcional)</span></h2>
                    <button type="button" id="agregar" class="btn btn-secondary btn-sm">+ Agregar acompañante</button>
                </div>
                <p id="sin-acompanantes" class="rounded-box border border-dashed border-base-300 p-6 text-center text-sm opacity-60">
                    Sin acompañantes. Usa "Agregar acompañante" si va más gente.
                </p>
                <div id="acompanantes" class="grid gap-4"></div>
            </section>

            {{-- Paso 3: excursión --}}
            <section data-paso hidden class="grid gap-4 sm:grid-cols-2">
                <h2 class="sm:col-span-2 text-lg font-semibold">Datos de la excursión</h2>
                <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Sendero</span>
                    <select class="select w-full" name="sendero_id" required>
                        @foreach ($senderos as $s)
                            <option value="{{ $s['id'] }}" @selected(old('sendero_id') === $s['id'])>{{ $s['nombre'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Estado</span>
                    <select class="select w-full" name="estado" required>
                        @foreach ($estados as $estado)
                            <option value="{{ $estado->value }}" @selected(old('estado', 'planificada') === $estado->value)>{{ $estado->etiqueta() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Salida</span><input class="{{ $campo }}" type="datetime-local" name="salida_at" value="{{ old('salida_at') }}" required></label>
                <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Retorno</span><input class="{{ $campo }}" type="datetime-local" name="retorno_at" value="{{ old('retorno_at') }}" required></label>
                <label class="{{ $etiqueta }} sm:col-span-2"><span class="{{ $texto }}">Rescatista asignado <span class="font-normal opacity-60">(opcional)</span></span>
                    <select class="select w-full" name="rescatista_id">
                        <option value="">— ninguno —</option>
                        @foreach ($rescatistas as $r)
                            <option value="{{ $r['id'] }}" @selected(old('rescatista_id') === $r['id'])>{{ $r['nombre'] }} ({{ $r['id'] }})</option>
                        @endforeach
                    </select>
                </label>
                <label class="{{ $etiqueta }} sm:col-span-2"><span class="{{ $texto }}">Detalle <span class="font-normal opacity-60">(opcional)</span></span><textarea class="textarea w-full" name="detalle" rows="3">{{ old('detalle') }}</textarea></label>

                <div class="sm:col-span-2 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-box border border-base-300 p-4 grid gap-3 content-start">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" id="lleva-carpa" class="checkbox checkbox-primary rounded-md" name="lleva_carpa" value="1" @checked(old('lleva_carpa'))>
                            <span class="font-semibold">Lleva carpa</span>
                        </label>
                        <label id="campo-color" class="{{ $etiqueta }}" hidden>
                            <span class="{{ $texto }}">Color de carpa <span class="font-normal opacity-60">(opcional)</span></span>
                            <input class="{{ $campo }}" name="color_carpa" value="{{ old('color_carpa') }}">
                        </label>
                    </div>
                    <div class="rounded-box border border-base-300 p-4 content-start">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" class="checkbox checkbox-primary rounded-md" name="lleva_linterna" value="1" @checked(old('lleva_linterna'))>
                            <span class="font-semibold">Lleva linterna</span>
                        </label>
                    </div>
                </div>
            </section>

            {{-- Paso 4: contacto y ubicación --}}
            <section data-paso hidden class="grid gap-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <h2 class="sm:col-span-2 text-lg font-semibold">Contacto de emergencia</h2>
                    <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Nombre</span><input class="{{ $campo }}" name="contacto_nombre" value="{{ old('contacto_nombre') }}" required></label>
                    <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Teléfono</span><input class="{{ $campo }}" name="contacto_telefono" value="{{ old('contacto_telefono') }}" required></label>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <h2 class="sm:col-span-3 text-lg font-semibold">Última ubicación <span class="text-sm font-normal opacity-60">(opcional)</span></h2>
                    <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Latitud</span><input class="{{ $campo }}" type="number" step="any" name="ultima_lat" value="{{ old('ultima_lat') }}"></label>
                    <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Longitud</span><input class="{{ $campo }}" type="number" step="any" name="ultima_lng" value="{{ old('ultima_lng') }}"></label>
                    <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Batería (%)</span><input class="{{ $campo }}" type="number" name="bateria" min="0" max="100" value="{{ old('bateria') }}"></label>
                </div>
            </section>

            <div class="flex items-center justify-between border-t border-base-300 pt-4">
                <a href="/excursiones" class="btn btn-ghost">Cancelar</a>
                <div class="flex gap-2">
                    <button type="button" id="anterior" class="btn btn-outline">Anterior</button>
                    <button type="button" id="siguiente" class="btn btn-primary">Siguiente</button>
                    <button type="submit" id="guardar" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </form>

    <template id="tpl-acompanante">
        <div class="acompanante rounded-box border border-base-300 p-4 grid gap-3 sm:grid-cols-3">
            <div class="sm:col-span-3 flex items-center justify-between">
                <h3 class="titulo font-semibold"></h3>
                <button type="button" class="quitar btn btn-ghost btn-sm btn-square text-error" title="Quitar acompañante" aria-label="Quitar acompañante">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
            <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Nombre</span><input class="{{ $campo }}" data-campo="nombre" required></label>
            <label class="{{ $etiqueta }}"><span class="{{ $texto }}">RUT</span><input class="{{ $campo }}" data-campo="rut" required></label>
            <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Edad</span><input class="{{ $campo }}" type="number" min="0" max="120" data-campo="edad" required></label>
            <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Teléfono <span class="font-normal opacity-60">(opcional)</span></span><input class="{{ $campo }}" data-campo="telefono"></label>
            <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Experiencia <span class="font-normal opacity-60">(opcional)</span></span>
                <select class="select w-full" data-campo="experiencia">
                    <option value="">—</option>
                    <option>Principiante</option>
                    <option>Intermedio</option>
                    <option>Avanzado</option>
                </select>
            </label>
            <label class="{{ $etiqueta }}"><span class="{{ $texto }}">Condiciones médicas</span><input class="{{ $campo }}" data-campo="condicionesMedicas" value="Ninguna"></label>
        </div>
    </template>

    <script>
        (() => {
            const form = document.getElementById('wizard');
            const pasos = [...form.querySelectorAll('[data-paso]')];
            const indicador = [...document.querySelectorAll('#indicador li')];
            const btnAnt = document.getElementById('anterior');
            const btnSig = document.getElementById('siguiente');
            const btnGuardar = document.getElementById('guardar');
            let actual = 0;

            function mostrar(n) {
                actual = n;
                pasos.forEach((p, i) => { p.hidden = i !== n; });
                indicador.forEach((li, i) => li.classList.toggle('step-primary', i <= n));
                btnAnt.style.visibility = n === 0 ? 'hidden' : 'visible';
                btnSig.hidden = n === pasos.length - 1;
                btnGuardar.hidden = n !== pasos.length - 1;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            function pasoValido(n) {
                for (const campo of pasos[n].querySelectorAll('input, select, textarea')) {
                    if (!campo.checkValidity()) {
                        mostrar(n);
                        campo.reportValidity();
                        return false;
                    }
                }
                return true;
            }

            function ir(n) {
                if (n > actual) {
                    for (let i = actual; i < n; i++) if (!pasoValido(i)) return;
                }
                mostrar(n);
            }

            btnAnt.addEventListener('click', () => ir(actual - 1));
            btnSig.addEventListener('click', () => ir(actual + 1));
            indicador.forEach((li) => li.addEventListener('click', () => ir(Number(li.dataset.ir))));

            form.addEventListener('submit', (e) => {
                for (let i = 0; i < pasos.length; i++) {
                    if (!pasoValido(i)) { e.preventDefault(); return; }
                }
            });
            form.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.target.tagName === 'INPUT' && actual < pasos.length - 1) {
                    e.preventDefault();
                    ir(actual + 1);
                }
            });

            // Carpa: el color solo aparece si lleva carpa
            const carpa = document.getElementById('lleva-carpa');
            const campoColor = document.getElementById('campo-color');
            const syncCarpa = () => campoColor.hidden = !carpa.checked;
            carpa.addEventListener('change', syncCarpa);
            syncCarpa();

            // Acompañantes
            const lista = document.getElementById('acompanantes');
            const vacio = document.getElementById('sin-acompanantes');
            const tpl = document.getElementById('tpl-acompanante');
            let contador = 0;

            function renumerar() {
                const items = lista.querySelectorAll('.acompanante');
                items.forEach((el, i) => el.querySelector('.titulo').textContent = `Acompañante ${i + 1}`);
                vacio.hidden = items.length > 0;
            }

            function agregar(valores = {}) {
                const i = contador++;
                const nodo = tpl.content.firstElementChild.cloneNode(true);
                nodo.querySelectorAll('[data-campo]').forEach((campo) => {
                    const nombre = campo.dataset.campo;
                    campo.name = `acompanantes[${i}][${nombre}]`;
                    if (valores[nombre] !== undefined && valores[nombre] !== null) campo.value = valores[nombre];
                });
                nodo.querySelector('.quitar').addEventListener('click', () => { nodo.remove(); renumerar(); });
                lista.appendChild(nodo);
                renumerar();
            }

            document.getElementById('agregar').addEventListener('click', () => agregar());
            Object.values(@json(old('acompanantes', []))).forEach((a) => agregar(a));

            mostrar(0);
        })();
    </script>
@endsection
