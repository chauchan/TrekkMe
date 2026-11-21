import 'package:dio/dio.dart';

/// Entrega el ID token vigente de Firebase, o null si no hay sesión.
typedef TokenProvider = Future<String?> Function();

/// Fábrica de clientes HTTP hacia la API de Laravel.
class DioClient {
  const DioClient._();

  static BaseOptions _opciones(String baseUrl) => BaseOptions(
        baseUrl: baseUrl,
        connectTimeout: const Duration(seconds: 10),
        receiveTimeout: const Duration(seconds: 15),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      );

  /// Cliente sin sesión, para `POST /login`.
  static Dio publico(String baseUrl) => Dio(_opciones(baseUrl));

  /// Cliente con sesión: agrega `Authorization: Bearer <ID token>` en cada
  /// petición. El token se pide cada vez porque el proveedor lo renueva solo
  /// cuando está por vencer. Si la API responde 401, avisa con
  /// [onSesionVencida] para volver al login.
  static Dio autenticado(
    String baseUrl, {
    required TokenProvider token,
    required void Function() onSesionVencida,
  }) {
    final dio = Dio(_opciones(baseUrl));
    dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final valor = await token();
        if (valor != null) options.headers['Authorization'] = 'Bearer $valor';
        handler.next(options);
      },
      onError: (error, handler) {
        if (error.response?.statusCode == 401) onSesionVencida();
        handler.next(error);
      },
    ));
    return dio;
  }
}
