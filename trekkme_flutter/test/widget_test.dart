import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:trekkme/core/session.dart';
import 'package:trekkme/data/auth_demo_repository.dart';
import 'package:trekkme/data/excursion_demo_repository.dart';
import 'package:trekkme/main.dart';

// La app de verdad usa la API; estas pruebas la arman con los repositorios de demo.
MyApp _app() => MyApp(
      session: Session(AuthDemoRepository()),
      repo: ExcursionDemoRepository(),
    );

// Credenciales de demo definidas en lib/data/auth_demo_repository.dart.
const _email = 'trekker@trekkme.test';
const _password = 'trekker123';

Future<void> _entrar(WidgetTester tester, String email, String password) async {
  await tester.enterText(find.byType(TextFormField).at(0), email);
  await tester.enterText(find.byType(TextFormField).at(1), password);
  await tester.tap(find.text('Entrar'));
  await tester.pump(); // inicia la petición
  await tester.pump(const Duration(seconds: 1)); // respuesta del demo
}

void main() {
  testWidgets('Sin sesión se ve el login y valida campos vacíos',
      (tester) async {
    await tester.pumpWidget(_app());
    expect(find.text('Entrar'), findsOneWidget);
    expect(find.text('Mis excursiones'), findsNothing);

    await tester.tap(find.text('Entrar'));
    await tester.pump();
    expect(find.text('Ingresa tu correo.'), findsOneWidget);
    expect(find.text('Ingresa tu contraseña.'), findsOneWidget);
  });

  testWidgets('Credenciales malas muestran el error y no entran',
      (tester) async {
    await tester.pumpWidget(_app());
    await _entrar(tester, _email, 'otra-clave');

    expect(find.text('Correo o contraseña incorrectos.'), findsOneWidget);
    expect(find.text('Mis excursiones'), findsNothing);
  });

  testWidgets('Login correcto lista excursiones y se puede cerrar sesión',
      (tester) async {
    await tester.pumpWidget(_app());
    await _entrar(tester, _email, _password);

    expect(find.text('Mis excursiones'), findsOneWidget);
    await tester.pump(const Duration(seconds: 1)); // latencia del repo demo
    expect(find.text('PN Nahuelbuta - Piedra del Águila'), findsOneWidget);
    expect(find.text('En curso'), findsOneWidget);

    await tester.tap(find.byIcon(Icons.logout));
    await tester.pump();
    await tester.pump();
    expect(find.text('Entrar'), findsOneWidget);
    expect(find.text('Mis excursiones'), findsNothing);
  });
}
