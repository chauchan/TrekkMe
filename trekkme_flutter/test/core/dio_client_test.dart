import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:trekkme/core/api_exception.dart';
import 'package:trekkme/core/dio_client.dart';

import '../support/adaptador_falso.dart';

void main() {
  group('DioClient.autenticado', () {
    test('agrega el Bearer con el token que entrega el proveedor, en cada petición', () async {
      var n = 0;
      final falso = AdaptadorFalso((_) => jsonRespuesta({'data': []}));
      final dio = DioClient.autenticado(
        'http://api.test/api/v1',
        token: () async => 'token-${++n}',
        onSesionVencida: () {},
      )..httpClientAdapter = falso;

      await dio.get('/senderos');
      await dio.get('/senderos');

      expect(falso.peticiones[0].headers['Authorization'], 'Bearer token-1');
      expect(falso.peticiones[1].headers['Authorization'], 'Bearer token-2');
      expect(falso.peticiones[0].headers['Accept'], 'application/json');
      expect(falso.peticiones[0].uri.toString(), 'http://api.test/api/v1/senderos');
    });

    test('sin token no manda Authorization', () async {
      final falso = AdaptadorFalso((_) => jsonRespuesta({'data': []}));
      final dio = DioClient.autenticado(
        'http://api.test/api/v1',
        token: () async => null,
        onSesionVencida: () {},
      )..httpClientAdapter = falso;

      await dio.get('/senderos');

      expect(falso.peticiones.single.headers.containsKey('Authorization'), isFalse);
    });

    test('un 401 avisa que la sesión venció y el error sigue llegando', () async {
      var avisos = 0;
      final dio = DioClient.autenticado(
        'http://api.test/api/v1',
        token: () async => 'viejo',
        onSesionVencida: () => avisos++,
      )..httpClientAdapter = AdaptadorFalso(
          (_) => jsonRespuesta({'message': 'Tu sesión expiró. Vuelve a iniciar sesión.'}, 401));

      await expectLater(
        dio.get('/excursionista/excursiones'),
        throwsA(isA<DioException>()),
      );
      expect(avisos, 1);
    });

    test('otros errores no cierran la sesión', () async {
      var avisos = 0;
      for (final status in [403, 404, 409, 422, 500]) {
        final dio = DioClient.autenticado(
          'http://api.test/api/v1',
          token: () async => 't',
          onSesionVencida: () => avisos++,
        )..httpClientAdapter = AdaptadorFalso((_) => jsonRespuesta({'message': 'x'}, status));

        await expectLater(dio.get('/x'), throwsA(isA<DioException>()));
      }
      expect(avisos, 0);
    });
  });

  group('ApiException.desde', () {
    Future<ApiException> error(ResponseBody Function(RequestOptions) responder) async {
      final dio = Dio(BaseOptions(baseUrl: 'http://api.test'))
        ..httpClientAdapter = AdaptadorFalso(responder);
      try {
        await dio.get('/x');
      } on DioException catch (e) {
        return ApiException.desde(e);
      }
      throw StateError('Debía fallar');
    }

    test('en un 422 prefiere el mensaje del campo', () async {
      final e = await error((_) => jsonRespuesta({
            'message': 'The given data was invalid.',
            'errors': {'sendero_id': ['El sendero no existe.']},
          }, 422));

      expect(e.mensaje, 'El sendero no existe.');
      expect(e.statusCode, 422);
    });

    test('usa el message de la API si no hay errors', () async {
      final e = await error((_) => jsonRespuesta(
          {'message': 'Tu excursión cambió de estado. Actualiza e intenta de nuevo.'}, 409));

      expect(e.mensaje, 'Tu excursión cambió de estado. Actualiza e intenta de nuevo.');
      expect(e.conflicto, isTrue);
      expect(e.sesionVencida, isFalse);
    });

    test('un 401 se reconoce como sesión vencida', () async {
      final e = await error((_) => jsonRespuesta({'message': 'Tu sesión expiró.'}, 401));

      expect(e.sesionVencida, isTrue);
    });

    test('sin cuerpo útil da un mensaje genérico con el código', () async {
      final e = await error((_) => jsonRespuesta(<String, dynamic>{}, 500));

      expect(e.mensaje, 'Error inesperado (500).');
    });

    test('sin conexión no inventa un código', () async {
      final e = await error((o) => throw sinRed(o));

      expect(e.mensaje, 'No se pudo conectar con el servidor.');
      expect(e.statusCode, isNull);
    });
  });
}
