import 'package:flutter/foundation.dart';

/// Configuración de la app. Se puede cambiar al ejecutar sin tocar el código:
///
///   flutter run --dart-define=API_URL=http://192.168.1.10:8000/api/v1
///   flutter run --dart-define=USE_DEMO=true     # sin backend, datos de ejemplo
class AppConfig {
  const AppConfig._();

  /// Sin backend: la app trabaja con los repositorios de demostración.
  static const bool useDemo = bool.fromEnvironment('USE_DEMO');

  static const String _apiUrl = String.fromEnvironment('API_URL');

  /// URL base de la API de Laravel.
  ///
  /// El emulador de Android ve la máquina de desarrollo como `10.0.2.2`; el
  /// simulador de iOS y Chrome, como `localhost`. En un teléfono real hay que
  /// pasar la IP de la máquina con `--dart-define=API_URL=...`.
  static String get apiBaseUrl {
    if (_apiUrl.isNotEmpty) return _apiUrl;
    final esAndroid =
        !kIsWeb && defaultTargetPlatform == TargetPlatform.android;
    return 'http://${esAndroid ? '10.0.2.2' : 'localhost'}:8000/api/v1';
  }

  /// Clave web del proyecto Firebase de TrekkMe. Es una clave pública de
  /// cliente (la misma que usa Ionic), no un secreto: solo identifica el
  /// proyecto. Se usa para canjear el token de /login por un ID token.
  static const String firebaseApiKey = String.fromEnvironment(
    'FIREBASE_API_KEY',
    defaultValue: 'AIzaSyBoP59AemTbYspygqD5OQZPg3QBmO-dIbM',
  );
}
