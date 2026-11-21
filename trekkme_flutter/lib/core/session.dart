import 'package:flutter/foundation.dart';

import '../data/auth_repository.dart';
import '../models/usuario.dart';

/// Sesión del trekker. La app escucha este objeto para decidir si muestra
/// el login o la home.
class Session extends ChangeNotifier {
  Session(this._auth);

  final AuthRepository _auth;
  Usuario? _usuario;

  Usuario? get usuario => _usuario;
  bool get autenticado => _usuario != null;

  Future<void> login(String email, String password) async {
    _usuario = await _auth.login(email, password);
    notifyListeners();
  }

  Future<void> logout() async {
    await _auth.logout();
    _usuario = null;
    notifyListeners();
  }
}
