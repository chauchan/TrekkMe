import 'package:flutter/material.dart';

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
  });

  final ExcursionRepository repo;
  final String nombreUsuario;
  final VoidCallback onLogout;

  @override
  State<ExcursionesPage> createState() => _ExcursionesPageState();
}

class _ExcursionesPageState extends State<ExcursionesPage> {
  late Future<List<Excursion>> _excursiones;

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
              itemCount: lista.length,
              separatorBuilder: (_, _) => const Divider(height: 1),
              itemBuilder: (context, i) => _ExcursionTile(lista[i]),
            ),
          );
        },
      ),
    );
  }
}

class _ExcursionTile extends StatelessWidget {
  const _ExcursionTile(this.excursion);

  final Excursion excursion;

  static String _fecha(DateTime d) {
    String dos(int n) => n.toString().padLeft(2, '0');
    return '${dos(d.day)}/${dos(d.month)} ${dos(d.hour)}:${dos(d.minute)}';
  }

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: const Icon(Icons.hiking),
      title: Text(excursion.sendero.nombre),
      subtitle: Text(
          'Salida ${_fecha(excursion.salidaAt)} · Retorno ${_fecha(excursion.retornoAt)}'),
      trailing: Chip(label: Text(excursion.estado.etiquetaPublica)),
    );
  }
}
