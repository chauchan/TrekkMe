import 'package:dio/dio.dart';

import '../core/api_exception.dart';
import '../models/excursion.dart';
import '../models/sendero.dart';
import 'excursion_repository.dart';

/// Excursiones del trekker contra la API de Laravel (`/excursionista/...`).
///
/// [dio] debe ser el cliente con sesión (`DioClient.autenticado`), que agrega el
/// Bearer. Cualquier error HTTP se traduce a [ApiException] con el mensaje de
/// la API listo para mostrar.
class ExcursionApiRepository implements ExcursionRepository {
  ExcursionApiRepository(this._dio);

  final Dio _dio;

  @override
  Future<List<Sendero>> senderos() => _llamar(() async {
        final res = await _dio.get<Map<String, dynamic>>('/senderos');
        return _lista(res).map(Sendero.fromJson).toList();
      });

  @override
  Future<List<Excursion>> misExcursiones() => _llamar(() async {
        final res = await _dio.get<Map<String, dynamic>>('/excursionista/excursiones');
        return _lista(res).map(Excursion.fromJson).toList();
      });

  @override
  Future<Excursion> crear(NuevaExcursion datos) => _llamar(() async {
        final res = await _dio.post<Map<String, dynamic>>(
          '/excursionista/excursiones',
          data: datos.toJson(),
        );
        return Excursion.fromJson(_objeto(res));
      });

  @override
  Future<Excursion> iniciar(Excursion excursion) =>
      _transicion(excursion, 'iniciar');

  @override
  Future<Excursion> regresar(Excursion excursion) =>
      _transicion(excursion, 'regresar');

  @override
  Future<Excursion> enviarUbicacion(
    Excursion excursion, {
    required double lat,
    required double lng,
    int? bateria,
  }) =>
      _llamar(() async {
        final res = await _dio.post<Map<String, dynamic>>(
          '/excursionista/excursiones/${excursion.id}/ubicacion',
          data: {'lat': lat, 'lng': lng, 'bateria': ?bateria},
        );
        final d = _objeto(res);
        // La API devuelve solo la ubicación guardada: se mezcla con lo que ya se tenía
        return excursion.copyWith(
          ultimaLat: (d['ultima_lat'] as num).toDouble(),
          ultimaLng: (d['ultima_lng'] as num).toDouble(),
          ultimaUbicacionAt: DateTime.parse(d['ultima_ubicacion_at'] as String).toLocal(),
          bateria: d['bateria'] as int?,
        );
      });

  // iniciar / regresar: la API devuelve solo `{ id, estado }`
  Future<Excursion> _transicion(Excursion excursion, String accion) =>
      _llamar(() async {
        final res = await _dio.post<Map<String, dynamic>>(
          '/excursionista/excursiones/${excursion.id}/$accion',
          data: {'estado_esperado': excursion.estado.api},
        );
        final estado = EstadoExcursion.desdeApi(_objeto(res)['estado'] as String);
        return excursion.copyWith(estado: estado);
      });

  Future<T> _llamar<T>(Future<T> Function() peticion) async {
    try {
      return await peticion();
    } on DioException catch (e) {
      throw ApiException.desde(e);
    }
  }

  // Respuestas `{ "data": [...] }` y `{ "data": {...} }`
  List<Map<String, dynamic>> _lista(Response<Map<String, dynamic>> res) => [
        for (final f in res.data!['data'] as List) (f as Map).cast<String, dynamic>(),
      ];

  Map<String, dynamic> _objeto(Response<Map<String, dynamic>> res) =>
      (res.data!['data'] as Map).cast<String, dynamic>();
}
