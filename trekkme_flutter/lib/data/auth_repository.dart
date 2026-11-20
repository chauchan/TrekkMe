import '../models/usuario.dart';

/// Error de autenticación con un mensaje listo para mostrar al usuario
/// (la API devuelve los mensajes en español).
class AuthException implements Exception {
  const AuthException(this.mensaje);

  final String mensaje;

  @override
  String toString() => mensaje;
}

/// Contrato de autenticación. La implementación real llamará a
/// `POST /login` de Laravel; hoy existe una de demostración.
abstract class AuthRepository {
  /// Inicia sesión. Lanza [AuthException] si las credenciales son inválidas.
  Future<Usuario> login(String email, String password);

  Future<void> logout();
}
