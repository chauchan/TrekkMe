import '../models/usuario.dart';
import 'auth_repository.dart';

/// Autenticación de demostración, sin backend.
///
/// Usuario de prueba (solo para desarrollo, no es un acceso real):
///   correo:     trekker@trekkme.test
///   contraseña: trekker123
class AuthDemoRepository implements AuthRepository {
  static const _email = 'trekker@trekkme.test';
  static const _password = 'trekker123';

  @override
  Future<Usuario> login(String email, String password) async {
    await Future<void>.delayed(const Duration(milliseconds: 500));
    if (email.trim().toLowerCase() != _email || password != _password) {
      // Igual que la API: credenciales malas son un error de validación,
      // no una sesión expirada.
      throw const AuthException('Correo o contraseña incorrectos.');
    }
    return const Usuario(id: 'trek-001', nombre: 'Trekker de Prueba');
  }

  @override
  Future<void> logout() async {}
}
