/// Sendero del catálogo oficial (el mismo que usa Ionic).
class Sendero {
  const Sendero({required this.id, required this.nombre});

  final String id;
  final String nombre;

  /// Fila de `GET /senderos`: `{ id, nombre, jurisdiccion_id }`.
  factory Sendero.fromJson(Map<String, dynamic> json) => Sendero(
        id: json['id'] as String,
        nombre: json['nombre'] as String,
      );
}
