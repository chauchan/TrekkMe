import { Injectable } from '@angular/core';
import { Observable, from } from 'rxjs';
import { doc, setDoc } from 'firebase/firestore';
import { aAviso } from '../../../core/conversores';
import { bitacorasDeEjemplo, excursionesDeEjemplo } from '../../../core/datos-ejemplo';
import { traducirError } from '../../../core/errores';
import { buscarPorEstados } from '../../../core/excursiones-firestore';
import { COLECCION_BITACORA, COLECCION_EXCURSIONES, db } from '../../../core/firebase';
import {
  Aviso,
  ESTADO_CIERRE_PENDIENTE,
  ESTADO_EN_ALERTA,
  ESTADO_EN_CURSO,
  ESTADO_EN_OPERATIVO,
  ESTADO_PLANIFICADA,
} from '../../../models/excursion';

// Estados que aparecen en la bandeja del turno: todos menos los cerrados
const ESTADOS_ACTIVOS = [
  ESTADO_PLANIFICADA,
  ESTADO_EN_CURSO,
  ESTADO_EN_ALERTA,
  ESTADO_EN_OPERATIVO,
  ESTADO_CIERRE_PENDIENTE,
];

// Servicio de la bandeja del turno (actor: carabinero).
// Sin "providedIn: 'root'": lo provee solo BandejaPage, así no lo puede usar otra página.
@Injectable()
export class BandejaService {

  // Avisos activos de la jurisdicción, sin datos médicos
  listar(): Observable<Aviso[]> {
    return from(this.buscarActivos());
  }

  private async buscarActivos(): Promise<Aviso[]> {
    const excursiones = await buscarPorEstados(ESTADOS_ACTIVOS);
    return excursiones.map(aAviso);
  }

  // Solo para la demo: crea (o reinicia) las excursiones y la bitácora de ejemplo
  async cargarEjemplos(): Promise<void> {
    try {
      for (const [id, datos] of Object.entries(excursionesDeEjemplo())) {
        await setDoc(doc(db, COLECCION_EXCURSIONES, id), datos);
      }
      for (const [id, datos] of Object.entries(bitacorasDeEjemplo())) {
        await setDoc(doc(db, COLECCION_BITACORA, id), datos);
      }
    } catch (error) {
      throw traducirError(error);
    }
  }
}
