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
  setDoc,
  where,
} from 'firebase/firestore';
import { db } from '../firebase';
import { Bitacora } from '../models/bitacora';
import { RESCATISTA_ACTUAL } from '../models/rescatista';

// Nombre de la colección en Firestore
const COLECCION = 'bitacora';

// Este es el ÚNICO archivo que habla con Firestore
@Injectable({
  providedIn: 'root',
})
export class BitacoraService {

  constructor() { }

  // Devuelve los registros de un caso, del más nuevo al más antiguo.
  // El caso es una excursión, por eso el campo se llama excursionId
  listar(casoId: string): Observable<Bitacora[]> {
    return from(this.buscarRegistros(casoId));
  }

  // Hace la consulta a Firestore (listar() la convierte en Observable)
  private async buscarRegistros(casoId: string): Promise<Bitacora[]> {
    try {
      const consulta = query(
        collection(db, COLECCION),
        where('excursionId', '==', casoId),
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

  // Crea un registro nuevo dentro de un caso, con la fecha actual y version 1
  async crear(casoId: string, ruta: string, horaInicio: Date, horaFin: Date | null, texto: string): Promise<void> {
    try {
      await addDoc(collection(db, COLECCION), {
        excursionId: casoId,
        rescatistaId: RESCATISTA_ACTUAL,
        ruta: ruta,
        horaInicio: Timestamp.fromDate(horaInicio),
        horaFin: this.aTimestamp(horaFin),
        texto: texto,
        registradoAt: Timestamp.now(),
        version: 1,
      });
    } catch (error) {
      throw this.error(error);
    }
  }

  // Edita el registro SOLO si nadie más cambió el registro desde que lo leímos.
  // La transacción lee y escribe "de una sola vez", así dos personas no se pisan.
  async editar(
    id: string,
    ruta: string,
    horaInicio: Date,
    horaFin: Date | null,
    texto: string,
    versionLeida: number
  ): Promise<void> {
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
          ruta: ruta,
          horaInicio: Timestamp.fromDate(horaInicio),
          horaFin: this.aTimestamp(horaFin),
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

  // Crea (o reinicia) registros de bitácora de ejemplo para los casos de ejemplo
  async cargarEjemplos(): Promise<void> {
    try {
      await setDoc(doc(db, COLECCION, 'bit-001'), {
        excursionId: 'exc-001',
        rescatistaId: 'resc-001',
        ruta: 'PN Nonguén - Sendero Los Canelos',
        horaInicio: this.haceHoras(20),
        horaFin: this.haceHoras(16),
        texto: 'Primera salida. Recorrimos el sendero completo hasta el mirador sin encontrar rastros. Se entrevistó a dos visitantes que no la vieron.',
        registradoAt: this.haceHoras(16),
        version: 1,
      });
      await setDoc(doc(db, COLECCION, 'bit-002'), {
        excursionId: 'exc-001',
        rescatistaId: 'resc-001',
        ruta: 'Fuera de sendero',
        horaInicio: this.haceHoras(12),
        horaFin: this.haceHoras(8),
        texto: 'Búsqueda por la quebrada al norte del mirador. Se encontró un envoltorio de barra de cereal reciente. Sin contacto visual ni respuesta a los llamados.',
        registradoAt: this.haceHoras(8),
        version: 1,
      });
      await setDoc(doc(db, COLECCION, 'bit-003'), {
        excursionId: 'exc-001',
        rescatistaId: 'resc-001',
        ruta: 'PN Nonguén - Sendero Los Copihues',
        horaInicio: this.haceHoras(2),
        horaFin: null,
        texto: 'Tercera salida con apoyo de un guardaparque. Revisando el cruce con el sendero Los Copihues.',
        registradoAt: this.haceHoras(2),
        version: 1,
      });
      await setDoc(doc(db, COLECCION, 'bit-004'), {
        excursionId: 'exc-006',
        rescatistaId: 'resc-001',
        ruta: 'Laguna del Laja - Sierra Velluda',
        horaInicio: this.haceHoras(66),
        horaFin: this.haceHoras(58),
        texto: 'Día 1. Ascenso por la ruta declarada hasta el primer campamento. No hay carpa ni señales de pernocte.',
        registradoAt: this.haceHoras(58),
        version: 1,
      });
      await setDoc(doc(db, COLECCION, 'bit-005'), {
        excursionId: 'exc-006',
        rescatistaId: 'resc-001',
        ruta: 'Laguna del Laja - Sierra Velluda',
        horaInicio: this.haceHoras(42),
        horaFin: this.haceHoras(33),
        texto: 'Día 2. Se amplía la búsqueda hacia el sector del glaciar. Mala visibilidad por neblina, se suspende a las 18:00.',
        registradoAt: this.haceHoras(33),
        version: 1,
      });
      await setDoc(doc(db, COLECCION, 'bit-006'), {
        excursionId: 'exc-007',
        rescatistaId: 'resc-001',
        ruta: 'PN Nonguén - Sendero Los Copihues',
        horaInicio: this.haceHoras(31),
        horaFin: this.haceHoras(29),
        texto: 'Se encuentra a las dos personas en buen estado a 400 metros del sendero. Se habían desorientado al anochecer. Se acompaña su descenso.',
        registradoAt: this.haceHoras(29),
        version: 1,
      });
    } catch (error) {
      throw this.error(error);
    }
  }

  // Devuelve la fecha de hace N horas
  private haceHoras(horas: number): Timestamp {
    const milisegundos = horas * 60 * 60 * 1000;
    return Timestamp.fromMillis(Date.now() - milisegundos);
  }

  // Firestore guarda las fechas como Timestamp. La hora de fin puede venir vacía (null)
  private aTimestamp(fecha: Date | null): Timestamp | null {
    if (fecha === null) {
      return null;
    }
    return Timestamp.fromDate(fecha);
  }

  // Arma un objeto Bitacora, campo por campo, con los datos de Firestore
  private convertir(id: string, datos: any): Bitacora {
    // Los registros antiguos no tienen hora de inicio: usamos la fecha de creación
    let horaInicio = datos.registradoAt.toDate();
    if (datos.horaInicio) {
      horaInicio = datos.horaInicio.toDate();
    }

    // La hora de fin es opcional
    let horaFin = null;
    if (datos.horaFin) {
      horaFin = datos.horaFin.toDate();
    }

    const registro: Bitacora = {
      id: id,
      excursionId: datos.excursionId,
      rescatistaId: datos.rescatistaId,
      ruta: datos.ruta || '',
      horaInicio: horaInicio,
      horaFin: horaFin,
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
