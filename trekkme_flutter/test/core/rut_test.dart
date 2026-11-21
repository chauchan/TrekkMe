import 'package:flutter_test/flutter_test.dart';
import 'package:trekkme/core/rut.dart';

void main() {
  group('Rut.esValido', () {
    test('acepta RUT con el dígito verificador correcto', () {
      for (final rut in [
        '11.111.111-1',
        '12.345.678-5',
        '7.654.321-6',
        '10.000.013-K', // verificador K
        '10.000.013-k', // en minúscula
        '1.000.013-0', // verificador 0
        '15.000.000-9',
      ]) {
        expect(Rut.esValido(rut), isTrue, reason: rut);
      }
    });

    test('acepta el RUT sin puntos ni guion o con espacios', () {
      expect(Rut.esValido('123456785'), isTrue);
      expect(Rut.esValido('12345678-5'), isTrue);
      expect(Rut.esValido(' 12.345.678-5 '), isTrue);
    });

    test('rechaza un dígito verificador equivocado', () {
      expect(Rut.esValido('12.345.678-4'), isFalse);
      expect(Rut.esValido('12.345.678-K'), isFalse);
      expect(Rut.esValido('11.111.111-2'), isFalse);
      // Los RUT de los datos de ejemplo de Ionic no son reales
      expect(Rut.esValido('20.000.004-4'), isFalse);
    });

    test('rechaza lo que no tiene forma de RUT', () {
      for (final rut in ['', '   ', '123', '1-9', '12.345.678', 'abcdefgh-1', '123456789012-3', '12.345.67K-5']) {
        expect(Rut.esValido(rut), isFalse, reason: '"$rut"');
      }
    });
  });

  group('Rut.formatear', () {
    test('agrega puntos y guion', () {
      expect(Rut.formatear('123456785'), '12.345.678-5');
      expect(Rut.formatear('76543216'), '7.654.321-6');
      expect(Rut.formatear('12345678-5'), '12.345.678-5');
      expect(Rut.formatear('10000013k'), '10.000.013-K');
    });

    test('un RUT ya formateado queda igual', () {
      expect(Rut.formatear('12.345.678-5'), '12.345.678-5');
    });

    test('si no tiene forma de RUT devuelve el texto sin espacios sobrantes', () {
      expect(Rut.formatear('  hola '), 'hola');
    });
  });
}
