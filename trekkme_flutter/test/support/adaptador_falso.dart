import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';

/// Respuesta JSON para un adaptador falso.
ResponseBody jsonRespuesta(Object cuerpo, [int status = 200]) =>
    ResponseBody.fromString(
      jsonEncode(cuerpo),
      status,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );

/// Adaptador HTTP que no sale a la red: guarda las peticiones y responde con
/// lo que decida [responder]. Si [responder] lanza una DioException, la
/// petición falla como una caída de red.
class AdaptadorFalso implements HttpClientAdapter {
  AdaptadorFalso(this.responder);

  final ResponseBody Function(RequestOptions) responder;
  final List<RequestOptions> peticiones = [];

  /// Peticiones cuya URL contiene [texto].
  List<RequestOptions> a(String texto) =>
      peticiones.where((p) => p.uri.toString().contains(texto)).toList();

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    peticiones.add(options);
    return responder(options);
  }

  @override
  void close({bool force = false}) {}
}

/// Caída de red: no hay respuesta.
DioException sinRed(RequestOptions options) => DioException(
      requestOptions: options,
      type: DioExceptionType.connectionError,
    );
