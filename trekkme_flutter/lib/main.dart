import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import 'core/config.dart';
import 'core/dio_client.dart';
import 'core/session.dart';
import 'data/auth_api_repository.dart';
import 'data/auth_demo_repository.dart';
import 'data/excursion_api_repository.dart';
import 'data/excursion_demo_repository.dart';
import 'data/excursion_repository.dart';
import 'features/excursiones/excursiones_page.dart';
import 'features/login/login_page.dart';

void main() {
  runApp(MyApp());
}

/// Arma las dependencias según la configuración: con backend real o, con
/// `--dart-define=USE_DEMO=true`, con los repositorios de demostración.
(Session, ExcursionRepository) _crearDependencias() {
  if (AppConfig.useDemo) {
    return (Session(AuthDemoRepository()), ExcursionDemoRepository());
  }

  final auth = AuthApiRepository(
    api: DioClient.publico(AppConfig.apiBaseUrl),
    firebase: Dio(BaseOptions(
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 15),
    )),
    apiKey: AppConfig.firebaseApiKey,
  );
  final session = Session(auth);

  // Cliente con sesión: agrega el ID token y, si la API responde 401, vuelve al login.
  final dio = DioClient.autenticado(
    AppConfig.apiBaseUrl,
    token: auth.idToken,
    onSesionVencida: session.logout,
  );
  return (session, ExcursionApiRepository(dio));
}

class MyApp extends StatelessWidget {
  /// Sin argumentos usa la configuración de la app; los tests pasan los suyos.
  MyApp({Key? key, Session? session, ExcursionRepository? repo})
      : this._(
          key,
          session != null && repo != null
              ? (session, repo)
              : _crearDependencias(),
        );

  MyApp._(Key? key, (Session, ExcursionRepository) dependencias)
      : _session = dependencias.$1,
        _repo = dependencias.$2,
        super(key: key);

  final Session _session;
  final ExcursionRepository _repo;

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
