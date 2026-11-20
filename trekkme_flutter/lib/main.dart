import 'package:flutter/material.dart';
import 'overpass_service.dart';
import 'page.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Flutter Demo',
      theme: ThemeData(
        colorScheme: .fromSeed(seedColor: const Color.fromARGB(255, 30, 91, 171)),
      ),
      home: const MyHomePage(title: 'TrekkMe'),
    );
  }
}

class MyHomePage extends StatefulWidget {
  const MyHomePage({super.key, required this.title});

  final String title;

  @override
  State<MyHomePage> createState() => _MyHomePageState();
}

class _MyHomePageState extends State<MyHomePage> {
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

  void _reload() => setState(() => _data = _load());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Theme.of(context).colorScheme.inversePrimary,
        title: Text(widget.title),
      ),
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
                    FilledButton(onPressed: _reload, child: const Text('Reintentar')),
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
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: ElevatedButton(
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (context) => const Pagina()),
                      );
                    },
                    child: const Text('Ir a Pagina'),
                  ),
                ),
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
