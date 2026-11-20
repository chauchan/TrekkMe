import 'package:flutter/material.dart';

import 'data/excursion_demo_repository.dart';
import 'data/excursion_repository.dart';
import 'features/excursiones/excursiones_page.dart';

void main() {
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  MyApp({super.key});

  // Cambiar por la implementación de la API cuando Laravel tenga los
  // endpoints del excursionista.
  final ExcursionRepository _repo = ExcursionDemoRepository();

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'TrekkMe',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color.fromARGB(255, 30, 91, 171),
        ),
      ),
      home: ExcursionesPage(repo: _repo),
    );
  }
}
