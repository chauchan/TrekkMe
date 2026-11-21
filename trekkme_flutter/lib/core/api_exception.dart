import 'package:dio/dio.dart';

/// Error de la API con un mensaje listo para mostrar al usuario.
///
/// Los códigos siguen el contrato de `laravel/API.md`: 401 sesión vencida,
/// 403 sin permiso, 404 ya no existe, 409 el estado cambió, 422 datos
/// inválidos.
class ApiException implements Exception {
  const ApiException(this.mensaje, {this.statusCode});

  final String mensaje;
  final int? statusCode;

  bool get sesionVencida => statusCode == 401;
  bool get conflicto => statusCode == 409;

  /// Traduce un error de Dio. Usa el `message` de la API (viene en español);
  /// en un 422 prefiere el primer mensaje de `errors`, que dice qué campo falló.
  factory ApiException.desde(DioException e) {
    final status = e.response?.statusCode;
    final cuerpo = e.response?.data;

    if (cuerpo is Map) {
      final errores = cuerpo['errors'];
      if (errores is Map && errores.isNotEmpty) {
        final primero = errores.values.first;
        final texto = primero is List && primero.isNotEmpty ? primero.first : primero;
        if (texto is String && texto.isNotEmpty) {
          return ApiException(texto, statusCode: status);
        }
      }
      final mensaje = cuerpo['message'];
      if (mensaje is String && mensaje.isNotEmpty) {
        return ApiException(mensaje, statusCode: status);
      }
    }

    if (status == null) {
      return const ApiException('No se pudo conectar con el servidor.');
    }
    return ApiException('Error inesperado ($status).', statusCode: status);
  }

  @override
  String toString() => mensaje;
}
