import { DocumentData, DocumentReference, Query, onSnapshot } from 'firebase/firestore';
import { Observable } from 'rxjs';
import { traducirError } from './errores';

// Lista en vivo: emite el arreglo completo cada vez que algo cambia
export function escuchar<T>(
  consulta: Query,
  convertir: (id: string, datos: DocumentData) => T
): Observable<T[]> {
  return new Observable<T[]>((suscriptor) =>
    // onSnapshot devuelve la función para dejar de escuchar: el Observable la llama al desuscribirse
    onSnapshot(
      consulta,
      (resultado) => suscriptor.next(resultado.docs.map((d) => convertir(d.id, d.data()))),
      (error) => suscriptor.error(traducirError(error))
    )
  );
}

// Un documento en vivo: emite el objeto, o null si no existe
export function escucharUno<T>(
  referencia: DocumentReference,
  convertir: (id: string, datos: DocumentData) => T
): Observable<T | null> {
  return new Observable<T | null>((suscriptor) =>
    onSnapshot(
      referencia,
      (documento) => suscriptor.next(documento.exists() ? convertir(documento.id, documento.data()) : null),
      (error) => suscriptor.error(traducirError(error))
    )
  );
}
