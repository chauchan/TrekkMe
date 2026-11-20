import 'package:flutter_test/flutter_test.dart';

import 'package:trekkme/main.dart';

void main() {
  testWidgets('La home lista las excursiones con su estado', (tester) async {
    await tester.pumpWidget(MyApp());
    expect(find.text('Mis excursiones'), findsOneWidget);

    // El repositorio demo simula latencia.
    await tester.pump(const Duration(seconds: 1));
    await tester.pump();

    expect(find.text('PN Nahuelbuta - Piedra del Águila'), findsOneWidget);
    expect(find.text('En curso'), findsOneWidget);
    expect(find.text('Planificada'), findsOneWidget);
    expect(find.text('Cerrada'), findsOneWidget);
  });
}
