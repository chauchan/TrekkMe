import { Injectable } from '@angular/core';
import { Observable, from } from 'rxjs';
import {
  Timestamp,
  addDoc,
  collection,
  deleteDoc,
  doc,
  getDoc,
  getDocs,
  orderBy,
  query,
  runTransaction,
  where,
} from 'firebase/firestore';
import { db } from '../firebase';
import { Bitacora } from '../models/bitacora';

// Como todavía no hay login, usamos una excursión y un rescatista fijos
const EXCURSION_ACTUAL = 'exc-001';
const RESCATISTA_ACTUAL = 'resc-001';

// Nombre de la colección en Firestore
const COLECCION = 'bitacora';

// Este es el ÚNICO archivo que habla con Firestore
@Injectable({
  providedIn: 'root',
})
export class BitacoraService {

  constructor() { }

  // Devuelve los registros de la excursión actual, del más nuevo al más antiguo
  listar(): Observable<Bitacora[]> {
    return from(this.buscarRegistros());
  }

  // Hace la consulta a Firestore (listar() la convierte en Observable)
  private async buscarRegistros(): Promise<Bitacora[]> {
    try {
      const consulta = query(
        collection(db, COLECCION),
        where('excursionId', '==', EXCURSION_ACTUAL),
        orderBy('registradoAt', 'desc')
      );
      const resultado = await getDocs(consulta);

      // Convierte cada documento en un objeto Bitacora
      const registros: Bitacora[] = [];
      for (const documento of resultado.docs) {
        registros.push(this.convertir(documento.id, documento.data()));
      }
      return registros;
    } catch (error) {
      throw this.error(error);
    }
  }

  // Devuelve un registro por su ID, o null si no existe
  async obtener(id: string): Promise<Bitacora | null> {
    try {
      const documento = await getDoc(doc(db, COLECCION, id));
      if (!documento.exists()) {
        return null;
      }
      return this.convertir(documento.id, documento.data());
    } catch (error) {
      throw this.error(error);
    }
  }

  // Crea un registro nuevo con la fecha actual y version 1
  async crear(texto: string): Promise<void> {
    try {
      await addDoc(collection(db, COLECCION), {
        excursionId: EXCURSION_ACTUAL,
        rescatistaId: RESCATISTA_ACTUAL,
        texto: texto,
        registradoAt: Timestamp.now(),
        version: 1,
      });
    } catch (error) {
      throw this.error(error);
    }
  }

  // Edita el texto SOLO si nadie más cambió el registro desde que lo leímos.
  // La transacción lee y escribe "de una sola vez", así dos personas no se pisan.
  async editar(id: string, texto: string, versionLeida: number): Promise<void> {
    try {
      const referencia = doc(db, COLECCION, id);

      await runTransaction(db, async (transaccion) => {
        const documento = await transaccion.get(referencia);

        if (!documento.exists()) {
          throw new Error('El registro ya no existe.');
        }

        // Condición: la versión guardada debe ser la misma que leímos
        const versionActual = documento.data()['version'];
        if (versionActual !== versionLeida) {
          throw new Error('Alguien modificó este registro. Vuelve a abrirlo e intenta de nuevo.');
        }

        transaccion.update(referencia, {
          texto: texto,
          version: versionActual + 1,
        });
      });
    } catch (error) {
      throw this.error(error);
    }
  }

  // Borra un registro
  async eliminar(id: string): Promise<void> {
    try {
      await deleteDoc(doc(db, COLECCION, id));
    } catch (error) {
      throw this.error(error);
    }
  }

  // Arma un objeto Bitacora, campo por campo, con los datos de Firestore
  private convertir(id: string, datos: any): Bitacora {
    const registro: Bitacora = {
      id: id,
      excursionId: datos.excursionId,
      rescatistaId: datos.rescatistaId,
      texto: datos.texto,
      // Firestore guarda la fecha como Timestamp: la pasamos a Date
      registradoAt: datos.registradoAt.toDate(),
      version: datos.version,
    };
    return registro;
  }

  // Traduce los errores de Firebase a un mensaje que el usuario entienda
  private error(error: any): Error {
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
}
