import 'sendero.dart';

/// Estados de una excursión. En la API y en Firestore van en minúscula con
/// guion bajo (`en_curso`).
enum EstadoExcursion {
  planificada('planificada', 'Planificada'),
  enCurso('en_curso', 'En curso'),
  enAlerta('en_alerta', 'En alerta'),
  enOperativo('en_operativo', 'En operativo'),
  cierrePendiente('cierre_pendiente', 'Cierre pendiente'),
  cerrada('cerrada', 'Cerrada');

  const EstadoExcursion(this.api, this.etiqueta);

  /// Valor que viaja en la API.
  final String api;

  /// Texto interno completo.
  final String etiqueta;

  /// Texto que ve el excursionista: "cierre pendiente" no se distingue de
  /// "en operativo" (pregunta abierta 5 del plan).
  String get etiquetaPublica =>
      this == cierrePendiente ? enOperativo.etiqueta : etiqueta;

  static EstadoExcursion desdeApi(String valor) => EstadoExcursion.values
      .firstWhere((e) => e.api == valor, orElse: () => planificada);
}

/// Acompañante sin cuenta, ingresado por quien crea la excursión.
class Acompanante {
  const Acompanante({
    required this.nombre,
    required this.rut,
    required this.edad,
    this.condicionesMedicas = 'Ninguna',
  });

  final String nombre;
  final String rut;
  final int edad;
  final String condicionesMedicas;
}

/// Excursión del trekker autenticado.
class Excursion {
  const Excursion({
    required this.id,
    required this.sendero,
    required this.salidaAt,
    required this.retornoAt,
    required this.estado,
    required this.contactoNombre,
    required this.contactoTelefono,
    this.detalle,
    this.llevaCarpa = false,
    this.colorCarpa,
    this.llevaLinterna = false,
    this.acompanantes = const [],
    this.ultimaLat,
    this.ultimaLng,
    this.ultimaUbicacionAt,
    this.bateria,
  });

  final String id;
  final Sendero sendero;
  final DateTime salidaAt;
  final DateTime retornoAt;
  final EstadoExcursion estado;
  final String contactoNombre;
  final String contactoTelefono;
  final String? detalle;
  final bool llevaCarpa;
  final String? colorCarpa;
  final bool llevaLinterna;
  final List<Acompanante> acompanantes;
  final double? ultimaLat;
  final double? ultimaLng;
  final DateTime? ultimaUbicacionAt;
  final int? bateria;

  Excursion copyWith({
    EstadoExcursion? estado,
    double? ultimaLat,
    double? ultimaLng,
    DateTime? ultimaUbicacionAt,
    int? bateria,
  }) {
    return Excursion(
      id: id,
      sendero: sendero,
      salidaAt: salidaAt,
      retornoAt: retornoAt,
      estado: estado ?? this.estado,
      contactoNombre: contactoNombre,
      contactoTelefono: contactoTelefono,
      detalle: detalle,
      llevaCarpa: llevaCarpa,
      colorCarpa: colorCarpa,
      llevaLinterna: llevaLinterna,
      acompanantes: acompanantes,
      ultimaLat: ultimaLat ?? this.ultimaLat,
      ultimaLng: ultimaLng ?? this.ultimaLng,
      ultimaUbicacionAt: ultimaUbicacionAt ?? this.ultimaUbicacionAt,
      bateria: bateria ?? this.bateria,
    );
  }
}

/// Datos que el trekker ingresa en el formulario de aviso de salida.
class NuevaExcursion {
  const NuevaExcursion({
    required this.sendero,
    required this.salidaAt,
    required this.retornoAt,
    required this.contactoNombre,
    required this.contactoTelefono,
    this.detalle,
    this.llevaCarpa = false,
    this.colorCarpa,
    this.llevaLinterna = false,
    this.acompanantes = const [],
  });

  final Sendero sendero;
  final DateTime salidaAt;
  final DateTime retornoAt;
  final String contactoNombre;
  final String contactoTelefono;
  final String? detalle;
  final bool llevaCarpa;
  final String? colorCarpa;
  final bool llevaLinterna;
  final List<Acompanante> acompanantes;
}
