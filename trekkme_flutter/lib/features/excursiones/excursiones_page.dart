import 'package:flutter/material.dart';

import '../../core/api_exception.dart';
import '../../core/telefono.dart';
import '../../data/excursion_repository.dart';
import '../../models/excursion.dart';
import '../aviso/aviso_page.dart';
import '../explorar/explorar_page.dart';

/// Home del trekker: sus excursiones con el estado actual (F3).
class ExcursionesPage extends StatefulWidget {
  const ExcursionesPage({
    super.key,
    required this.repo,
    required this.nombreUsuario,
    required this.onLogout,
    this.leer = leerTelefono,
  });

  final ExcursionRepository repo;
  final String nombreUsuario;
  final VoidCallback onLogout;

  /// De dónde salen la ubicación y la batería.
  final LeerTelefono leer;

  @override
  State<ExcursionesPage> createState() => _ExcursionesPageState();
}

class _ExcursionesPageState extends State<ExcursionesPage> {
  late Future<List<Excursion>> _excursiones;

  /// Excursión con una acción en curso: mientras tanto no se aceptan otras.
  String? _ocupada;

  @override
  void initState() {
    super.initState();
    _excursiones = widget.repo.misExcursiones();
  }

  Future<void> _recargar() async {
    final nuevas = widget.repo.misExcursiones();
    setState(() {
      _excursiones = nuevas;
    });
    await nuevas;
  }

  void _mensaje(String texto) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(texto)));
  }

  /// Ejecuta una acción sobre una excursión, informa el resultado y recarga.
  /// También recarga si falla: con un 409 el estado en pantalla está atrasado.
  Future<void> _accion(
    Excursion excursion,
    Future<void> Function() ejecutar,
    String exito,
  ) async {
    if (_ocupada != null) return;
    setState(() => _ocupada = excursion.id);
    try {
      await ejecutar();
      if (mounted) _mensaje(exito);
    } on ApiException catch (e) {
      if (mounted) _mensaje(e.mensaje);
    } catch (_) {
      if (mounted) _mensaje('No se pudo completar. Intenta de nuevo.');
    }
    if (!mounted) return;
    setState(() => _ocupada = null);
    // Si la recarga falla, la lista ya muestra el error y ofrece reintentar
    await _recargar().catchError((_) {});
  }

  Future<void> _iniciar(Excursion e) =>
      _accion(e, () => widget.repo.iniciar(e), 'Salida iniciada.');

  Future<void> _enviarUbicacion(Excursion e) => _accion(e, () async {
        final lectura = await widget.leer();
        await widget.repo.enviarUbicacion(
          e,
          lat: lectura.lat,
          lng: lectura.lng,
          bateria: lectura.bateria,
        );
      }, 'Ubicación enviada.');

  /// "Ya volví" cierra la excursión y no se puede deshacer: se confirma antes.
  Future<void> _regresar(Excursion e) async {
    final confirmado = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('¿Ya volviste?'),
        content: Text('Se cerrará tu excursión a ${e.sendero.nombre}.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Sí, ya volví'),
          ),
        ],
      ),
    );
    if (confirmado != true || !mounted) return;
    await _accion(e, () => widget.repo.regresar(e), 'Regreso confirmado.');
  }

  Future<void> _avisarSalida() async {
    final creada = await Navigator.push<Excursion>(
      context,
      MaterialPageRoute(builder: (_) => AvisoPage(repo: widget.repo)),
    );
    if (creada == null || !mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Salida avisada: ${creada.sendero.nombre}')),
    );
    await _recargar();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Theme.of(context).colorScheme.inversePrimary,
        title: const Text('Mis excursiones'),
        actions: [
          IconButton(
            tooltip: 'Explorar senderos y miradores',
            icon: const Icon(Icons.explore),
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const ExplorarPage()),
            ),
          ),
          IconButton(
            tooltip: 'Cerrar sesión (${widget.nombreUsuario})',
            icon: const Icon(Icons.logout),
            onPressed: widget.onLogout,
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _avisarSalida,
        icon: const Icon(Icons.add),
        label: const Text('Avisar salida'),
      ),
      body: FutureBuilder<List<Excursion>>(
        future: _excursiones,
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
                    Text('No se pudo cargar:\n${snapshot.error}',
                        textAlign: TextAlign.center),
                    const SizedBox(height: 12),
                    FilledButton(
                        onPressed: _recargar, child: const Text('Reintentar')),
                  ],
                ),
              ),
            );
          }

          final lista = snapshot.data!;
          if (lista.isEmpty) {
            return const Center(child: Text('Aún no tienes excursiones.'));
          }
          return RefreshIndicator(
            onRefresh: _recargar,
            child: ListView.separated(
              // Deja libre el botón flotante bajo la última excursión
              padding: const EdgeInsets.only(bottom: 88),
              itemCount: lista.length,
              separatorBuilder: (_, _) => const Divider(height: 1),
              itemBuilder: (context, i) {
                final e = lista[i];
                final libre = _ocupada == null;
                return _ExcursionTile(
                  e,
                  ocupada: _ocupada == e.id,
                  onIniciar: libre ? () => _iniciar(e) : null,
                  onEnviarUbicacion: libre ? () => _enviarUbicacion(e) : null,
                  onRegresar: libre ? () => _regresar(e) : null,
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class _ExcursionTile extends StatelessWidget {
  const _ExcursionTile(
    this.excursion, {
    required this.ocupada,
    required this.onIniciar,
    required this.onEnviarUbicacion,
    required this.onRegresar,
  });

  final Excursion excursion;
  final bool ocupada;

  // `null` deshabilita el botón
  final VoidCallback? onIniciar;
  final VoidCallback? onEnviarUbicacion;
  final VoidCallback? onRegresar;

  static String _fecha(DateTime d) {
    String dos(int n) => n.toString().padLeft(2, '0');
    return '${dos(d.day)}/${dos(d.month)} ${dos(d.hour)}:${dos(d.minute)}';
  }

  /// Botones según el estado (las mismas reglas que valida Laravel).
  List<Widget> _botones() {
    final ubicacion = OutlinedButton.icon(
      onPressed: onEnviarUbicacion,
      icon: const Icon(Icons.my_location),
      label: const Text('Enviar ubicación'),
    );

    return switch (excursion.estado) {
      EstadoExcursion.planificada => [
          FilledButton.icon(
            onPressed: onIniciar,
            icon: const Icon(Icons.play_arrow),
            label: const Text('Iniciar salida'),
          ),
        ],
      EstadoExcursion.enCurso => [
          ubicacion,
          FilledButton.icon(
            onPressed: onRegresar,
            icon: const Icon(Icons.check),
            label: const Text('Ya volví'),
          ),
        ],
      // En alerta o en búsqueda solo cierra Carabineros, pero la ubicación sirve
      EstadoExcursion.enAlerta ||
      EstadoExcursion.enOperativo ||
      EstadoExcursion.cierrePendiente =>
        [ubicacion],
      EstadoExcursion.cerrada => const [],
    };
  }

  @override
  Widget build(BuildContext context) {
    final botones = _botones();
    final ubicadaAt = excursion.ultimaUbicacionAt;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        ListTile(
          leading: const Icon(Icons.hiking),
          title: Text(excursion.sendero.nombre),
          subtitle: Text(
              'Salida ${_fecha(excursion.salidaAt)} · Retorno ${_fecha(excursion.retornoAt)}'
              '${ubicadaAt == null ? '' : '\nÚltima ubicación ${_fecha(ubicadaAt)}'}'),
          trailing: Chip(label: Text(excursion.estado.etiquetaPublica)),
        ),
        if (botones.isNotEmpty)
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
            child: Wrap(
              spacing: 8,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                ...botones,
                if (ocupada)
                  const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
              ],
            ),
          ),
      ],
    );
  }
}
