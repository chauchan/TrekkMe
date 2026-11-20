import 'package:dio/dio.dart';

class DioClient {
  static Dio build() {
    final dio = Dio(BaseOptions(
      baseUrl: 'https://dummyjson.com/users',
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 15),
      headers: {'Accept': 'application/json'},
    ));
    return dio;
  }
}