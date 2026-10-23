import { Injectable } from '@angular/core';
import { aAviso } from '../../../core/conversores';
import { cambiarEstadoSi, obtenerPorId } from '../../../core/excursiones-firestore';
import {
  Aviso,
  ESTADO_CERRADA,
  ESTADO_CIERRE_PENDIENTE,
  ESTADO_EN_ALERTA,
  ESTADO_EN_OPERATIVO,
} from '../../../models/excursion';
import { RESCATISTAS, Rescatista } from '../../../models/rescatista';

// Servicio del detalle del aviso (actor: carabinero). Lo provee solo AvisoPage.
// Cada acción del carabinero es un método: la página no decide de qué estado a qué estado se pasa.
@Injectable()
export class AvisoService {

  // Un aviso por su ID, sin datos médicos, o null si no existe
  async obtener(id: string): Promise<Aviso | null> {
    const excursion = await obtenerPorId(id);
    if (excursion === null) {
      return null;
    }
    return aAviso(excursion);
  }

  // Rescatistas que se pueden asignar al escalar.
  // Más adelante vendrán del backend (usuarios con perfil rescatista de la jurisdicción).
  listarRescatistas(): Rescatista[] {
    return RESCATISTAS;
  }

  // En alerta → cerrada (falsa alarma)
  cerrarFalsaAlarma(id: string): Promise<void> {
    return cambiarEstadoSi(id, ESTADO_EN_ALERTA, { estado: ESTADO_CERRADA });
  }

  // En alerta → en operativo, asignando el rescatista en el mismo cambio
  escalar(id: string, rescatistaId: string): Promise<void> {
    return cambiarEstadoSi(id, ESTADO_EN_ALERTA, {
      estado: ESTADO_EN_OPERATIVO,
      rescatistaId: rescatistaId,
    });
  }

  // Cierre pendiente → cerrada (el carabinero confirma lo que pidió el rescatista)
  confirmarCierre(id: string): Promise<void> {
    return cambiarEstadoSi(id, ESTADO_CIERRE_PENDIENTE, { estado: ESTADO_CERRADA });
  }

  // Cierre pendiente → en operativo (el carabinero rechaza el cierre)
  rechazarCierre(id: string): Promise<void> {
    return cambiarEstadoSi(id, ESTADO_CIERRE_PENDIENTE, { estado: ESTADO_EN_OPERATIVO });
  }
}
