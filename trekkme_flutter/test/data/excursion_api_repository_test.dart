import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:trekkme/core/api_exception.dart';
import 'package:trekkme/data/excursion_api_repository.dart';
import 'package:trekkme/models/excursion.dart';
import 'package:trekkme/models/sendero.dart';

import '../support/adaptador_falso.dart';

// Las respuestas replican el contrato de laravel/API.md.
Map<String, dynamic> _excursionJson({
  String id = 'exc-010',
  String estado = 'planificada',
}) =>
    {
      'id': id,
      'sendero_id': 'sen-001',
      'sendero': 'PN Nahuelbuta - Piedra del Águila',
      'estado': estado,
      'salida_at': '2026-11-21T08:00:00-03:00',
      'retorno_at': '2026-11-21T16:00:00-03:00',
      'contacto_nombre': 'Paula Díaz',
      'contacto_telefono': '+56 9 5555 0108',
      'detalle': null,
      'lleva_carpa': false,
      'color_carpa': null,
      'lleva_linterna': true,
      'acompanantes': [
        {'nombre': 'Tomás Garrido', 'rut': '20.000.015-5'},
      ],
      'ultima_lat': null,
      'ultima_lng': null,
      'ultima_ubicacion_at': null,
      'bateria': null,
    };

Excursion _excursion({EstadoExcursion estado = EstadoExcursion.planificada}) =>
    Excursion.fromJson(_excursionJson(estado: estado.api));

class _Escenario {
  _Escenario(ResponseBody Function(RequestOptions) responder)
      : falso = AdaptadorFalso(responder) {
    final dio = Dio(BaseOptions(baseUrl: 'http://api.test/api/v1'))
      ..httpClientAdapter = falso;
    repo = ExcursionApiRepository(dio);
  }

  final AdaptadorFalso falso;
  late final ExcursionApiRepository repo;
  RequestOptions get peticion => falso.peticiones.single;
}

NuevaExcursion _nueva({bool carpa = false, String? detalle}) => NuevaExcursion(
      sendero: const Sendero(id: 'sen-001', nombre: 'PN Nahuelbuta'),
      // 08:00 y 16:00 hora de Chile (UTC-3) = 11:00 y 19:00 UTC
      salidaAt: DateTime.utc(2026, 11, 21, 11),
      retornoAt: DateTime.utc(2026, 11, 21, 19),
      contactoNombre: 'Paula Díaz',
      contactoTelefono: '+56 9 5555 0108',
      rut: '20.000.004-4',
      telefono: '+56 9 5555 0107',
      edad: 23,
      experiencia: 'Principiante',
      condicionesMedicas: 'Alergia',
      detalle: detalle,
      llevaCarpa: carpa,
      colorCarpa: carpa ? 'Naranja' : null,
      llevaLinterna: true,
      acompanantes: const [
        Acompanante(nombre: 'Catalina Ríos', rut: '20.000.016-6', edad: 22, condicionesMedicas: 'Asma'),
      ],
    );

void main() {
  group('lectura', () {
    test('senderos lee el catálogo', () async {
      final e = _Escenario((_) => jsonRespuesta({
            'data': [
              {'id': 'sen-001', 'nombre': 'PN Nahuelbuta', 'jurisdiccion_id': 'jur-001'},
              {'id': 'sen-002', 'nombre': 'PN Nonguén', 'jurisdiccion_id': 'jur-001'},
            ],
          }));

      final lista = await e.repo.senderos();

      expect(e.peticion.method, 'GET');
      expect(e.peticion.path, '/senderos');
      expect(lista.map((s) => s.id), ['sen-001', 'sen-002']);
      expect(lista.first.nombre, 'PN Nahuelbuta');
    });

    test('misExcursiones convierte el JSON en excursiones', () async {
      final e = _Escenario((_) => jsonRespuesta({
            'data': [
              _excursionJson(),
              _excursionJson(id: 'exc-011', estado: 'en_curso')
                ..['ultima_lat'] = -37.8235
                ..['ultima_lng'] = -73.0312
                ..['ultima_ubicacion_at'] = '2026-11-21T10:30:00-03:00'
                ..['bateria'] = 78
                ..['detalle'] = 'Polerón amarillo'
                ..['lleva_carpa'] = true
                ..['color_carpa'] = 'Verde',
            ],
          }));

      final lista = await e.repo.misExcursiones();

      expect(e.peticion.path, '/excursionista/excursiones');
      expect(lista, hasLength(2));

      final a = lista[0];
      expect(a.id, 'exc-010');
      expect(a.sendero.id, 'sen-001');
      expect(a.sendero.nombre, 'PN Nahuelbuta - Piedra del Águila');
      expect(a.estado, EstadoExcursion.planificada);
      expect(a.detalle, isNull);
      expect(a.colorCarpa, isNull);
      expect(a.llevaLinterna, isTrue);
      expect(a.acompanantes.single.nombre, 'Tomás Garrido');
      expect(a.acompanantes.single.rut, '20.000.015-5');
      expect(a.ultimaLat, isNull);
      expect(a.ultimaUbicacionAt, isNull);
      expect(a.bateria, isNull);

      final b = lista[1];
      expect(b.estado, EstadoExcursion.enCurso);
      expect(b.ultimaLat, -37.8235);
      expect(b.ultimaLng, -73.0312);
      expect(b.bateria, 78);
      expect(b.detalle, 'Polerón amarillo');
      expect(b.llevaCarpa, isTrue);
      expect(b.colorCarpa, 'Verde');
    });

    test('las fechas se muestran en hora local y representan el mismo instante', () async {
      final e = _Escenario((_) => jsonRespuesta({'data': [_excursionJson()]}));

      final x = (await e.repo.misExcursiones()).single;

      expect(x.salidaAt.isUtc, isFalse);
      // 08:00 en UTC-3 es 11:00 UTC, sin importar la zona de quien ejecute el test
      expect(x.salidaAt.toUtc(), DateTime.utc(2026, 11, 21, 11));
      expect(x.retornoAt.toUtc(), DateTime.utc(2026, 11, 21, 19));
    });

    test('sin excursiones devuelve una lista vacía', () async {
      final e = _Escenario((_) => jsonRespuesta({'data': []}));

      expect(await e.repo.misExcursiones(), isEmpty);
    });

    test('"cierre_pendiente" se reconoce pero se muestra como "En operativo"', () async {
      final e = _Escenario((_) => jsonRespuesta({'data': [_excursionJson(estado: 'cierre_pendiente')]}));

      final x = (await e.repo.misExcursiones()).single;

      expect(x.estado, EstadoExcursion.cierrePendiente);
      expect(x.estado.etiquetaPublica, 'En operativo');
    });
  });

  group('crear', () {
    test('envía el cuerpo del contrato con fechas en UTC', () async {
      final e = _Escenario((_) => jsonRespuesta({'data': _excursionJson()}, 201));

      final creada = await e.repo.crear(_nueva(detalle: 'Polerón amarillo'));

      expect(e.peticion.method, 'POST');
      expect(e.peticion.path, '/excursionista/excursiones');
      expect(e.peticion.data, {
        'sendero_id': 'sen-001',
        'salida_at': '2026-11-21T11:00:00.000Z',
        'retorno_at': '2026-11-21T19:00:00.000Z',
        'contacto_nombre': 'Paula Díaz',
        'contacto_telefono': '+56 9 5555 0108',
        'rut': '20.000.004-4',
        'telefono': '+56 9 5555 0107',
        'edad': 23,
        'experiencia': 'Principiante',
        'condiciones_medicas': 'Alergia',
        'detalle': 'Polerón amarillo',
        'lleva_carpa': false,
        'lleva_linterna': true,
        'acompanantes': [
          {'nombre': 'Catalina Ríos', 'rut': '20.000.016-6', 'edad': 22, 'condiciones_medicas': 'Asma'},
        ],
      });
      expect(creada.id, 'exc-010');
      expect(creada.estado, EstadoExcursion.planificada);
    });

    test('un detalle vacío no se envía y el color de carpa solo si lleva carpa', () async {
      final e = _Escenario((_) => jsonRespuesta({'data': _excursionJson()}, 201));
      await e.repo.crear(_nueva(detalle: '   '));
      final sinCarpa = e.peticion.data as Map;
      expect(sinCarpa.containsKey('detalle'), isFalse);
      expect(sinCarpa.containsKey('color_carpa'), isFalse);

      final f = _Escenario((_) => jsonRespuesta({'data': _excursionJson()}, 201));
      await f.repo.crear(_nueva(carpa: true));
      final conCarpa = f.peticion.data as Map;
      expect(conCarpa['lleva_carpa'], isTrue);
      expect(conCarpa['color_carpa'], 'Naranja');
    });

    test('una fecha en hora local se envía en UTC, sin perder el instante', () async {
      // Hora local (sin zona): lo que entrega un selector de fecha del teléfono
      final salida = DateTime(2026, 11, 21, 8);
      final retorno = DateTime(2026, 11, 21, 16);
      final e = _Escenario((_) => jsonRespuesta({'data': _excursionJson()}, 201));

      await e.repo.crear(NuevaExcursion(
        sendero: const Sendero(id: 'sen-001', nombre: 'PN Nahuelbuta'),
        salidaAt: salida,
        retornoAt: retorno,
        contactoNombre: 'Paula',
        contactoTelefono: '+56 9 1',
        rut: '1-9',
        telefono: '+56 9 2',
        edad: 30,
        experiencia: 'Avanzado',
      ));

      final cuerpo = e.peticion.data as Map;
      expect(cuerpo['salida_at'], endsWith('Z'), reason: 'Sin zona, el servidor la leería en otra hora');
      expect(cuerpo['retorno_at'], endsWith('Z'));
      expect(DateTime.parse(cuerpo['salida_at'] as String), salida.toUtc());
      expect(DateTime.parse(cuerpo['retorno_at'] as String), retorno.toUtc());
    });

    test('el color de la carpa no se envía si no lleva carpa', () async {
      final e = _Escenario((_) => jsonRespuesta({'data': _excursionJson()}, 201));

      await e.repo.crear(NuevaExcursion(
        sendero: const Sendero(id: 'sen-001', nombre: 'PN Nahuelbuta'),
        salidaAt: DateTime.utc(2026, 11, 21, 11),
        retornoAt: DateTime.utc(2026, 11, 21, 19),
        contactoNombre: 'Paula',
        contactoTelefono: '+56 9 1',
        rut: '1-9',
        telefono: '+56 9 2',
        edad: 30,
        experiencia: 'Avanzado',
        llevaCarpa: false,
        colorCarpa: 'Naranja', // quedó escrito en el formulario antes de desmarcar la carpa
      ));

      final cuerpo = e.peticion.data as Map;
      expect(cuerpo['lleva_carpa'], isFalse);
      expect(cuerpo.containsKey('color_carpa'), isFalse);
    });

    test('un 422 muestra el mensaje del campo que falló', () async {
      final e = _Escenario((_) => jsonRespuesta({
            'message': 'The given data was invalid.',
            'errors': {'sendero_id': ['El sendero no existe.']},
          }, 422));

      await expectLater(
        e.repo.crear(_nueva()),
        throwsA(isA<ApiException>()
            .having((x) => x.mensaje, 'mensaje', 'El sendero no existe.')
            .having((x) => x.statusCode, 'status', 422)),
      );
    });
  });

  group('iniciar y regresar', () {
    test('iniciar manda el estado que la app tiene en pantalla y actualiza el estado', () async {
      final e = _Escenario((_) => jsonRespuesta({'data': {'id': 'exc-010', 'estado': 'en_curso'}}));

      final nueva = await e.repo.iniciar(_excursion());

      expect(e.peticion.method, 'POST');
      expect(e.peticion.path, '/excursionista/excursiones/exc-010/iniciar');
      expect(e.peticion.data, {'estado_esperado': 'planificada'});
      expect(nueva.estado, EstadoExcursion.enCurso);
      // Lo demás se conserva (la API solo devuelve id y estado)
      expect(nueva.sendero.nombre, 'PN Nahuelbuta - Piedra del Águila');
      expect(nueva.acompanantes, hasLength(1));
    });

    test('regresar cierra la excursión', () async {
      final e = _Escenario((_) => jsonRespuesta({'data': {'id': 'exc-010', 'estado': 'cerrada'}}));

      final nueva = await e.repo.regresar(_excursion(estado: EstadoExcursion.enCurso));

      expect(e.peticion.path, '/excursionista/excursiones/exc-010/regresar');
      expect(e.peticion.data, {'estado_esperado': 'en_curso'});
      expect(nueva.estado, EstadoExcursion.cerrada);
    });

    test('si el servidor ya la pasó a alerta responde 409 con el mensaje de la API', () async {
      final e = _Escenario((_) => jsonRespuesta(
          {'message': 'Tu excursión cambió de estado. Actualiza e intenta de nuevo.'}, 409));

      await expectLater(
        e.repo.regresar(_excursion(estado: EstadoExcursion.enCurso)),
        throwsA(isA<ApiException>()
            .having((x) => x.conflicto, 'conflicto', isTrue)
            .having((x) => x.mensaje, 'mensaje',
                'Tu excursión cambió de estado. Actualiza e intenta de nuevo.')),
      );
    });
  });

  group('enviarUbicacion', () {
    test('envía lat, lng y batería y mezcla la respuesta con la excursión', () async {
      final e = _Escenario((_) => jsonRespuesta({
            'data': {
              'id': 'exc-010',
              'ultima_lat': -37.8235,
              'ultima_lng': -73.0312,
              'ultima_ubicacion_at': '2026-11-21T13:30:00+00:00',
              'bateria': 78,
            },
          }));

      final nueva = await e.repo.enviarUbicacion(
        _excursion(estado: EstadoExcursion.enCurso),
        lat: -37.8235,
        lng: -73.0312,
        bateria: 78,
      );

      expect(e.peticion.path, '/excursionista/excursiones/exc-010/ubicacion');
      expect(e.peticion.data, {'lat': -37.8235, 'lng': -73.0312, 'bateria': 78});
      expect(nueva.ultimaLat, -37.8235);
      expect(nueva.ultimaLng, -73.0312);
      expect(nueva.bateria, 78);
      expect(nueva.ultimaUbicacionAt!.toUtc(), DateTime.utc(2026, 11, 21, 13, 30));
      expect(nueva.estado, EstadoExcursion.enCurso);
      expect(nueva.id, 'exc-010');
    });

    test('la batería es opcional: no se envía y queda sin dato', () async {
      final e = _Escenario((_) => jsonRespuesta({
            'data': {
              'id': 'exc-010',
              'ultima_lat': -37.8,
              'ultima_lng': -73.0,
              'ultima_ubicacion_at': '2026-11-21T13:30:00+00:00',
              'bateria': null,
            },
          }));

      final nueva = await e.repo.enviarUbicacion(
        _excursion(estado: EstadoExcursion.enCurso),
        lat: -37.8,
        lng: -73.0,
      );

      expect((e.peticion.data as Map).containsKey('bateria'), isFalse);
      expect(nueva.bateria, isNull);
    });
  });

  group('errores', () {
    test('sin conexión da un ApiException sin código', () async {
      final e = _Escenario((o) => throw sinRed(o));

      await expectLater(
        e.repo.misExcursiones(),
        throwsA(isA<ApiException>()
            .having((x) => x.mensaje, 'mensaje', 'No se pudo conectar con el servidor.')
            .having((x) => x.statusCode, 'status', isNull)),
      );
    });

    test('un 403 muestra el mensaje de la API', () async {
      final e = _Escenario(
          (_) => jsonRespuesta({'message': 'No tienes acceso a esta excursión.'}, 403));

      await expectLater(
        e.repo.iniciar(_excursion()),
        throwsA(isA<ApiException>()
            .having((x) => x.mensaje, 'mensaje', 'No tienes acceso a esta excursión.')),
      );
    });

    test('un 401 se reconoce como sesión vencida', () async {
      final e = _Escenario((_) => jsonRespuesta({'message': 'Tu sesión expiró.'}, 401));

      await expectLater(
        e.repo.misExcursiones(),
        throwsA(isA<ApiException>().having((x) => x.sesionVencida, 'sesionVencida', isTrue)),
      );
    });
  });
}
