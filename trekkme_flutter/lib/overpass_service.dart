import 'dart:convert';

import 'package:http/http.dart' as http;

/// Sendero (relación route=hiking de OpenStreetMap).
class Trail {
  const Trail({
    required this.name,
    required this.lat,
    required this.lon,
    this.ref,
    this.distanceKm,
    this.network,
  });

  final String name;
  final double lat;
  final double lon;
  final String? ref;
  final double? distanceKm;
  final String? network;
}

/// Mirador (nodo tourism=viewpoint de OpenStreetMap).
class Viewpoint {
  const Viewpoint({
    required this.name,
    required this.lat,
    required this.lon,
    this.elevation,
  });

  final String name;
  final double lat;
  final double lon;
  final String? elevation;
}

/// Cliente mínimo de Overpass API. Datos © OpenStreetMap contributors (ODbL).
class OverpassService {
  // El servidor público a veces responde 502/429 por saturación: se prueban
  // espejos en orden hasta que uno responda.
  static const _endpoints = [
    'https://overpass-api.de/api/interpreter',
    'https://overpass.kumi.systems/api/interpreter',
    'https://overpass.private.coffee/api/interpreter',
  ];

  Future<List<dynamic>> _query(String ql) async {
    Object? lastError;
    for (final endpoint in _endpoints) {
      try {
        final res = await http
            .post(
              Uri.parse(endpoint),
              headers: {'User-Agent': 'TrekkMe/0.1 (flutter)'},
              body: {'data': ql},
            )
            .timeout(const Duration(seconds: 40));

        if (res.statusCode != 200) {
          lastError = 'HTTP ${res.statusCode} en $endpoint';
          continue;
        }
        final json =
            jsonDecode(utf8.decode(res.bodyBytes)) as Map<String, dynamic>;
        return json['elements'] as List<dynamic>;
      } catch (e) {
        lastError = e;
      }
    }
    throw Exception('Overpass no disponible ($lastError)');
  }

  /// Senderos de senderismo con nombre cerca de un punto.
  Future<List<Trail>> trailsNear(
    double lat,
    double lon, {
    int radiusMeters = 25000,
    int limit = 30,
  }) async {
    final elements = await _query('''
[out:json][timeout:30];
relation["route"="hiking"]["name"](around:$radiusMeters,$lat,$lon);
out tags center $limit;
''');

    return elements
        .map((e) {
          final tags = (e['tags'] as Map?)?.cast<String, dynamic>() ?? {};
          final center = e['center'] as Map?;
          if (center == null) return null;
          final dist = double.tryParse('${tags['distance']}'.replaceAll(',', '.'));
          return Trail(
            name: tags['name'] as String,
            ref: tags['ref'] as String?,
            network: tags['network'] as String?,
            distanceKm: dist,
            lat: (center['lat'] as num).toDouble(),
            lon: (center['lon'] as num).toDouble(),
          );
        })
        .whereType<Trail>()
        .toList();
  }

  /// Miradores cerca de un punto.
  Future<List<Viewpoint>> viewpointsNear(
    double lat,
    double lon, {
    int radiusMeters = 25000,
    int limit = 40,
  }) async {
    final elements = await _query('''
[out:json][timeout:30];
node["tourism"="viewpoint"](around:$radiusMeters,$lat,$lon);
out $limit;
''');

    return elements.map((e) {
      final tags = (e['tags'] as Map?)?.cast<String, dynamic>() ?? {};
      return Viewpoint(
        name: (tags['name'] as String?) ?? 'Mirador sin nombre',
        elevation: tags['ele'] as String?,
        lat: (e['lat'] as num).toDouble(),
        lon: (e['lon'] as num).toDouble(),
      );
    }).toList();
  }
}
