import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:trekkme/core/api_exception.dart';
import 'package:trekkme/core/telefono.dart';
import 'package:trekkme/data/excursion_repository.dart';
import 'package:trekkme/features/excursiones/excursiones_page.dart';
import 'package:trekkme/models/excursion.dart';
import 'package:trekkme/models/sendero.dart';

Excursion _excursion(EstadoExcursion estado) => Excursion(
      id: 'exc-1',
      sendero: const Sendero(id: 'sen-001', nombre: 'Sendero Los Canelos'),
      salidaAt: DateTime(2026, 11, 21, 11),
      retornoAt: DateTime(2026, 11, 21, 17),
      estado: estado,
      contactoNombre: 'Paula',
      contactoTelefono: '+56 9 5555 0108',
    );

class _Repo implements ExcursionRepository {
  _Repo(EstadoExcursion estado) : actual = _excursion(estado);

  Excursion actual;
  ApiException? fallo;
  final llamadas = <String>[];

  Future<Excursion> _hacer(String llamada, Excursion nueva) async {
    llamadas.add(llamada);
    if (fallo != null) throw fallo!;
    return actual = nueva;
  }

  @override
  Future<List<Excursion>> misExcursiones() async => [actual];

  @override
  Future<Excursion> iniciar(Excursion e) =>
      _hacer('iniciar', e.copyWith(estado: EstadoExcursion.enCurso));

  @override
  Future<Excursion> regresar(Excursion e) =>
      _hacer('regresar', e.copyWith(estado: EstadoExcursion.cerrada));

  @override
  Future<Excursion> enviarUbicacion(Excursion e,
          {required double lat, required double lng, int? bateria}) =>
      _hacer('ubicacion $lat $lng $bateria', e);

  @override
  Future<List<Sendero>> senderos() => throw UnimplementedError();

  @override
  Future<Excursion> crear(NuevaExcursion d) => throw UnimplementedError();
}

Future<void> _abrir(WidgetTester tester, _Repo repo, {LeerTelefono? leer}) async {
  await tester.pumpWidget(MaterialApp(
    home: ExcursionesPage(
      repo: repo,
      nombreUsuario: 'Trekker',
      onLogout: () {},
      leer: leer ?? () async => (lat: -36.88, lng: -72.97, bateria: 55),
    ),
  ));
  await tester.pumpAndSettle();
}

Future<void> _tocar(WidgetTester tester, String texto) async {
  await tester.tap(find.text(texto));
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('cada estado muestra solo sus botones', (tester) async {
    const esperados = {
      EstadoExcursion.planificada: ['Iniciar salida'],
      EstadoExcursion.enCurso: ['Enviar ubicación', 'Ya volví'],
      EstadoExcursion.enAlerta: ['Enviar ubicación'],
      EstadoExcursion.enOperativo: ['Enviar ubicación'],
      EstadoExcursion.cierrePendiente: ['Enviar ubicación'],
      EstadoExcursion.cerrada: <String>[],
    };
    for (final MapEntry(key: estado, value: visibles) in esperados.entries) {
      await tester.pumpWidget(const SizedBox());
      await _abrir(tester, _Repo(estado));
      for (final boton in ['Iniciar salida', 'Enviar ubicación', 'Ya volví']) {
        expect(find.text(boton),
            visibles.contains(boton) ? findsOneWidget : findsNothing,
            reason: '$estado: $boton');
      }
    }
  });

  testWidgets('iniciar la salida actualiza el estado', (tester) async {
    final repo = _Repo(EstadoExcursion.planificada);
    await _abrir(tester, repo);

    await _tocar(tester, 'Iniciar salida');

    expect(repo.llamadas, ['iniciar']);
    expect(find.text('En curso'), findsOneWidget);
    expect(find.text('Salida iniciada.'), findsOneWidget);
  });

  testWidgets('envía la posición y la batería del teléfono', (tester) async {
    final repo = _Repo(EstadoExcursion.enCurso);
    await _abrir(tester, repo);

    await _tocar(tester, 'Enviar ubicación');

    expect(repo.llamadas, ['ubicacion -36.88 -72.97 55']);
    expect(find.text('Ubicación enviada.'), findsOneWidget);
  });

  testWidgets('sin ubicación no envía nada y explica por qué', (tester) async {
    final repo = _Repo(EstadoExcursion.enCurso);
    await _abrir(tester, repo,
        leer: () async => throw const ApiException('Activa la ubicación del teléfono.'));

    await _tocar(tester, 'Enviar ubicación');

    expect(repo.llamadas, isEmpty);
    expect(find.text('Activa la ubicación del teléfono.'), findsOneWidget);
  });

  testWidgets('"Ya volví" pide confirmación', (tester) async {
    final repo = _Repo(EstadoExcursion.enCurso);
    await _abrir(tester, repo);

    await _tocar(tester, 'Ya volví');
    await _tocar(tester, 'Cancelar');
    expect(repo.llamadas, isEmpty);

    await _tocar(tester, 'Ya volví');
    await _tocar(tester, 'Sí, ya volví');
    expect(repo.llamadas, ['regresar']);
    expect(find.text('Cerrada'), findsOneWidget);
  });

  testWidgets('si la API responde 409 muestra el mensaje y recarga', (tester) async {
    final repo = _Repo(EstadoExcursion.planificada);
    await _abrir(tester, repo);
    repo
      ..fallo = const ApiException('Tu excursión cambió de estado.', statusCode: 409)
      ..actual = _excursion(EstadoExcursion.enAlerta);

    await _tocar(tester, 'Iniciar salida');

    expect(find.text('Tu excursión cambió de estado.'), findsOneWidget);
    expect(find.text('En alerta'), findsOneWidget);
  });
}
