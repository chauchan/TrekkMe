import '../models/excursion.dart';
import '../models/sendero.dart';

/// Contrato de datos del trekker. Hay dos implementaciones: la real (API de
/// Laravel) y la de demostración en memoria. Las pantallas solo conocen esta
/// interfaz.
///
/// Las operaciones que cambian una excursión reciben la [Excursion] tal como
/// está en pantalla: de ahí sale el `estado_esperado` con el que la API detecta
/// que alguien (o el servidor) la cambió mientras tanto (error 409).
/// Los errores de la API llegan como `ApiException`.
abstract class ExcursionRepository {
  /// Catálogo de senderos (`GET /senderos`).
  Future<List<Sendero>> senderos();

  /// Excursiones del trekker autenticado, las más recientes primero.
  Future<List<Excursion>> misExcursiones();

  /// Crea una excursión en estado `planificada`.
  Future<Excursion> crear(NuevaExcursion datos);

  /// `planificada` → `en_curso`.
  Future<Excursion> iniciar(Excursion excursion);

  /// Guarda la última ubicación y la batería.
  Future<Excursion> enviarUbicacion(
    Excursion excursion, {
    required double lat,
    required double lng,
    int? bateria,
  });

  /// `en_curso` → `cerrada` ("Ya volví").
  Future<Excursion> regresar(Excursion excursion);
}
