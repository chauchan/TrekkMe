import 'package:flutter/material.dart';

import 'core/session.dart';
import 'data/auth_demo_repository.dart';
import 'data/excursion_demo_repository.dart';
import 'data/excursion_repository.dart';
import 'features/excursiones/excursiones_page.dart';
import 'features/login/login_page.dart';

void main() {
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  MyApp({super.key});

  // Cambiar por las implementaciones de la API cuando Laravel tenga los
  // endpoints del excursionista.
  final Session _session = Session(AuthDemoRepository());
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
      home: ListenableBuilder(
        listenable: _session,
        builder: (context, _) {
          final usuario = _session.usuario;
          if (usuario == null) return LoginPage(session: _session);
          return ExcursionesPage(
            repo: _repo,
            nombreUsuario: usuario.nombre,
            onLogout: _session.logout,
          );
        },
      ),
    );
  }
}
