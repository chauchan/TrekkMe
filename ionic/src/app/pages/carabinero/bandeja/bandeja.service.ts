import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { collection, doc, query, setDoc, where } from 'firebase/firestore';
import { aAviso } from '../../../core/conversores';
import { bitacorasDeEjemplo, excursionesDeEjemplo } from '../../../core/datos-ejemplo';
import { traducirError } from '../../../core/errores';
import { COLECCION_AVISOS, COLECCION_BITACORA, COLECCION_EXCURSIONES, db } from '../../../core/firebase';
import { obtenerJurisdiccionId } from '../../../core/sesion';
import { escuchar } from '../../../core/tiempo-real';
import {
  Aviso,
  ESTADO_CERRADA,
  ESTADO_CIERRE_PENDIENTE,
  ESTADO_EN_ALERTA,
  ESTADO_EN_CURSO,
  ESTADO_EN_OPERATIVO,
  ESTADO_PLANIFICADA,
} from '../../../models/excursion';

// Estados que aparecen en la bandeja del turno, como códigos (así los guarda Firestore)
const ESTADOS_ACTIVOS = ['planificada', 'en_curso', 'en_alerta', 'en_operativo', 'cierre_pendiente'];

// Servicio de la bandeja del turno (actor: carabinero).
// Sin "providedIn: 'root'": lo provee solo BandejaPage, así no lo puede usar otra página.
@Injectable()
export class BandejaService {

  // Avisos activos de la jurisdicción, en vivo: emite cada vez que alguno cambia
  listar(): Observable<Aviso[]> {
    const consulta = query(
      collection(db, COLECCION_AVISOS),
      where('jurisdiccionId', '==', obtenerJurisdiccionId()),   // las reglas exigen este filtro
      where('estado', 'in', ESTADOS_ACTIVOS)
    );
    return escuchar(consulta, aAviso);
  }

  // ⏳ TEMPORAL (se borra en el paso 16): crea los casos de ejemplo con el formato nuevo.
  // Escribe directo en Firestore: solo funciona mientras no se publiquen las reglas.
  async cargarEjemplos(): Promise<void> {
    // Los datos de ejemplo usan etiquetas: aquí se pasan a código
    const CODIGOS: Record<string, string> = {
      [ESTADO_PLANIFICADA]: 'planificada',
      [ESTADO_EN_CURSO]: 'en_curso',
      [ESTADO_EN_ALERTA]: 'en_alerta',
      [ESTADO_EN_OPERATIVO]: 'en_operativo',
      [ESTADO_CIERRE_PENDIENTE]: 'cierre_pendiente',
      [ESTADO_CERRADA]: 'cerrada',
    };

    try {
      for (const [id, datos] of Object.entries(excursionesDeEjemplo())) {
        // Ficha completa → "excursiones"
        const excursion: any = { ...datos };
        excursion.estado = CODIGOS[excursion.estado];
        excursion.jurisdiccionId = '1';
        await setDoc(doc(db, COLECCION_EXCURSIONES, id), excursion);

        // Lo que ve el carabinero → "avisos" (sin datos médicos)
        const { condicionesMedicas, acompanantes, ...aviso } = excursion;
        aviso.acompanantes = acompanantes.map((p: any) => ({ nombre: p.nombre, rut: p.rut }));
        await setDoc(doc(db, COLECCION_AVISOS, id), aviso);
      }
      for (const [id, datos] of Object.entries(bitacorasDeEjemplo())) {
        await setDoc(doc(db, COLECCION_BITACORA, id), { ...datos, senderoId: null });
      }
    } catch (error) {
      throw traducirError(error);
    }
  }
}
