import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:trekkme/data/auth_api_repository.dart';
import 'package:trekkme/data/auth_repository.dart';

import '../support/adaptador_falso.dart';

const _clave = 'clave-de-prueba';

Map<String, dynamic> _loginOk({String perfil = 'excursionista'}) => {
      'data': {
        'firebase_token': 'token-personalizado',
        'perfil': perfil,
        'usuario_id': 'usr-001',
        'nombre': 'Trekker de Prueba',
        'jurisdiccion_id': null,
      },
    };

Map<String, dynamic> _canjeOk({String idToken = 'id-1', String refresh = 'ref-1'}) => {
      'idToken': idToken,
      'refreshToken': refresh,
      'expiresIn': '3600',
    };

Map<String, dynamic> _renovacionOk({String idToken = 'id-2', String refresh = 'ref-2'}) => {
      'id_token': idToken,
      'refresh_token': refresh,
      'expires_in': '3600',
    };

class _Escenario {
  _Escenario({
    ResponseBody Function(RequestOptions)? api,
    ResponseBody Function(RequestOptions)? firebase,
  })  : apiFalsa = AdaptadorFalso(api ?? (_) => jsonRespuesta(_loginOk())),
        firebaseFalsa = AdaptadorFalso(firebase ??
            (o) => o.uri.toString().contains('securetoken')
                ? jsonRespuesta(_renovacionOk())
                : jsonRespuesta(_canjeOk())) {
    final apiDio = Dio(BaseOptions(baseUrl: 'http://api.test/api/v1'))
      ..httpClientAdapter = apiFalsa;
    final firebaseDio = Dio()..httpClientAdapter = firebaseFalsa;
    repo = AuthApiRepository(
      api: apiDio,
      firebase: firebaseDio,
      apiKey: _clave,
      ahora: () => ahora,
    );
  }

  final AdaptadorFalso apiFalsa;
  final AdaptadorFalso firebaseFalsa;
  late final AuthApiRepository repo;
  DateTime ahora = DateTime(2026, 11, 21, 10);
}

void main() {
  group('login', () {
    test('canjea el token de Laravel por un ID token y devuelve el usuario', () async {
      final e = _Escenario();

      final usuario = await e.repo.login('  trekker@trekkme.test ', 'clave');

      expect(usuario.id, 'usr-001');
      expect(usuario.nombre, 'Trekker de Prueba');
      expect(await e.repo.idToken(), 'id-1');

      // Laravel recibe el correo sin espacios y la contraseña tal cual
      final login = e.apiFalsa.peticiones.single;
      expect(login.path, '/login');
      expect(login.data, {'email': 'trekker@trekkme.test', 'password': 'clave'});

      // Firebase recibe el token personalizado y la clave del proyecto
      final canje = e.firebaseFalsa.peticiones.single;
      expect(canje.uri.toString(), contains('accounts:signInWithCustomToken'));
      expect(canje.queryParameters['key'], _clave);
      expect(canje.data, {'token': 'token-personalizado', 'returnSecureToken': true});
    });

    test('credenciales malas (422) muestran el mensaje de la API', () async {
      final e = _Escenario(
        api: (_) => jsonRespuesta({
          'message': 'The given data was invalid.',
          'errors': {'email': ['Correo o contraseña incorrectos.']},
        }, 422),
      );

      await expectLater(
        e.repo.login('a@b.cl', 'mala'),
        throwsA(isA<AuthException>()
            .having((x) => x.mensaje, 'mensaje', 'Correo o contraseña incorrectos.')),
      );
      expect(e.firebaseFalsa.peticiones, isEmpty);
      expect(await e.repo.idToken(), isNull);
    });

    test('un perfil sin acceso (403) muestra el mensaje de la API', () async {
      final e = _Escenario(
        api: (_) => jsonRespuesta(
            {'message': 'Este perfil no tiene acceso a la aplicación.'}, 403),
      );

      await expectLater(
        e.repo.login('a@b.cl', 'x'),
        throwsA(isA<AuthException>().having((x) => x.mensaje, 'mensaje',
            'Este perfil no tiene acceso a la aplicación.')),
      );
    });

    test('demasiados intentos (429) pide esperar', () async {
      final e = _Escenario(api: (_) => jsonRespuesta({'message': 'Too Many Attempts.'}, 429));

      await expectLater(
        e.repo.login('a@b.cl', 'x'),
        throwsA(isA<AuthException>()
            .having((x) => x.mensaje, 'mensaje', contains('Demasiados intentos'))),
      );
    });

    test('un carabinero o rescatista no entra a la app del excursionista', () async {
      for (final perfil in ['carabinero', 'rescatista']) {
        final e = _Escenario(api: (_) => jsonRespuesta(_loginOk(perfil: perfil)));

        await expectLater(
          e.repo.login('a@b.cl', 'x'),
          throwsA(isA<AuthException>()
              .having((x) => x.mensaje, 'mensaje', contains('solo para excursionistas'))),
        );
        expect(e.firebaseFalsa.peticiones, isEmpty, reason: 'No debe canjear el token');
        expect(await e.repo.idToken(), isNull);
      }
    });

    test('si Firebase rechaza el canje, no queda sesión', () async {
      final e = _Escenario(firebase: (_) => jsonRespuesta({'error': {'message': 'INVALID_CUSTOM_TOKEN'}}, 400));

      await expectLater(
        e.repo.login('a@b.cl', 'x'),
        throwsA(isA<AuthException>()
            .having((x) => x.mensaje, 'mensaje', contains('No se pudo iniciar sesión'))),
      );
      expect(await e.repo.idToken(), isNull);
    });

    test('sin conexión avisa que no se pudo conectar', () async {
      final e = _Escenario(api: (o) => throw sinRed(o));

      await expectLater(
        e.repo.login('a@b.cl', 'x'),
        throwsA(isA<AuthException>()
            .having((x) => x.mensaje, 'mensaje', 'No se pudo conectar con el servidor.')),
      );
    });
  });

  group('idToken', () {
    test('sin sesión no hay token', () async {
      expect(await _Escenario().repo.idToken(), isNull);
    });

    test('mientras está vigente no se renueva', () async {
      final e = _Escenario();
      await e.repo.login('a@b.cl', 'x');

      e.ahora = e.ahora.add(const Duration(minutes: 30));

      expect(await e.repo.idToken(), 'id-1');
      expect(e.firebaseFalsa.a('securetoken'), isEmpty);
    });

    test('al estar por vencer lo renueva con el refresh token', () async {
      final e = _Escenario();
      await e.repo.login('a@b.cl', 'x');

      // Faltan 30 s para que venza: dentro del margen de renovación
      e.ahora = e.ahora.add(const Duration(minutes: 59, seconds: 30));

      expect(await e.repo.idToken(), 'id-2');

      final renovacion = e.firebaseFalsa.a('securetoken').single;
      expect(renovacion.queryParameters['key'], _clave);
      expect(renovacion.data, {'grant_type': 'refresh_token', 'refresh_token': 'ref-1'});
      expect(renovacion.contentType, Headers.formUrlEncodedContentType);

      // Y el siguiente ya usa el token nuevo sin volver a renovar
      expect(await e.repo.idToken(), 'id-2');
      expect(e.firebaseFalsa.a('securetoken'), hasLength(1));
    });

    test('varias peticiones a la vez comparten una sola renovación', () async {
      final e = _Escenario();
      await e.repo.login('a@b.cl', 'x');
      e.ahora = e.ahora.add(const Duration(hours: 2));

      final tokens = await Future.wait([e.repo.idToken(), e.repo.idToken(), e.repo.idToken()]);

      expect(tokens, ['id-2', 'id-2', 'id-2']);
      expect(e.firebaseFalsa.a('securetoken'), hasLength(1));
    });

    test('si la renovación falla, cierra la sesión', () async {
      final e = _Escenario(
        firebase: (o) => o.uri.toString().contains('securetoken')
            ? jsonRespuesta({'error': {'message': 'TOKEN_EXPIRED'}}, 400)
            : jsonRespuesta(_canjeOk()),
      );
      await e.repo.login('a@b.cl', 'x');
      e.ahora = e.ahora.add(const Duration(hours: 2));

      expect(await e.repo.idToken(), isNull);
      // Ya no queda nada que renovar: no insiste
      expect(await e.repo.idToken(), isNull);
      expect(e.firebaseFalsa.a('securetoken'), hasLength(1));
    });

    test('logout borra la sesión', () async {
      final e = _Escenario();
      await e.repo.login('a@b.cl', 'x');

      await e.repo.logout();

      expect(await e.repo.idToken(), isNull);
    });
  });
}
