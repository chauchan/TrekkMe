import 'package:battery_plus/battery_plus.dart';
import 'package:geolocator/geolocator.dart';

import 'api_exception.dart';

/// Posición del teléfono y, si se puede saber, su batería (0 a 100).
typedef Lectura = ({double lat, double lng, int? bateria});

/// Cómo obtener la [Lectura]. Las pantallas lo reciben para que los tests no
/// dependan del GPS real.
typedef LeerTelefono = Future<Lectura> Function();

/// Lee el GPS y la batería del dispositivo. Pide el permiso de ubicación la
/// primera vez; los problemas se informan como [ApiException] para mostrarlos
/// igual que los demás errores.
Future<Lectura> leerTelefono() async {
  if (!await Geolocator.isLocationServiceEnabled()) {
    throw const ApiException('Activa la ubicación del teléfono.');
  }

  var permiso = await Geolocator.checkPermission();
  if (permiso == LocationPermission.denied) {
    permiso = await Geolocator.requestPermission();
  }
  if (permiso == LocationPermission.denied ||
      permiso == LocationPermission.deniedForever) {
    throw const ApiException('TrekkMe necesita permiso para usar tu ubicación.');
  }

  final Position posicion;
  try {
    posicion = await Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(timeLimit: Duration(seconds: 15)),
    );
  } catch (_) {
    throw const ApiException('No se pudo obtener tu ubicación. Intenta de nuevo.');
  }

  return (
    lat: posicion.latitude,
    lng: posicion.longitude,
    bateria: await _bateria(),
  );
}

// La batería es opcional: el simulador de iOS, por ejemplo, no la informa
Future<int?> _bateria() async {
  try {
    final nivel = await Battery().batteryLevel;
    return nivel >= 0 && nivel <= 100 ? nivel : null;
  } catch (_) {
    return null;
  }
}
