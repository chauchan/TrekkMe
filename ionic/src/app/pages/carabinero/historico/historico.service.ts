import { Injectable } from '@angular/core';
import { Observable, from } from 'rxjs';
import { aAviso } from '../../../core/conversores';
import { buscarPorEstados } from '../../../core/excursiones-firestore';
import { Aviso, ESTADO_CERRADA } from '../../../models/excursion';

// Servicio del histórico de avisos (actor: carabinero). Lo provee solo HistoricoPage.
@Injectable()
export class HistoricoService {

  // Avisos cerrados: reemplazan el libro de guardia en papel
  listar(): Observable<Aviso[]> {
    return from(this.buscarCerrados());
  }

  private async buscarCerrados(): Promise<Aviso[]> {
    const excursiones = await buscarPorEstados([ESTADO_CERRADA]);
    return excursiones.map(aAviso);
  }
}
