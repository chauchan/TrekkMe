import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:trekkme/core/api_exception.dart';
import 'package:trekkme/data/excursion_demo_repository.dart';
import 'package:trekkme/data/excursion_repository.dart';
import 'package:trekkme/features/aviso/aviso_page.dart';
import 'package:trekkme/features/excursiones/excursiones_page.dart';
import 'package:trekkme/models/excursion.dart';
import 'package:trekkme/models/sendero.dart';

const _nahuelbuta = Sendero(id: 'sen-001', nombre: 'PN Nahuelbuta - Piedra del Águila');
const _nonguen = Sendero(id: 'sen-002', nombre: 'PN Nonguén - Sendero Los Copihues');

// "Ahora" fijo: las fechas por defecto salen de aquí (salida 11:00, retorno 17:00)
final _ahora = DateTime(2026, 11, 21, 10, 30);

/// Repositorio que guarda lo que recibe y deja controlar las respuestas.
class _RepoFalso implements ExcursionRepository {
  _RepoFalso({this.alCrear, Future<List<Sendero>> Function()? senderos})
      : _senderos = senderos ?? (() async => [_nahuelbuta, _nonguen]);

  final Future<List<Sendero>> Function() _senderos;
  Future<Excursion> Function(NuevaExcursion)? alCrear;
  final List<NuevaExcursion> recibidas = [];

  @override
  Future<List<Sendero>> senderos() => _senderos();

  @override
  Future<Excursion> crear(NuevaExcursion d) {
    recibidas.add(d);
    return alCrear != null ? alCrear!(d) : Future.value(_creada(d));
  }

  @override
  Future<List<Excursion>> misExcursiones() async => [];

  @override
  Future<Excursion> iniciar(Excursion e) => throw UnimplementedError();

  @override
  Future<Excursion> regresar(Excursion e) => throw UnimplementedError();

  @override
  Future<Excursion> enviarUbicacion(Excursion e, {required double lat, required double lng, int? bateria}) =>
      throw UnimplementedError();
}

Excursion _creada(NuevaExcursion d) => Excursion(
      id: 'exc-100',
      sendero: d.sendero,
      salidaAt: d.salidaAt,
      retornoAt: d.retornoAt,
      estado: EstadoExcursion.planificada,
      contactoNombre: d.contactoNombre,
      contactoTelefono: d.contactoTelefono,
    );

/// Pantalla alta para que el formulario completo esté construido sin hacer scroll.
void _pantallaAlta(WidgetTester tester) {
  tester.view.physicalSize = const Size(800, 3600);
  tester.view.devicePixelRatio = 1.0;
  addTearDown(tester.view.reset);
}

/// Abre el formulario desde una pantalla de partida y guarda lo que devuelve.
Future<ValueNotifier<Excursion?>> _abrir(WidgetTester tester, ExcursionRepository repo) async {
  final resultado = ValueNotifier<Excursion?>(null);
  await tester.pumpWidget(MaterialApp(
    home: Builder(
      builder: (context) => Scaffold(
        body: Center(
          child: TextButton(
            onPressed: () async {
              resultado.value = await Navigator.push<Excursion>(
                context,
                MaterialPageRoute(builder: (_) => AvisoPage(repo: repo, ahora: () => _ahora)),
              );
            },
            child: const Text('abrir'),
          ),
        ),
      ),
    ),
  ));
  await tester.tap(find.text('abrir'));
  await tester.pumpAndSettle();
  return resultado;
}

Finder _campo(String etiqueta) => find.widgetWithText(TextFormField, etiqueta);
final _botonEnviar = find.widgetWithText(FilledButton, 'Avisar salida');

Future<void> _elegirSendero(WidgetTester tester, String nombre) async {
  await tester.tap(find.byKey(const ValueKey('campo-sendero')));
  await tester.pumpAndSettle();
  await tester.tap(find.text(nombre).last);
  await tester.pumpAndSettle();
}

/// Llena los datos obligatorios con valores válidos.
Future<void> _llenarObligatorios(WidgetTester tester) async {
  await _elegirSendero(tester, _nonguen.nombre);
  await tester.enterText(_campo('Nombre (ej. Paula Díaz, madre)'), '  Paula Díaz  ');
  await tester.enterText(_campo('Teléfono del contacto'), '+56 9 5555 0108');
  await tester.enterText(_campo('RUT'), '123456785');
  await tester.enterText(_campo('Tu teléfono'), '+56 9 5555 0107');
  await tester.enterText(_campo('Edad'), '23');
}

Future<void> _enviar(WidgetTester tester) async {
  await tester.ensureVisible(_botonEnviar);
  await tester.tap(_botonEnviar);
  await tester.pumpAndSettle();
}

void main() {
  group('formulario', () {
    testWidgets('muestra los senderos y sugiere salida en la próxima hora y retorno 6 h después', (tester) async {
      _pantallaAlta(tester);
      await _abrir(tester, _RepoFalso());

      expect(find.text('Avisar salida'), findsNWidgets(2)); // título y botón
      expect(find.text('21/11/2026  11:00'), findsOneWidget);
      expect(find.text('21/11/2026  17:00'), findsOneWidget);
      expect(find.text('Voy solo.'), findsOneWidget);

      await tester.tap(find.byKey(const ValueKey('campo-sendero')));
      await tester.pumpAndSettle();
      expect(find.text(_nahuelbuta.nombre), findsOneWidget);
      expect(find.text(_nonguen.nombre), findsOneWidget);
    });

    testWidgets('vacío: marca los obligatorios y no envía nada', (tester) async {
      _pantallaAlta(tester);
      final repo = _RepoFalso();
      final resultado = await _abrir(tester, repo);

      await _enviar(tester);

      expect(find.text('Elige un sendero.'), findsOneWidget);
      expect(find.text('Ingresa el nombre del contacto.'), findsOneWidget);
      expect(find.text('Ingresa un teléfono.'), findsNWidgets(2));
      expect(find.text('RUT no válido.'), findsOneWidget);
      expect(find.text('Edad entre 1 y 120.'), findsOneWidget);
      expect(repo.recibidas, isEmpty);
      expect(resultado.value, isNull);
    });

    testWidgets('valida el RUT, la edad y los teléfonos', (tester) async {
      _pantallaAlta(tester);
      final repo = _RepoFalso();
      await _abrir(tester, repo);
      await _llenarObligatorios(tester);

      await tester.enterText(_campo('RUT'), '12.345.678-4'); // verificador equivocado
      await tester.enterText(_campo('Edad'), '150');
      await tester.enterText(_campo('Tu teléfono'), 'no es un teléfono');
      await _enviar(tester);

      expect(find.text('RUT no válido.'), findsOneWidget);
      expect(find.text('Edad entre 1 y 120.'), findsOneWidget);
      expect(find.text('Teléfono no válido.'), findsOneWidget);
      expect(repo.recibidas, isEmpty);
    });

    testWidgets('envía los datos, normaliza el RUT y devuelve la excursión creada', (tester) async {
      _pantallaAlta(tester);
      final repo = _RepoFalso();
      final resultado = await _abrir(tester, repo);
      await _llenarObligatorios(tester);

      await _enviar(tester);

      final d = repo.recibidas.single;
      expect(d.sendero, _nonguen);
      expect(d.salidaAt, DateTime(2026, 11, 21, 11));
      expect(d.retornoAt, DateTime(2026, 11, 21, 17));
      expect(d.contactoNombre, 'Paula Díaz'); // sin espacios sobrantes
      expect(d.contactoTelefono, '+56 9 5555 0108');
      expect(d.rut, '12.345.678-5'); // llegó como 123456785
      expect(d.telefono, '+56 9 5555 0107');
      expect(d.edad, 23);
      expect(d.experiencia, 'Principiante');
      expect(d.condicionesMedicas, 'Ninguna');
      expect(d.llevaCarpa, isFalse);
      expect(d.llevaLinterna, isFalse);
      expect(d.acompanantes, isEmpty);

      // Vuelve a la pantalla anterior con la excursión creada
      expect(resultado.value?.id, 'exc-100');
      expect(find.byType(AvisoPage), findsNothing);
    });

    testWidgets('envía experiencia, condición médica, detalle y equipo', (tester) async {
      _pantallaAlta(tester);
      final repo = _RepoFalso();
      await _abrir(tester, repo);
      await _llenarObligatorios(tester);

      await tester.tap(find.byKey(const ValueKey('campo-experiencia')));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Avanzado').last);
      await tester.pumpAndSettle();
      await tester.enterText(_campo('Condición médica (opcional)'), 'Asma');
      await tester.enterText(find.widgetWithText(TextFormField, 'Cómo vas vestido, qué llevas, cómo reconocerte…'), 'Polerón amarillo');

      // El color de la carpa solo aparece si lleva carpa
      expect(_campo('Color de la carpa (opcional)'), findsNothing);
      await tester.tap(find.widgetWithText(SwitchListTile, 'Llevo carpa'));
      await tester.pumpAndSettle();
      await tester.enterText(_campo('Color de la carpa (opcional)'), 'Naranja');
      await tester.tap(find.widgetWithText(SwitchListTile, 'Llevo linterna'));
      await tester.pumpAndSettle();

      await _enviar(tester);

      final d = repo.recibidas.single;
      expect(d.experiencia, 'Avanzado');
      expect(d.condicionesMedicas, 'Asma');
      expect(d.detalle, 'Polerón amarillo');
      expect(d.llevaCarpa, isTrue);
      expect(d.colorCarpa, 'Naranja');
      expect(d.llevaLinterna, isTrue);
    });

    testWidgets('si la API rechaza el aviso muestra su mensaje y permite reintentar', (tester) async {
      _pantallaAlta(tester);
      var intentos = 0;
      final repo = _RepoFalso(alCrear: (d) async {
        if (++intentos == 1) throw const ApiException('El sendero no existe.', statusCode: 422);
        return _creada(d);
      });
      final resultado = await _abrir(tester, repo);
      await _llenarObligatorios(tester);

      await _enviar(tester);

      expect(find.text('El sendero no existe.'), findsOneWidget);
      expect(find.byType(AvisoPage), findsOneWidget, reason: 'No debe cerrar el formulario');
      expect(resultado.value, isNull);
      // Los datos escritos se conservan y el botón vuelve a estar disponible
      expect(find.text('123456785'), findsOneWidget);
      expect(tester.widget<FilledButton>(_botonEnviar).onPressed, isNotNull);

      await _enviar(tester);

      expect(repo.recibidas, hasLength(2));
      expect(resultado.value?.id, 'exc-100');
    });

    testWidgets('un error inesperado también se avisa', (tester) async {
      _pantallaAlta(tester);
      final repo = _RepoFalso(alCrear: (_) async => throw StateError('algo raro'));
      await _abrir(tester, repo);
      await _llenarObligatorios(tester);

      await _enviar(tester);

      expect(find.text('No se pudo avisar la salida. Intenta de nuevo.'), findsOneWidget);
    });

    testWidgets('mientras envía no se puede enviar dos veces', (tester) async {
      _pantallaAlta(tester);
      final respuesta = Completer<Excursion>();
      final repo = _RepoFalso(alCrear: (_) => respuesta.future);
      final resultado = await _abrir(tester, repo);
      await _llenarObligatorios(tester);

      await tester.ensureVisible(_botonEnviar);
      await tester.tap(_botonEnviar);
      await tester.pump();

      // Al enviar, el texto del botón se reemplaza por un spinner
      final boton = find.byType(FilledButton);
      expect(tester.widget<FilledButton>(boton).onPressed, isNull);
      expect(find.byType(CircularProgressIndicator), findsOneWidget);
      await tester.tap(boton, warnIfMissed: false);
      await tester.pump();
      expect(repo.recibidas, hasLength(1));

      respuesta.complete(_creada(repo.recibidas.single));
      await tester.pumpAndSettle();
      expect(resultado.value?.id, 'exc-100');
    });
  });

  group('horario', () {
    test('validarRetorno exige que sea posterior a la salida', () {
      final salida = DateTime(2026, 11, 21, 11);

      expect(validarRetorno(salida, DateTime(2026, 11, 21, 17)), isNull);
      expect(validarRetorno(salida, DateTime(2026, 11, 21, 11, 1)), isNull);
      expect(validarRetorno(salida, salida), isNotNull, reason: 'Igual a la salida no vale');
      expect(validarRetorno(salida, DateTime(2026, 11, 21, 10)), isNotNull);
      expect(validarRetorno(salida, null), isNotNull);
    });

    testWidgets('si la salida se mueve después del retorno, no deja enviar', (tester) async {
      _pantallaAlta(tester);
      final repo = _RepoFalso();
      await _abrir(tester, repo);
      await _llenarObligatorios(tester);

      // Salida: 25/11 a la hora que propone el selector. El retorno sigue el 21/11
      await tester.tap(find.byKey(const ValueKey('campo-salida')));
      await tester.pumpAndSettle();
      await tester.tap(find.text('25'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('OK'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('OK'));
      await tester.pumpAndSettle();
      expect(find.textContaining('25/11/2026'), findsOneWidget);

      await _enviar(tester);

      expect(find.text('El retorno debe ser posterior a la salida.'), findsOneWidget);
      expect(repo.recibidas, isEmpty);
    });
  });

  group('acompañantes', () {
    testWidgets('se agregan, se listan, se envían y se pueden quitar', (tester) async {
      _pantallaAlta(tester);
      final repo = _RepoFalso();
      await _abrir(tester, repo);
      await _llenarObligatorios(tester);

      Finder dialogo(String etiqueta) =>
          find.descendant(of: find.byType(AlertDialog), matching: find.widgetWithText(TextFormField, etiqueta));

      await tester.tap(find.text('Agregar acompañante'));
      await tester.pumpAndSettle();
      await tester.enterText(dialogo('Nombre completo'), 'Catalina Ríos');
      await tester.enterText(dialogo('RUT'), '76543216');
      await tester.enterText(dialogo('Edad'), '22');
      await tester.enterText(dialogo('Condición médica (opcional)'), 'Asma');
      await tester.tap(find.widgetWithText(FilledButton, 'Agregar'));
      await tester.pumpAndSettle();

      expect(find.text('Voy solo.'), findsNothing);
      expect(find.text('Catalina Ríos'), findsOneWidget);
      expect(find.text('7.654.321-6 · 22 años'), findsOneWidget);

      // Segundo acompañante, sin condición médica
      await tester.tap(find.text('Agregar acompañante'));
      await tester.pumpAndSettle();
      await tester.enterText(dialogo('Nombre completo'), 'Tomás Garrido');
      await tester.enterText(dialogo('RUT'), '11.111.111-1');
      await tester.enterText(dialogo('Edad'), '0');
      await tester.tap(find.widgetWithText(FilledButton, 'Agregar'));
      await tester.pumpAndSettle();

      // Quitar al segundo
      await tester.tap(find.byTooltip('Quitar a Tomás Garrido'));
      await tester.pumpAndSettle();
      expect(find.text('Tomás Garrido'), findsNothing);

      await _enviar(tester);

      final a = repo.recibidas.single.acompanantes.single;
      expect(a.nombre, 'Catalina Ríos');
      expect(a.rut, '7.654.321-6');
      expect(a.edad, 22);
      expect(a.condicionesMedicas, 'Asma');
    });

    testWidgets('sin condición médica queda "Ninguna"', (tester) async {
      _pantallaAlta(tester);
      final repo = _RepoFalso();
      await _abrir(tester, repo);
      await _llenarObligatorios(tester);

      await tester.tap(find.text('Agregar acompañante'));
      await tester.pumpAndSettle();
      Finder dialogo(String etiqueta) =>
          find.descendant(of: find.byType(AlertDialog), matching: find.widgetWithText(TextFormField, etiqueta));
      await tester.enterText(dialogo('Nombre completo'), 'Ana');
      await tester.enterText(dialogo('RUT'), '11.111.111-1');
      await tester.enterText(dialogo('Edad'), '30');
      await tester.tap(find.widgetWithText(FilledButton, 'Agregar'));
      await tester.pumpAndSettle();
      await _enviar(tester);

      expect(repo.recibidas.single.acompanantes.single.condicionesMedicas, 'Ninguna');
    });

    testWidgets('el diálogo valida los datos y se puede cancelar', (tester) async {
      _pantallaAlta(tester);
      await _abrir(tester, _RepoFalso());

      await tester.tap(find.text('Agregar acompañante'));
      await tester.pumpAndSettle();
      await tester.tap(find.widgetWithText(FilledButton, 'Agregar'));
      await tester.pumpAndSettle();

      final dialogo = find.byType(AlertDialog);
      expect(find.descendant(of: dialogo, matching: find.text('Ingresa el nombre.')), findsOneWidget);
      expect(find.descendant(of: dialogo, matching: find.text('RUT no válido.')), findsOneWidget);
      expect(find.descendant(of: dialogo, matching: find.text('Edad entre 0 y 120.')), findsOneWidget);

      await tester.tap(find.text('Cancelar'));
      await tester.pumpAndSettle();
      expect(find.byType(AlertDialog), findsNothing);
      expect(find.text('Voy solo.'), findsOneWidget);
    });
  });

  group('senderos', () {
    testWidgets('si no cargan, ofrece reintentar', (tester) async {
      _pantallaAlta(tester);
      var llamadas = 0;
      final repo = _RepoFalso(senderos: () async {
        if (++llamadas == 1) throw const ApiException('No se pudo conectar con el servidor.');
        return [_nahuelbuta];
      });
      await _abrir(tester, repo);

      expect(find.textContaining('No se pudieron cargar los senderos'), findsOneWidget);
      expect(find.textContaining('No se pudo conectar con el servidor.'), findsOneWidget);

      await tester.tap(find.text('Reintentar'));
      await tester.pumpAndSettle();

      expect(find.byKey(const ValueKey('campo-sendero')), findsOneWidget);
    });
  });

  group('desde la home', () {
    testWidgets('"Avisar salida" abre el formulario y, al enviar, la lista se actualiza', (tester) async {
      _pantallaAlta(tester);
      final repo = ExcursionDemoRepository();
      await tester.pumpWidget(MaterialApp(
        home: ExcursionesPage(repo: repo, nombreUsuario: 'Trekker', onLogout: () {}),
      ));
      await tester.pump(const Duration(seconds: 1));
      expect(find.text('Planificada'), findsOneWidget);

      await tester.tap(find.widgetWithText(FloatingActionButton, 'Avisar salida'));
      await tester.pump(const Duration(seconds: 1));
      await tester.pumpAndSettle();
      await _llenarObligatorios(tester);
      await tester.ensureVisible(_botonEnviar);
      await tester.tap(_botonEnviar);
      await tester.pump(const Duration(seconds: 1)); // latencia del repo demo
      await tester.pumpAndSettle();

      expect(find.byType(AvisoPage), findsNothing);
      expect(find.textContaining('Salida avisada'), findsOneWidget);
      await tester.pump(const Duration(seconds: 1));
      expect(find.text('Planificada'), findsNWidgets(2), reason: 'La nueva excursión aparece en la lista');
    });
  });
}
