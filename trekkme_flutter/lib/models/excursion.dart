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

/// Acompañante sin cuenta. Al crear la excursión se piden todos los datos; la
/// API solo devuelve nombre y RUT (los datos médicos no vuelven al celular).
class Acompanante {
  const Acompanante({
    required this.nombre,
    required this.rut,
    this.edad,
    this.condicionesMedicas,
  });

  final String nombre;
  final String rut;
  final int? edad;
  final String? condicionesMedicas;

  factory Acompanante.fromJson(Map<String, dynamic> json) => Acompanante(
        nombre: json['nombre'] as String,
        rut: json['rut'] as String,
      );

  Map<String, dynamic> toJson() => {
        'nombre': nombre,
        'rut': rut,
        'edad': edad ?? 0,
        'condiciones_medicas': condicionesMedicas ?? 'Ninguna',
      };
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

  /// Excursión de `GET /excursionista/excursiones` o respuesta de crear.
  /// Las fechas llegan en ISO 8601 con zona y se muestran en hora local.
  factory Excursion.fromJson(Map<String, dynamic> json) {
    DateTime? fecha(Object? v) =>
        v == null ? null : DateTime.parse(v as String).toLocal();

    return Excursion(
      id: json['id'] as String,
      sendero: Sendero(
        id: (json['sendero_id'] as String?) ?? '',
        nombre: (json['sendero'] as String?) ?? '',
      ),
      estado: EstadoExcursion.desdeApi(json['estado'] as String),
      salidaAt: fecha(json['salida_at'])!,
      retornoAt: fecha(json['retorno_at'])!,
      contactoNombre: (json['contacto_nombre'] as String?) ?? '',
      contactoTelefono: (json['contacto_telefono'] as String?) ?? '',
      detalle: json['detalle'] as String?,
      llevaCarpa: (json['lleva_carpa'] as bool?) ?? false,
      colorCarpa: json['color_carpa'] as String?,
      llevaLinterna: (json['lleva_linterna'] as bool?) ?? false,
      acompanantes: [
        for (final a in (json['acompanantes'] as List?) ?? const [])
          Acompanante.fromJson((a as Map).cast<String, dynamic>()),
      ],
      ultimaLat: (json['ultima_lat'] as num?)?.toDouble(),
      ultimaLng: (json['ultima_lng'] as num?)?.toDouble(),
      ultimaUbicacionAt: fecha(json['ultima_ubicacion_at']),
      bateria: json['bateria'] as int?,
    );
  }

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
///
/// Los datos personales (`rut`, `telefono`, `edad`, `experiencia`,
/// `condicionesMedicas`) aún no viven en el perfil del usuario: la API los
/// pide en cada aviso.
class NuevaExcursion {
  const NuevaExcursion({
    required this.sendero,
    required this.salidaAt,
    required this.retornoAt,
    required this.contactoNombre,
    required this.contactoTelefono,
    required this.rut,
    required this.telefono,
    required this.edad,
    required this.experiencia,
    this.condicionesMedicas = 'Ninguna',
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
  final String rut;
  final String telefono;
  final int edad;

  /// `Principiante`, `Intermedio` o `Avanzado`.
  final String experiencia;
  final String condicionesMedicas;
  final String? detalle;
  final bool llevaCarpa;
  final String? colorCarpa;
  final bool llevaLinterna;
  final List<Acompanante> acompanantes;

  /// Cuerpo de `POST /excursionista/excursiones`. Las fechas van en UTC (`Z`):
  /// una fecha local sin zona la interpretaría el servidor en otra hora.
  Map<String, dynamic> toJson() => {
        'sendero_id': sendero.id,
        'salida_at': salidaAt.toUtc().toIso8601String(),
        'retorno_at': retornoAt.toUtc().toIso8601String(),
        'contacto_nombre': contactoNombre,
        'contacto_telefono': contactoTelefono,
        'rut': rut,
        'telefono': telefono,
        'edad': edad,
        'experiencia': experiencia,
        'condiciones_medicas': condicionesMedicas,
        if (detalle != null && detalle!.trim().isNotEmpty) 'detalle': detalle,
        'lleva_carpa': llevaCarpa,
        if (llevaCarpa && colorCarpa != null && colorCarpa!.trim().isNotEmpty)
          'color_carpa': colorCarpa,
        'lleva_linterna': llevaLinterna,
        'acompanantes': [for (final a in acompanantes) a.toJson()],
      };
}
