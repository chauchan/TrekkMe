import { HttpErrorResponse } from '@angular/common/http';

// Traduce los errores de la API y de Firebase a un mensaje que el usuario entienda.
// La usan todos los servicios, para no repetir el mismo bloque en cada uno.
export function traducirError(error: any): Error {
  console.error(error);

  // Errores de la API (Laravel)
  if (error instanceof HttpErrorResponse) {
    if (error.status === 0) {
      return new Error('No se pudo conectar con el servidor.');
    }
    if (error.status === 401) {
      return new Error('Tu sesión expiró. Vuelve a iniciar sesión.');
    }
    if (error.status === 403) {
      return new Error('No tienes acceso a este caso.');
    }
    if (error.status === 422) {
      const mensajes = Object.values(error.error?.errors ?? {}).flat();
      return new Error(mensajes.join(' ') || 'Revisa los datos ingresados.');
    }
    // 409 y los demás: el servidor manda el mensaje listo
    // (por ejemplo "Otra persona ya gestionó este aviso.")
    return new Error(error.error?.message ?? 'Ocurrió un error en el servidor.');
  }

  // Errores de Firebase
  if (error.code === 'permission-denied') {
    return new Error('No tienes acceso a estos datos.');
  }
  if (error.code === 'failed-precondition') {
    return new Error('Falta crear un índice en Firestore. Revisa la consola (F12).');
  }
  if (error.code) {
    return new Error('No se pudo conectar con la base de datos.');
  }

  // Sin "code" es uno de nuestros propios mensajes: lo dejamos pasar tal cual
  return error;
}
