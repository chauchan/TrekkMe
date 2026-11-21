import 'package:dio/dio.dart';

import '../core/api_exception.dart';
import '../models/usuario.dart';
import 'auth_repository.dart';

/// Login real contra Laravel.
///
/// 1. `POST /login` valida correo y contraseña y devuelve un token
///    personalizado de Firebase.
/// 2. Ese token se canjea por un ID token con la API REST de Firebase (no hace
///    falta el SDK completo).
/// 3. El ID token viaja en cada petición como `Authorization: Bearer`. Dura una
///    hora, así que [idToken] lo renueva con el refresh token cuando está por
///    vencer.
class AuthApiRepository implements AuthRepository {
  AuthApiRepository({
    required this._api,
    required this._firebase,
    required this._apiKey,
    this._ahora = DateTime.now,
  });

  static const _canje =
      'https://identitytoolkit.googleapis.com/v1/accounts:signInWithCustomToken';
  static const _renovacion = 'https://securetoken.googleapis.com/v1/token';

  /// Margen para renovar antes de que el token venza de verdad.
  static const _margen = Duration(minutes: 1);

  final Dio _api;
  final Dio _firebase;
  final String _apiKey;
  final DateTime Function() _ahora;

  String? _idToken;
  String? _refreshToken;
  DateTime? _vence;
  Future<String?>? _renovando;

  @override
  Future<Usuario> login(String email, String password) async {
    final Map<String, dynamic> datos;
    try {
      final res = await _api.post<Map<String, dynamic>>('/login', data: {
        'email': email.trim(),
        'password': password,
      });
      datos = (res.data!['data'] as Map).cast<String, dynamic>();
    } on DioException catch (e) {
      throw AuthException(_mensajeLogin(e));
    }

    // Laravel deja entrar también a carabineros y rescatistas (app Ionic).
    if (datos['perfil'] != 'excursionista') {
      throw const AuthException('Esta aplicación es solo para excursionistas.');
    }

    try {
      final res = await _firebase.post<Map<String, dynamic>>(
        _canje,
        queryParameters: {'key': _apiKey},
        data: {'token': datos['firebase_token'], 'returnSecureToken': true},
      );
      _guardar(res.data!['idToken'] as String, res.data!['refreshToken'] as String,
          res.data!['expiresIn']);
    } on DioException {
      throw const AuthException('No se pudo iniciar sesión. Intenta de nuevo.');
    }

    return Usuario(
      id: datos['usuario_id'] as String,
      nombre: datos['nombre'] as String,
    );
  }

  @override
  Future<void> logout() async {
    _idToken = null;
    _refreshToken = null;
    _vence = null;
  }

  /// ID token vigente, renovándolo si está por vencer. `null` si no hay sesión
  /// o si ya no se pudo renovar (la API responderá 401 y la app volverá al login).
  Future<String?> idToken() async {
    if (_idToken == null) return null;
    if (_vence!.subtract(_margen).isAfter(_ahora())) return _idToken;

    // Varias peticiones a la vez comparten una sola renovación.
    return _renovando ??= _renovar().whenComplete(() => _renovando = null);
  }

  Future<String?> _renovar() async {
    final refresh = _refreshToken;
    if (refresh == null) return null;

    try {
      final res = await _firebase.post<Map<String, dynamic>>(
        _renovacion,
        queryParameters: {'key': _apiKey},
        data: {'grant_type': 'refresh_token', 'refresh_token': refresh},
        options: Options(contentType: Headers.formUrlEncodedContentType),
      );
      _guardar(res.data!['id_token'] as String, res.data!['refresh_token'] as String,
          res.data!['expires_in']);
      return _idToken;
    } on DioException {
      await logout();
      return null;
    }
  }

  void _guardar(String idToken, String refreshToken, Object? segundos) {
    _idToken = idToken;
    _refreshToken = refreshToken;
    _vence = _ahora().add(Duration(seconds: int.tryParse('$segundos') ?? 3600));
  }

  String _mensajeLogin(DioException e) {
    if (e.response?.statusCode == 429) {
      return 'Demasiados intentos. Espera un minuto e intenta de nuevo.';
    }
    return ApiException.desde(e).mensaje;
  }
}
