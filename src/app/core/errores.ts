// Traduce los errores de Firebase a un mensaje que el usuario entienda.
// La usan todos los servicios, para no repetir el mismo bloque en cada uno.
export function traducirError(error: any): Error {
  console.error(error);

  if (error.code === 'failed-precondition') {
    return new Error('Falta crear un índice en Firestore. Revisa la consola (F12).');
  }
  if (error.code) {
    return new Error('No se pudo conectar con la base de datos.');
  }
  // Sin "code" es uno de nuestros propios mensajes: lo dejamos pasar tal cual
  return error;
}
