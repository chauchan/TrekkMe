import 'package:flutter/material.dart';

import '../../core/api_exception.dart';
import '../../core/rut.dart';
import '../../data/excursion_repository.dart';
import '../../models/excursion.dart';
import '../../models/sendero.dart';
import 'acompanante_dialog.dart';

const _experiencias = ['Principiante', 'Intermedio', 'Avanzado'];

/// El retorno debe ser posterior a la salida (misma regla que valida Laravel).
@visibleForTesting
String? validarRetorno(DateTime salida, DateTime? retorno) {
  if (retorno == null) return 'Elige la hora de retorno.';
  if (!retorno.isAfter(salida)) {
    return 'El retorno debe ser posterior a la salida.';
  }
  return null;
}

String? _obligatorio(String? v, String mensaje, {int max = 255}) {
  final texto = v?.trim() ?? '';
  if (texto.isEmpty) return mensaje;
  if (texto.length > max) return 'Máximo $max caracteres.';
  return null;
}

String? _validarTelefono(String? v) {
  final texto = v?.trim() ?? '';
  if (texto.isEmpty) return 'Ingresa un teléfono.';
  if (!RegExp(r'^[+0-9 ()\-]{7,30}$').hasMatch(texto)) return 'Teléfono no válido.';
  return null;
}

/// Formulario para avisar una salida (F2). Al guardar devuelve la
/// [Excursion] creada, que queda `planificada`.
class AvisoPage extends StatefulWidget {
  const AvisoPage({super.key, required this.repo, DateTime Function()? ahora})
      : _ahora = ahora ?? DateTime.now;

  final ExcursionRepository repo;
  final DateTime Function() _ahora;

  @override
  State<AvisoPage> createState() => _AvisoPageState();
}

class _AvisoPageState extends State<AvisoPage> {
  final _formKey = GlobalKey<FormState>();

  final _contactoNombre = TextEditingController();
  final _contactoTelefono = TextEditingController();
  final _rut = TextEditingController();
  final _telefono = TextEditingController();
  final _edad = TextEditingController();
  final _condiciones = TextEditingController();
  final _detalle = TextEditingController();
  final _colorCarpa = TextEditingController();

  late Future<List<Sendero>> _senderos;
  Sendero? _sendero;
  late DateTime _salida;
  late DateTime _retorno;
  String _experiencia = _experiencias.first;
  bool _llevaCarpa = false;
  bool _llevaLinterna = false;
  final List<Acompanante> _acompanantes = [];

  bool _enviando = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final ahora = widget._ahora();
    // Por defecto: sale en la próxima hora en punto y vuelve 6 horas después
    _salida = DateTime(ahora.year, ahora.month, ahora.day, ahora.hour + 1);
    _retorno = _salida.add(const Duration(hours: 6));
    _senderos = widget.repo.senderos();
  }

  @override
  void dispose() {
    for (final c in [
      _contactoNombre, _contactoTelefono, _rut, _telefono,
      _edad, _condiciones, _detalle, _colorCarpa,
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  void _recargarSenderos() {
    setState(() {
      _senderos = widget.repo.senderos();
    });
  }

  Future<void> _agregarAcompanante() async {
    final nuevo = await pedirAcompanante(context);
    if (nuevo != null) setState(() => _acompanantes.add(nuevo));
  }

  Future<void> _enviar() async {
    if (_enviando || !_formKey.currentState!.validate()) return;

    setState(() {
      _enviando = true;
      _error = null;
    });

    try {
      final condiciones = _condiciones.text.trim();
      final creada = await widget.repo.crear(NuevaExcursion(
        sendero: _sendero!,
        salidaAt: _salida,
        retornoAt: _retorno,
        contactoNombre: _contactoNombre.text.trim(),
        contactoTelefono: _contactoTelefono.text.trim(),
        rut: Rut.formatear(_rut.text),
        telefono: _telefono.text.trim(),
        edad: int.parse(_edad.text.trim()),
        experiencia: _experiencia,
        condicionesMedicas: condiciones.isEmpty ? 'Ninguna' : condiciones,
        detalle: _detalle.text,
        llevaCarpa: _llevaCarpa,
        colorCarpa: _colorCarpa.text,
        llevaLinterna: _llevaLinterna,
        acompanantes: List.of(_acompanantes),
      ));
      if (mounted) Navigator.pop(context, creada);
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.mensaje);
    } catch (_) {
      if (mounted) setState(() => _error = 'No se pudo avisar la salida. Intenta de nuevo.');
    } finally {
      if (mounted) setState(() => _enviando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Avisar salida')),
      body: FutureBuilder<List<Sendero>>(
        future: _senderos,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text('No se pudieron cargar los senderos:\n${snapshot.error}',
                        textAlign: TextAlign.center),
                    const SizedBox(height: 12),
                    FilledButton(
                        onPressed: _recargarSenderos, child: const Text('Reintentar')),
                  ],
                ),
              ),
            );
          }
          return _formulario(snapshot.data!);
        },
      ),
    );
  }

  Widget _formulario(List<Sendero> senderos) {
    final tema = Theme.of(context);
    final hoy = widget._ahora();

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
        children: [
          _Seccion('Ruta y horario'),
          DropdownButtonFormField<Sendero>(
            key: const ValueKey('campo-sendero'),
            initialValue: _sendero,
            isExpanded: true,
            decoration: const InputDecoration(
              labelText: 'Sendero',
              border: OutlineInputBorder(),
            ),
            items: [
              for (final s in senderos)
                DropdownMenuItem(value: s, child: Text(s.nombre, overflow: TextOverflow.ellipsis)),
            ],
            onChanged: (s) => setState(() => _sendero = s),
            validator: (s) => s == null ? 'Elige un sendero.' : null,
          ),
          const SizedBox(height: 16),
          _CampoFechaHora(
            key: const ValueKey('campo-salida'),
            etiqueta: 'Salida',
            inicial: _salida,
            primera: DateTime(hoy.year, hoy.month, hoy.day),
            ultima: DateTime(hoy.year + 1, hoy.month, hoy.day),
            alCambiar: (d) => _salida = d,
            validator: (d) => d == null ? 'Elige la hora de salida.' : null,
          ),
          const SizedBox(height: 16),
          _CampoFechaHora(
            key: const ValueKey('campo-retorno'),
            etiqueta: 'Retorno comprometido',
            inicial: _retorno,
            primera: DateTime(hoy.year, hoy.month, hoy.day),
            ultima: DateTime(hoy.year + 1, hoy.month, hoy.day),
            alCambiar: (d) => _retorno = d,
            validator: (d) => validarRetorno(_salida, d),
          ),
          _Seccion('Contacto de confianza'),
          TextFormField(
            controller: _contactoNombre,
            textCapitalization: TextCapitalization.words,
            textInputAction: TextInputAction.next,
            decoration: const InputDecoration(
              labelText: 'Nombre (ej. Paula Díaz, madre)',
              border: OutlineInputBorder(),
            ),
            validator: (v) => _obligatorio(v, 'Ingresa el nombre del contacto.'),
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: _contactoTelefono,
            keyboardType: TextInputType.phone,
            textInputAction: TextInputAction.next,
            decoration: const InputDecoration(
              labelText: 'Teléfono del contacto',
              border: OutlineInputBorder(),
            ),
            validator: _validarTelefono,
          ),
          _Seccion('Tus datos'),
          TextFormField(
            controller: _rut,
            textInputAction: TextInputAction.next,
            decoration: const InputDecoration(
              labelText: 'RUT',
              hintText: '12.345.678-5',
              border: OutlineInputBorder(),
            ),
            validator: (v) => Rut.esValido(v ?? '') ? null : 'RUT no válido.',
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: _telefono,
            keyboardType: TextInputType.phone,
            textInputAction: TextInputAction.next,
            decoration: const InputDecoration(
              labelText: 'Tu teléfono',
              border: OutlineInputBorder(),
            ),
            validator: _validarTelefono,
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: _edad,
            keyboardType: TextInputType.number,
            textInputAction: TextInputAction.next,
            decoration: const InputDecoration(
              labelText: 'Edad',
              border: OutlineInputBorder(),
            ),
            validator: (v) {
              final edad = int.tryParse(v?.trim() ?? '');
              return (edad == null || edad < 1 || edad > 120) ? 'Edad entre 1 y 120.' : null;
            },
          ),
          const SizedBox(height: 16),
          DropdownButtonFormField<String>(
            key: const ValueKey('campo-experiencia'),
            initialValue: _experiencia,
            decoration: const InputDecoration(
              labelText: 'Experiencia',
              border: OutlineInputBorder(),
            ),
            items: [for (final e in _experiencias) DropdownMenuItem(value: e, child: Text(e))],
            onChanged: (e) => setState(() => _experiencia = e ?? _experiencia),
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: _condiciones,
            textInputAction: TextInputAction.next,
            decoration: const InputDecoration(
              labelText: 'Condición médica (opcional)',
              hintText: 'Ninguna',
              helperText: 'Solo la verá el rescatista asignado si hay una emergencia.',
              border: OutlineInputBorder(),
            ),
            validator: (v) => (v?.trim().length ?? 0) > 255 ? 'Máximo 255 caracteres.' : null,
          ),
          _Seccion('Equipo'),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            title: const Text('Llevo carpa'),
            value: _llevaCarpa,
            onChanged: (v) => setState(() => _llevaCarpa = v),
          ),
          if (_llevaCarpa)
            TextFormField(
              controller: _colorCarpa,
              textInputAction: TextInputAction.next,
              decoration: const InputDecoration(
                labelText: 'Color de la carpa (opcional)',
                border: OutlineInputBorder(),
              ),
            ),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            title: const Text('Llevo linterna'),
            value: _llevaLinterna,
            onChanged: (v) => setState(() => _llevaLinterna = v),
          ),
          _Seccion('Acompañantes'),
          if (_acompanantes.isEmpty)
            Text('Voy solo.', style: tema.textTheme.bodyMedium),
          for (var i = 0; i < _acompanantes.length; i++)
            ListTile(
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.person),
              title: Text(_acompanantes[i].nombre),
              subtitle: Text('${_acompanantes[i].rut} · ${_acompanantes[i].edad} años'),
              trailing: IconButton(
                tooltip: 'Quitar a ${_acompanantes[i].nombre}',
                icon: const Icon(Icons.close),
                onPressed: () => setState(() => _acompanantes.removeAt(i)),
              ),
            ),
          Align(
            alignment: Alignment.centerLeft,
            child: TextButton.icon(
              onPressed: _acompanantes.length >= 20 ? null : _agregarAcompanante,
              icon: const Icon(Icons.person_add),
              label: const Text('Agregar acompañante'),
            ),
          ),
          _Seccion('Detalle (opcional)'),
          TextFormField(
            controller: _detalle,
            maxLines: 3,
            maxLength: 2000,
            decoration: const InputDecoration(
              hintText: 'Cómo vas vestido, qué llevas, cómo reconocerte…',
              border: OutlineInputBorder(),
            ),
          ),
          if (_error != null) ...[
            const SizedBox(height: 8),
            Text(_error!,
                textAlign: TextAlign.center,
                style: TextStyle(color: tema.colorScheme.error)),
          ],
          const SizedBox(height: 16),
          FilledButton(
            onPressed: _enviando ? null : _enviar,
            child: _enviando
                ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('Avisar salida'),
          ),
        ],
      ),
    );
  }
}

class _Seccion extends StatelessWidget {
  const _Seccion(this.titulo);

  final String titulo;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(0, 24, 0, 12),
        child: Text(titulo, style: Theme.of(context).textTheme.titleMedium),
      );
}

/// Campo que muestra una fecha y hora y las pide con los selectores del sistema.
class _CampoFechaHora extends FormField<DateTime> {
  _CampoFechaHora({
    super.key,
    required String etiqueta,
    required DateTime inicial,
    required DateTime primera,
    required DateTime ultima,
    required ValueChanged<DateTime> alCambiar,
    super.validator,
  }) : super(
          initialValue: inicial,
          builder: (state) {
            Future<void> elegir() async {
              final actual = state.value ?? inicial;
              final fecha = await showDatePicker(
                context: state.context,
                initialDate: actual.isBefore(primera) ? primera : actual,
                firstDate: primera,
                lastDate: ultima,
              );
              if (fecha == null || !state.mounted) return;
              final hora = await showTimePicker(
                context: state.context,
                initialTime: TimeOfDay.fromDateTime(actual),
              );
              if (hora == null || !state.mounted) return;
              final nueva = DateTime(fecha.year, fecha.month, fecha.day, hora.hour, hora.minute);
              state.didChange(nueva);
              alCambiar(nueva);
            }

            final d = state.value;
            String dos(int n) => n.toString().padLeft(2, '0');
            final texto = d == null
                ? 'Elegir…'
                : '${dos(d.day)}/${dos(d.month)}/${d.year}  ${dos(d.hour)}:${dos(d.minute)}';

            return InkWell(
              onTap: elegir,
              child: InputDecorator(
                decoration: InputDecoration(
                  labelText: etiqueta,
                  border: const OutlineInputBorder(),
                  errorText: state.errorText,
                  suffixIcon: const Icon(Icons.event),
                ),
                child: Text(texto),
              ),
            );
          },
        );
}
