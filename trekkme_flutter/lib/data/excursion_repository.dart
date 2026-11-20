import '../models/excursion.dart';
import '../models/sendero.dart';

/// Contrato de datos del trekker. Hay dos implementaciones: la real (API de
/// Laravel, pendiente) y la de demostración en memoria. Las pantallas solo
/// conocen esta interfaz.
abstract class ExcursionRepository {
  /// Catálogo de senderos (`GET /senderos`).
  Future<List<Sendero>> senderos();

  /// Excursiones del trekker autenticado.
  Future<List<Excursion>> misExcursiones();

  /// Crea una excursión en estado `planificada`.
  Future<Excursion> crear(NuevaExcursion datos);

  /// `planificada` → `en_curso`.
  Future<Excursion> iniciar(String id);

  /// Guarda la última ubicación y la batería.
  Future<Excursion> enviarUbicacion(
    String id, {
    required double lat,
    required double lng,
    required int bateria,
  });

  /// `en_curso` → `cerrada` ("Ya volví").
  Future<Excursion> regresar(String id);
}
