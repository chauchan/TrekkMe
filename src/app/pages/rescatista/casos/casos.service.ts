import { Injectable } from '@angular/core';
import { Observable, from } from 'rxjs';
import { buscarPorRescatista, cambiarEstadoSi } from '../../../core/excursiones-firestore';
import { ESTADO_CIERRE_PENDIENTE, ESTADO_EN_OPERATIVO, Excursion } from '../../../models/excursion';
import { RESCATISTA_ACTUAL } from '../../../models/rescatista';

// Servicio de casos asignados (actor: rescatista). Lo provee solo CasosPage.
@Injectable()
export class CasosService {

  // Casos del rescatista que entró: en operativo o esperando que Carabineros confirme el cierre.
  // Como todavía no hay login, el rescatista es siempre RESCATISTA_ACTUAL.
  listar(): Observable<Excursion[]> {
    return from(this.buscarAbiertos());
  }

  private async buscarAbiertos(): Promise<Excursion[]> {
    const excursiones = await buscarPorRescatista(RESCATISTA_ACTUAL);
    return excursiones.filter(
      (caso) => caso.estado === ESTADO_EN_OPERATIVO || caso.estado === ESTADO_CIERRE_PENDIENTE
    );
  }

  // En operativo → cierre pendiente (falta la confirmación de Carabineros)
  solicitarCierre(id: string): Promise<void> {
    return cambiarEstadoSi(id, ESTADO_EN_OPERATIVO, { estado: ESTADO_CIERRE_PENDIENTE });
  }
}
