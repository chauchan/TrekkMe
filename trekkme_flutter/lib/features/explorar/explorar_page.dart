import 'package:flutter/material.dart';

import '../../overpass_service.dart';

/// Senderos y miradores cercanos desde OpenStreetMap. Es solo informativo:
/// la ruta de una excursión se elige del catálogo oficial, no de aquí.
class ExplorarPage extends StatefulWidget {
  const ExplorarPage({super.key});

  @override
  State<ExplorarPage> createState() => _ExplorarPageState();
}

class _ExplorarPageState extends State<ExplorarPage> {
  // Punto de búsqueda: Santiago, Chile. Cambia lat/lon para otra zona.
  static const _lat = -33.45;
  static const _lon = -70.66;

  final _service = OverpassService();
  late Future<(List<Trail>, List<Viewpoint>)> _data;

  @override
  void initState() {
    super.initState();
    _data = _load();
  }

  Future<(List<Trail>, List<Viewpoint>)> _load() async {
    final results = await Future.wait([
      _service.trailsNear(_lat, _lon),
      _service.viewpointsNear(_lat, _lon),
    ]);
    return (results[0] as List<Trail>, results[1] as List<Viewpoint>);
  }

  void _reload() {
    setState(() {
      _data = _load();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Explorar')),
      body: FutureBuilder(
        future: _data,
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
                        onPressed: _reload, child: const Text('Reintentar')),
                  ],
                ),
              ),
            );
          }

          final (trails, viewpoints) = snapshot.data!;
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              children: [
                _SectionHeader('Senderos (${trails.length})'),
                for (final t in trails)
                  ListTile(
                    leading: const Icon(Icons.hiking),
                    title: Text(t.name),
                    subtitle: Text([
                      if (t.ref != null) 'Ref ${t.ref}',
                      if (t.distanceKm != null) '${t.distanceKm} km',
                    ].join(' · ')),
                  ),
                _SectionHeader('Miradores (${viewpoints.length})'),
                for (final v in viewpoints)
                  ListTile(
                    leading: const Icon(Icons.landscape),
                    title: Text(v.name),
                    subtitle: Text([
                      if (v.elevation != null) '${v.elevation} m',
                      '${v.lat.toStringAsFixed(4)}, ${v.lon.toStringAsFixed(4)}',
                    ].join(' · ')),
                  ),
                const Padding(
                  padding: EdgeInsets.all(16),
                  child: Text(
                    '© OpenStreetMap contributors',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 20, 16, 4),
      child: Text(text, style: Theme.of(context).textTheme.titleLarge),
    );
  }
}
