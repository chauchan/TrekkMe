import { Injectable } from '@angular/core';
import { Timestamp, addDoc, collection, doc, getDoc, runTransaction } from 'firebase/firestore';
import { aBitacora } from '../../../core/conversores';
import { traducirError } from '../../../core/errores';
import { aTimestamp } from '../../../core/fechas';
import { COLECCION_BITACORA, db } from '../../../core/firebase';
import { Bitacora, RUTAS } from '../../../models/bitacora';
import { RESCATISTA_ACTUAL } from '../../../models/rescatista';

// Servicio del formulario de la bitácora (actor: rescatista). Lo provee solo BitacoraFormPage.
@Injectable()
export class BitacoraFormService {

  // Senderos para el selector "Ruta". Más adelante vendrán del backend (catálogo de senderos)
  listarRutas(): string[] {
    return RUTAS;
  }

  // Un registro por su ID, o null si no existe
  async obtener(id: string): Promise<Bitacora | null> {
    try {
      const documento = await getDoc(doc(db, COLECCION_BITACORA, id));
      if (!documento.exists()) {
        return null;
      }
      return aBitacora(documento.id, documento.data());
    } catch (error) {
      throw traducirError(error);
    }
  }

  // Crea un registro nuevo dentro de un caso, con la fecha actual y version 1
  async crear(casoId: string, ruta: string, horaInicio: Date, horaFin: Date | null, texto: string): Promise<void> {
    try {
      await addDoc(collection(db, COLECCION_BITACORA), {
        excursionId: casoId,
        rescatistaId: RESCATISTA_ACTUAL,
        ruta: ruta,
        horaInicio: Timestamp.fromDate(horaInicio),
        horaFin: aTimestamp(horaFin),
        texto: texto,
        registradoAt: Timestamp.now(),
        version: 1,
      });
    } catch (error) {
      throw traducirError(error);
    }
  }

  // Edita el registro SOLO si nadie más lo cambió desde que lo leímos.
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
      const referencia = doc(db, COLECCION_BITACORA, id);

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
          horaFin: aTimestamp(horaFin),
          texto: texto,
          version: versionActual + 1,
        });
      });
    } catch (error) {
      throw traducirError(error);
    }
  }
}
