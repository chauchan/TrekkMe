import '../models/excursion.dart';
import '../models/sendero.dart';
import 'excursion_repository.dart';

/// Implementación en memoria para trabajar sin backend (plan B del plan de
/// desarrollo). Los senderos son los mismos de los datos de ejemplo de Ionic.
class ExcursionDemoRepository implements ExcursionRepository {
  ExcursionDemoRepository() {
    final ahora = DateTime.now();
    _excursiones.addAll([
      Excursion(
        id: 'exc-demo-1',
        sendero: _senderos[0],
        salidaAt: ahora.subtract(const Duration(hours: 3)),
        retornoAt: ahora.add(const Duration(hours: 4)),
        estado: EstadoExcursion.enCurso,
        contactoNombre: 'Luis Muñoz (esposo)',
        contactoTelefono: '+56 9 5555 0106',
        detalle: 'Chaqueta roja y mochila gris.',
        llevaLinterna: true,
        ultimaLat: -37.8235,
        ultimaLng: -73.0312,
        ultimaUbicacionAt: ahora.subtract(const Duration(hours: 1)),
        bateria: 78,
      ),
      Excursion(
        id: 'exc-demo-2',
        sendero: _senderos[1],
        salidaAt: ahora.add(const Duration(days: 2)),
        retornoAt: ahora.add(const Duration(days: 2, hours: 8)),
        estado: EstadoExcursion.planificada,
        contactoNombre: 'Paula Díaz (madre)',
        contactoTelefono: '+56 9 5555 0108',
      ),
      Excursion(
        id: 'exc-demo-3',
        sendero: _senderos[2],
        salidaAt: ahora.subtract(const Duration(days: 5, hours: 8)),
        retornoAt: ahora.subtract(const Duration(days: 5)),
        estado: EstadoExcursion.cerrada,
        contactoNombre: 'Pedro Castro (hermano)',
        contactoTelefono: '+56 9 5555 0110',
      ),
    ]);
  }

  static const _senderos = [
    Sendero(id: 'sen-001', nombre: 'PN Nahuelbuta - Piedra del Águila'),
    Sendero(id: 'sen-002', nombre: 'PN Nonguén - Sendero Los Copihues'),
    Sendero(id: 'sen-003', nombre: 'Laguna del Laja - Sierra Velluda'),
  ];

  final List<Excursion> _excursiones = [];
  int _siguiente = 4;

  Future<void> _latencia() =>
      Future<void>.delayed(const Duration(milliseconds: 400));

  int _indice(String id) {
    final i = _excursiones.indexWhere((e) => e.id == id);
    if (i < 0) throw Exception('La excursión ya no existe.');
    return i;
  }

  @override
  Future<List<Sendero>> senderos() async {
    await _latencia();
    return _senderos;
  }

  @override
  Future<List<Excursion>> misExcursiones() async {
    await _latencia();
    final lista = [..._excursiones]
      ..sort((a, b) => b.salidaAt.compareTo(a.salidaAt));
    return lista;
  }

  @override
  Future<Excursion> crear(NuevaExcursion d) async {
    await _latencia();
    final nueva = Excursion(
      id: 'exc-demo-${_siguiente++}',
      sendero: d.sendero,
      salidaAt: d.salidaAt,
      retornoAt: d.retornoAt,
      estado: EstadoExcursion.planificada,
      contactoNombre: d.contactoNombre,
      contactoTelefono: d.contactoTelefono,
      detalle: d.detalle,
      llevaCarpa: d.llevaCarpa,
      colorCarpa: d.colorCarpa,
      llevaLinterna: d.llevaLinterna,
      acompanantes: d.acompanantes,
    );
    _excursiones.add(nueva);
    return nueva;
  }

  @override
  Future<Excursion> iniciar(Excursion excursion) async {
    await _latencia();
    final i = _indice(excursion.id);
    if (_excursiones[i].estado != EstadoExcursion.planificada) {
      throw Exception('Solo se puede iniciar una excursión planificada.');
    }
    return _excursiones[i] =
        _excursiones[i].copyWith(estado: EstadoExcursion.enCurso);
  }

  @override
  Future<Excursion> enviarUbicacion(
    Excursion excursion, {
    required double lat,
    required double lng,
    int? bateria,
  }) async {
    await _latencia();
    final i = _indice(excursion.id);
    return _excursiones[i] = _excursiones[i].copyWith(
      ultimaLat: lat,
      ultimaLng: lng,
      ultimaUbicacionAt: DateTime.now(),
      bateria: bateria,
    );
  }

  @override
  Future<Excursion> regresar(Excursion excursion) async {
    await _latencia();
    final i = _indice(excursion.id);
    if (_excursiones[i].estado != EstadoExcursion.enCurso) {
      throw Exception('Solo se puede cerrar una excursión en curso.');
    }
    return _excursiones[i] =
        _excursiones[i].copyWith(estado: EstadoExcursion.cerrada);
  }
}
