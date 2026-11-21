/// Validación y formato del RUT chileno (dígito verificador con módulo 11).
class Rut {
  const Rut._();

  /// Deja solo cuerpo y dígito verificador en mayúscula ("12.345.678-5" →
  /// "123456785"). Devuelve null si no tiene 7 u 8 dígitos más el verificador.
  static String? _limpiar(String texto) {
    final limpio = texto.replaceAll(RegExp(r'[.\-\s]'), '').toUpperCase();
    return RegExp(r'^\d{7,8}[\dK]$').hasMatch(limpio) ? limpio : null;
  }

  static String _digitoVerificador(String cuerpo) {
    var suma = 0;
    var factor = 2;
    for (final d in cuerpo.split('').reversed) {
      suma += int.parse(d) * factor;
      factor = factor == 7 ? 2 : factor + 1;
    }
    final resto = 11 - suma % 11;
    return switch (resto) { 11 => '0', 10 => 'K', _ => '$resto' };
  }

  /// ¿Es un RUT con formato y dígito verificador correctos?
  static bool esValido(String texto) {
    final limpio = _limpiar(texto);
    if (limpio == null) return false;
    final cuerpo = limpio.substring(0, limpio.length - 1);
    return _digitoVerificador(cuerpo) == limpio[limpio.length - 1];
  }

  /// "123456785" o "12.345.678-5" → "12.345.678-5". Si no es un RUT bien
  /// formado, devuelve el texto tal cual.
  static String formatear(String texto) {
    final limpio = _limpiar(texto);
    if (limpio == null) return texto.trim();
    final cuerpo = limpio.substring(0, limpio.length - 1);
    final puntos = cuerpo.replaceAllMapped(
      RegExp(r'(\d)(?=(\d{3})+$)'),
      (m) => '${m[1]}.',
    );
    return '$puntos-${limpio[limpio.length - 1]}';
  }
}
